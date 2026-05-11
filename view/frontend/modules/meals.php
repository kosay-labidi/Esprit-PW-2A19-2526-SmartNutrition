<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/repascontroller.php';
require_once __DIR__ . '/../../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../../helpers/repas_helpers.php';

$userId = $_SESSION['user']['id_utilisateur'] ?? $_SESSION['user_id'] ?? 1;
$repas = repas_getAllByUser($pdo, (int) $userId);
$aliments = aliment_getAll($pdo);

$details = [];
$totalCalories = 0;
$totalCo2 = 0;
$totalAliments = 0;

foreach ($repas as $item) {
    $id = (int) $item['id_repas'];
    $mealAliments = repas_getAlimentsOfRepas($pdo, $id);
    $totaux = repas_getTotauxNutritionnels($pdo, $id);
    $details[$id] = ['aliments' => $mealAliments, 'totaux' => $totaux];
    $totalCalories += (float) ($totaux['total_calories'] ?? 0);
    $totalCo2 += (float) ($totaux['total_co2'] ?? 0);
    $totalAliments += count($mealAliments);
}

$avgCalories = count($repas) ? round($totalCalories / count($repas)) : 0;
$avgEco = count($repas)
    ? round(array_sum(array_map(fn($item) => scoreEcologique($details[(int) $item['id_repas']]['totaux'] ?? []), $repas)) / count($repas))
    : 0;

$success = $_GET['success'] ?? '';
$error = urldecode($_GET['error'] ?? '');
$mealPayload = [];
foreach ($repas as $item) {
    $id = (int) $item['id_repas'];
    $mealPayload[$id] = [
        'id_repas' => $id,
        'nom_repas' => $item['nom_repas'],
        'date_repas' => date('Y-m-d\TH:i', strtotime($item['date_repas'])),
        'aliments' => array_map(static fn($aliment) => [
            'id_aliment' => (int) $aliment['id_aliment'],
            'nom' => $aliment['nom'],
            'quantite' => (float) $aliment['quantite'],
        ], $details[$id]['aliments'] ?? []),
    ];
}
?>

<section class="content-section" id="meals">
  <div class="section-header meals-hero">
    <div>
      <h2 class="module-title">🍽️ Gestion des Repas</h2>
      <p>Composez vos repas et analysez leurs valeurs nutritionnelles en temps réel.</p>
    </div>
    <div class="meals-actions">
      <a class="meal-btn meal-btn-ghost" href="fo_alimentlist.php">🥗 Aliments</a>
      <button class="meal-btn meal-btn-primary" type="button" onclick="openMealForm()">➕ Nouveau repas</button>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="meal-alert success">Repas <?= htmlspecialchars($success) ?> avec succès.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="meal-alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="meal-stats-grid">
    <div class="meal-stat">
      <span class="meal-stat-icon">🍱</span>
      <strong><?= count($repas) ?></strong>
      <small>Repas enregistrés</small>
    </div>
    <div class="meal-stat">
      <span class="meal-stat-icon">🔥</span>
      <strong><?= $avgCalories ?></strong>
      <small>Calories moyennes</small>
    </div>
    <div class="meal-stat">
      <span class="meal-stat-icon">🌿</span>
      <strong><?= $avgEco ?>/100</strong>
      <small>Score écologique moyen</small>
    </div>
    <div class="meal-stat">
      <span class="meal-stat-icon">🥦</span>
      <strong><?= $totalAliments ?></strong>
      <small>Aliments sélectionnés</small>
    </div>
  </div>

  <div class="meal-toolbar">
    <input id="mealSearch" type="search" placeholder="Rechercher un repas..." oninput="filterDashboardMeals()">
    <select id="mealSort" onchange="sortDashboardMeals()">
      <option value="date_desc">Date récente</option>
      <option value="date_asc">Date ancienne</option>
      <option value="cal_desc">Calories ↓</option>
      <option value="eco_desc">Score éco ↓</option>
    </select>
    <a class="meal-btn meal-btn-ghost" href="fo_repaslist.php">Vue avancée</a>
  </div>

  <form class="meal-form-panel" id="mealFormPanel" action="../../controller/repascontroller.php" method="POST" style="display:none;">
    <input type="hidden" name="action" id="mealFormAction" value="create">
    <input type="hidden" name="id_repas" id="mealFormId" value="">
    <input type="hidden" name="redirect_to" value="../view/frontend/dashboard.html?module=meals">

    <div class="meal-form-head">
      <div>
        <h3 id="mealFormTitle">Nouveau repas</h3>
        <p>Règles métier: nom obligatoire, date valide, au moins un aliment, quantités 1 à 2000g.</p>
      </div>
      <button type="button" onclick="closeMealForm()" title="Fermer">×</button>
    </div>

    <div class="meal-form-grid">
      <label>
        <span>Nom du repas</span>
        <input type="text" name="nom_repas" id="mealNameInput" minlength="2" maxlength="150" required placeholder="Ex: Déjeuner équilibré">
      </label>
      <label>
        <span>Date et heure</span>
        <input type="datetime-local" name="date_repas" id="mealDateInput" required>
      </label>
    </div>

    <?php if (empty($aliments)): ?>
      <div class="meal-alert error">Aucun aliment disponible. Créez d’abord des aliments pour composer un repas.</div>
    <?php else: ?>
      <div class="meal-food-picker">
        <?php foreach ($aliments as $aliment): ?>
          <label class="meal-pick" data-aliment-id="<?= (int) $aliment['id_aliment'] ?>">
            <input type="checkbox" name="aliments[]" value="<?= (int) $aliment['id_aliment'] ?>" onchange="syncMealQuantity(this)">
            <span>
              <strong><?= htmlspecialchars($aliment['nom']) ?></strong>
              <small><?= htmlspecialchars($aliment['type']) ?> · <?= round((float) $aliment['calories']) ?> kcal/100g</small>
            </span>
            <input class="meal-qte" type="number" name="quantites[<?= (int) $aliment['id_aliment'] ?>]" min="1" max="2000" value="100" disabled>
            <em>g</em>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="meal-form-actions">
      <button type="button" class="meal-btn meal-btn-ghost" onclick="fillSampleMeal()">Sample métier</button>
      <button type="button" class="meal-btn meal-btn-ghost" onclick="closeMealForm()">Annuler</button>
      <button type="submit" class="meal-btn meal-btn-primary" <?= empty($aliments) ? 'disabled' : '' ?>>Enregistrer</button>
    </div>
  </form>

  <?php if (empty($repas)): ?>
    <div class="meal-empty">
      <div>🍽️</div>
      <h3>Aucun repas enregistré</h3>
      <p>Créez votre premier repas depuis ce module, ou testez le sample métier.</p>
      <button class="meal-btn meal-btn-primary" type="button" onclick="openMealForm()">Créer un repas</button>
    </div>
  <?php else: ?>
    <div class="meal-list" id="dashboardMealsList">
      <?php foreach ($repas as $item):
        $id = (int) $item['id_repas'];
        $mealDetails = $details[$id];
        $totaux = $mealDetails['totaux'];
        $score = scoreEcologique($totaux);
        $label = labelEcologique($score);
        $calories = round((float) ($totaux['total_calories'] ?? 0));
        $proteines = round((float) ($totaux['total_proteines'] ?? 0), 1);
        $glucides = round((float) ($totaux['total_glucides'] ?? 0), 1);
        $lipides = round((float) ($totaux['total_lipides'] ?? 0), 1);
        $co2 = round((float) ($totaux['total_co2'] ?? 0), 2);
      ?>
      <article class="meal-card"
        data-name="<?= strtolower(htmlspecialchars($item['nom_repas'])) ?>"
        data-date="<?= htmlspecialchars($item['date_repas']) ?>"
        data-cal="<?= $calories ?>"
        data-eco="<?= $score ?>">
        <div class="meal-card-head">
          <div>
            <span class="meal-date"><?= date('d/m/Y H:i', strtotime($item['date_repas'])) ?></span>
            <h3><?= htmlspecialchars($item['nom_repas']) ?></h3>
          </div>
          <div class="meal-eco" style="color:<?= $label['color'] ?>;border-color:<?= $label['color'] ?>;">
            <strong><?= $score ?></strong>
            <span>/100</span>
          </div>
        </div>

        <div class="meal-macros">
          <span>🔥 <?= $calories ?> kcal</span>
          <span>💪 <?= $proteines ?>g prot.</span>
          <span>🌾 <?= $glucides ?>g gluc.</span>
          <span>🫒 <?= $lipides ?>g lip.</span>
          <span>🌍 <?= $co2 ?> kg CO₂</span>
        </div>

        <div class="meal-foods">
          <?php if (empty($mealDetails['aliments'])): ?>
            <span class="meal-food muted">Aucun aliment</span>
          <?php else: ?>
            <?php foreach ($mealDetails['aliments'] as $aliment): ?>
              <span class="meal-food">
                <?= htmlspecialchars($aliment['nom']) ?>
                <small><?= (float) $aliment['quantite'] ?>g</small>
              </span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="meal-card-actions">
          <button type="button" onclick="openMealForm(<?= $id ?>)">Modifier</button>
          <a href="../../controller/repascontroller.php?action=delete&id=<?= $id ?>&redirect_to=../view/frontend/dashboard.html?module=meals" onclick="return confirm('Supprimer ce repas ?')">Supprimer</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div id="mealNoResult" class="meal-empty compact" style="display:none;">
      <div>🔍</div>
      <p>Aucun repas ne correspond à votre recherche.</p>
    </div>
  <?php endif; ?>
</section>

<style>
.module-title {
  background: linear-gradient(135deg, var(--text), var(--blue));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.meals-hero { display:flex; justify-content:space-between; gap:20px; align-items:flex-start; flex-wrap:wrap; }
.meals-actions { display:flex; gap:10px; flex-wrap:wrap; }
.meal-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:10px 18px; border-radius:12px; text-decoration:none; font-weight:700; font-size:.88rem; transition:all .25s; }
button.meal-btn { border:0; cursor:pointer; font-family:inherit; }
.meal-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.meal-btn-primary { color:#fff; background:linear-gradient(135deg,var(--violet),var(--blue)); box-shadow:0 8px 24px rgba(91,62,150,.28); }
.meal-btn-ghost { color:var(--text); border:1px solid rgba(91,62,150,.35); background:rgba(91,62,150,.1); }
.meal-btn:hover { transform:translateY(-2px); }
.meal-stats-grid { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:14px; margin:28px 0; }
.meal-stat { background:var(--card-bg); border:1px solid rgba(91,62,150,.2); border-radius:14px; padding:18px; display:flex; flex-direction:column; gap:6px; min-height:118px; }
.meal-stat-icon { font-size:1.35rem; }
.meal-stat strong { font-size:1.75rem; color:var(--text); line-height:1; }
.meal-stat small { color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; }
.meal-toolbar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.meal-toolbar input, .meal-toolbar select { background:var(--card-bg); color:var(--text); border:1.5px solid rgba(91,62,150,.25); border-radius:12px; padding:11px 14px; outline:none; }
.meal-toolbar input { min-width:260px; flex:1; }
.meal-alert { padding:12px 16px; border-radius:12px; margin:14px 0; font-weight:700; font-size:.86rem; }
.meal-alert.success { color:#2ecc71; background:rgba(46,204,113,.1); border:1px solid rgba(46,204,113,.25); }
.meal-alert.error { color:#e74c3c; background:rgba(231,76,60,.1); border:1px solid rgba(231,76,60,.25); }
.meal-form-panel { background:var(--card-bg); border:1px solid rgba(91,62,150,.22); border-radius:16px; padding:20px; margin:0 0 20px; }
.meal-form-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px; }
.meal-form-head h3 { margin:0 0 4px; color:var(--text); font-family:'Cormorant Garamond',serif; font-size:1.45rem; }
.meal-form-head p { margin:0; color:var(--muted); font-size:.82rem; }
.meal-form-head button { width:34px; height:34px; border-radius:50%; border:1px solid rgba(231,76,60,.3); background:rgba(231,76,60,.12); color:#e74c3c; font-size:1.3rem; cursor:pointer; }
.meal-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
.meal-form-grid label span { display:block; color:var(--muted); font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
.meal-form-grid input { width:100%; background:rgba(91,62,150,.08); color:var(--text); border:1.5px solid rgba(91,62,150,.25); border-radius:12px; padding:11px 13px; outline:none; }
.meal-food-picker { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:10px; max-height:330px; overflow:auto; padding:2px; }
.meal-pick { display:grid; grid-template-columns:auto 1fr 72px auto; gap:9px; align-items:center; padding:10px; border-radius:12px; border:1px solid rgba(91,62,150,.16); background:rgba(91,62,150,.06); cursor:pointer; }
.meal-pick:has(input[type="checkbox"]:checked) { border-color:rgba(58,134,196,.55); background:rgba(58,134,196,.12); }
.meal-pick strong { color:var(--text); display:block; font-size:.86rem; }
.meal-pick small { color:var(--muted); display:block; font-size:.72rem; margin-top:2px; }
.meal-qte { background:rgba(0,0,0,.12); color:var(--text); border:1px solid rgba(91,62,150,.2); border-radius:9px; padding:7px; width:72px; }
.meal-qte:disabled { opacity:.35; }
.meal-pick em { color:var(--muted); font-style:normal; font-size:.78rem; }
.meal-form-actions { display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:16px; }
.meal-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:18px; }
.meal-card { background:var(--card-bg); border:1px solid rgba(91,62,150,.18); border-radius:16px; padding:20px; backdrop-filter:blur(12px); transition:all .25s; }
.meal-card:hover { transform:translateY(-4px); border-color:rgba(91,62,150,.45); box-shadow:0 14px 34px rgba(0,0,0,.2); }
.meal-card-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:16px; }
.meal-card h3 { margin:4px 0 0; font-family:'Cormorant Garamond',serif; font-size:1.35rem; color:var(--text); }
.meal-date { color:var(--muted); font-size:.78rem; }
.meal-eco { width:58px; height:58px; border:2px solid currentColor; border-radius:50%; display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0; }
.meal-eco strong { font-size:1rem; line-height:1; }
.meal-eco span { font-size:.65rem; opacity:.75; }
.meal-macros { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; margin-bottom:14px; }
.meal-macros span { color:var(--muted); background:rgba(91,62,150,.08); border-radius:10px; padding:8px 10px; font-size:.82rem; }
.meal-foods { display:flex; flex-wrap:wrap; gap:7px; min-height:34px; }
.meal-food { display:inline-flex; gap:5px; align-items:center; padding:5px 10px; border-radius:999px; background:rgba(58,134,196,.12); color:var(--text); font-size:.78rem; }
.meal-food small { opacity:.68; }
.meal-food.muted { color:var(--muted); background:rgba(255,255,255,.04); }
.meal-card-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:16px; padding-top:14px; border-top:1px solid rgba(91,62,150,.12); }
.meal-card-actions a, .meal-card-actions button { color:var(--blue); font-weight:700; font-size:.82rem; text-decoration:none; border:0; background:transparent; cursor:pointer; font-family:inherit; }
.meal-card-actions a:last-child { color:#e74c3c; }
.meal-empty { text-align:center; padding:54px 20px; color:var(--muted); background:var(--card-bg); border:1px solid rgba(91,62,150,.18); border-radius:16px; }
.meal-empty div { font-size:3rem; margin-bottom:12px; }
.meal-empty h3 { color:var(--text); margin-bottom:8px; }
.meal-empty.compact { margin-top:18px; padding:34px 20px; }
@media(max-width:900px){ .meal-stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .meal-stats-grid,.meal-list,.meal-form-grid{grid-template-columns:1fr;} .meal-macros{grid-template-columns:1fr;} }
</style>

<script>
var DASHBOARD_MEALS = <?= json_encode($mealPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function openMealForm(id) {
  var panel = document.getElementById('mealFormPanel');
  var action = document.getElementById('mealFormAction');
  var mealId = document.getElementById('mealFormId');
  var name = document.getElementById('mealNameInput');
  var date = document.getElementById('mealDateInput');
  var title = document.getElementById('mealFormTitle');
  if (!panel) return;

  resetMealForm();
  if (id && DASHBOARD_MEALS[id]) {
    var meal = DASHBOARD_MEALS[id];
    title.textContent = 'Modifier le repas';
    action.value = 'update';
    mealId.value = meal.id_repas;
    name.value = meal.nom_repas || '';
    date.value = meal.date_repas || '';
    meal.aliments.forEach(function(aliment) {
      var pick = panel.querySelector('.meal-pick[data-aliment-id="' + aliment.id_aliment + '"]');
      if (!pick) return;
      var checkbox = pick.querySelector('input[type="checkbox"]');
      var qte = pick.querySelector('.meal-qte');
      checkbox.checked = true;
      qte.disabled = false;
      qte.value = aliment.quantite || 100;
    });
  } else {
    title.textContent = 'Nouveau repas';
    action.value = 'create';
    mealId.value = '';
    var now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    date.value = now.toISOString().slice(0, 16);
  }

  panel.style.display = 'block';
  panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeMealForm() {
  var panel = document.getElementById('mealFormPanel');
  if (panel) panel.style.display = 'none';
}

function resetMealForm() {
  var panel = document.getElementById('mealFormPanel');
  if (!panel) return;
  panel.reset();
  panel.querySelectorAll('.meal-qte').forEach(function(input) {
    input.disabled = true;
    input.value = 100;
  });
}

function syncMealQuantity(checkbox) {
  var pick = checkbox.closest('.meal-pick');
  var qte = pick ? pick.querySelector('.meal-qte') : null;
  if (!qte) return;
  qte.disabled = !checkbox.checked;
  if (checkbox.checked && (!qte.value || Number(qte.value) <= 0)) qte.value = 100;
}

function fillSampleMeal() {
  openMealForm();
  document.getElementById('mealNameInput').value = 'Sample métier équilibré';
  var picks = Array.from(document.querySelectorAll('#mealFormPanel .meal-pick')).slice(0, 3);
  picks.forEach(function(pick, index) {
    var checkbox = pick.querySelector('input[type="checkbox"]');
    var qte = pick.querySelector('.meal-qte');
    checkbox.checked = true;
    qte.disabled = false;
    qte.value = [150, 120, 80][index] || 100;
  });
}

function filterDashboardMeals() {
  var input = document.getElementById('mealSearch');
  var q = input ? input.value.toLowerCase().trim() : '';
  var cards = document.querySelectorAll('#dashboardMealsList .meal-card');
  var visible = 0;
  cards.forEach(function(card) {
    var show = !q || card.dataset.name.indexOf(q) !== -1;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  var empty = document.getElementById('mealNoResult');
  if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
}

function sortDashboardMeals() {
  var list = document.getElementById('dashboardMealsList');
  var sort = document.getElementById('mealSort');
  if (!list || !sort) return;
  var cards = Array.from(list.querySelectorAll('.meal-card'));
  cards.sort(function(a, b) {
    if (sort.value === 'date_asc') return a.dataset.date.localeCompare(b.dataset.date);
    if (sort.value === 'cal_desc') return Number(b.dataset.cal) - Number(a.dataset.cal);
    if (sort.value === 'eco_desc') return Number(b.dataset.eco) - Number(a.dataset.eco);
    return b.dataset.date.localeCompare(a.dataset.date);
  });
  cards.forEach(function(card) { list.appendChild(card); });
  filterDashboardMeals();
}

sortDashboardMeals();
</script>
