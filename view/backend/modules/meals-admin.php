<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/repascontroller.php';
require_once __DIR__ . '/../../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../../helpers/repas_helpers.php';

$users = Config::getConnexion()
    ->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateurs ORDER BY prenom ASC, nom ASC")
    ->fetchAll(PDO::FETCH_ASSOC);
$aliments = aliment_getAll($pdo);

$stmt = Config::getConnexion()->query(
    "SELECT r.*, u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email,
            COUNT(ra.id_aliment) AS nb_aliments
     FROM repas r
     LEFT JOIN utilisateurs u ON u.id_utilisateur = r.id_utilisateur
     LEFT JOIN repas_aliments ra ON ra.id_repas = r.id_repas
     GROUP BY r.id_repas
     ORDER BY r.date_repas DESC"
);
$repas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$details = [];
$totalCalories = 0;
$totalCo2 = 0;
$weekCount = 0;
$payload = [];

foreach ($repas as $meal) {
    $id = (int) $meal['id_repas'];
    $mealAliments = repas_getAlimentsOfRepas($pdo, $id);
    $totaux = repas_getTotauxNutritionnels($pdo, $id);
    $score = scoreEcologique($totaux);
    $details[$id] = ['aliments' => $mealAliments, 'totaux' => $totaux, 'score' => $score];
    $totalCalories += (float) ($totaux['total_calories'] ?? 0);
    $totalCo2 += (float) ($totaux['total_co2'] ?? 0);
    if (strtotime($meal['date_repas']) >= strtotime('-7 days')) {
        $weekCount++;
    }
    $payload[$id] = [
        'id_repas' => $id,
        'id_utilisateur' => (int) $meal['id_utilisateur'],
        'nom_repas' => $meal['nom_repas'],
        'date_repas' => date('Y-m-d\TH:i', strtotime($meal['date_repas'])),
        'aliments' => array_map(static fn($aliment) => [
            'id_aliment' => (int) $aliment['id_aliment'],
            'quantite' => (float) $aliment['quantite'],
        ], $mealAliments),
    ];
}

$avgCalories = count($repas) ? round($totalCalories / count($repas)) : 0;
$avgEco = count($repas) ? round(array_sum(array_column($details, 'score')) / count($repas)) : 0;
$success = $_GET['success'] ?? '';
$error = urldecode($_GET['error'] ?? '');
?>

<section class="content-section" id="meals">
  <div class="section-header admin-meals-head">
    <div>
      <h2 class="module-title">🍽️ Gestion Admin des Repas</h2>
      <p>Supervisez les repas utilisateurs, leurs compositions et leurs indicateurs nutritionnels.</p>
    </div>
    <div class="admin-meals-actions">
      <button class="adm-meal-btn primary" type="button" onclick="openAdminMealForm()">➕ Créer</button>
      <a class="adm-meal-btn ghost" href="bo_repaslist.php">Vue détaillée</a>
      <a class="adm-meal-btn ghost" href="bo_alimentlist.php">Aliments</a>
    </div>
  </div>

  <?php if ($success): ?><div class="adm-meal-alert success">Opération réussie: <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="adm-meal-alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="adm-meal-stats">
    <div><span>🍱</span><strong><?= count($repas) ?></strong><small>Total repas</small></div>
    <div><span>📆</span><strong><?= $weekCount ?></strong><small>Cette semaine</small></div>
    <div><span>🔥</span><strong><?= $avgCalories ?></strong><small>Calories moy.</small></div>
    <div><span>🌿</span><strong><?= $avgEco ?>/100</strong><small>Score éco moy.</small></div>
  </div>

  <form class="adm-meal-form" id="adminMealForm" action="../../controller/repascontroller.php" method="POST" style="display:none;">
    <input type="hidden" name="action" id="adminMealAction" value="create">
    <input type="hidden" name="id_repas" id="adminMealId">
    <input type="hidden" name="redirect_to" value="../view/backend/admin.html?module=meals">

    <div class="adm-meal-form-head">
      <div>
        <h3 id="adminMealFormTitle">Créer un repas</h3>
        <p>CRUD admin avec validation métier et composition aliment/quantité.</p>
      </div>
      <button type="button" onclick="closeAdminMealForm()">×</button>
    </div>

    <div class="adm-meal-form-grid">
      <label><span>Utilisateur</span>
        <select name="id_utilisateur" id="adminMealUser" required>
          <?php foreach ($users as $user): ?>
            <option value="<?= (int) $user['id_utilisateur'] ?>">
              #<?= (int) $user['id_utilisateur'] ?> · <?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?: $user['email']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span>Nom du repas</span><input type="text" name="nom_repas" id="adminMealName" minlength="2" maxlength="150" required></label>
      <label><span>Date et heure</span><input type="datetime-local" name="date_repas" id="adminMealDate" required></label>
    </div>

    <?php if (empty($aliments)): ?>
      <div class="adm-meal-alert error">Aucun aliment disponible. Créez des aliments avant de créer des repas.</div>
    <?php else: ?>
      <div class="adm-food-picker">
        <?php foreach ($aliments as $aliment): ?>
          <label class="adm-food-pick" data-aliment-id="<?= (int) $aliment['id_aliment'] ?>">
            <input type="checkbox" name="aliments[]" value="<?= (int) $aliment['id_aliment'] ?>" onchange="syncAdminMealQuantity(this)">
            <span><strong><?= htmlspecialchars($aliment['nom']) ?></strong><small><?= htmlspecialchars($aliment['type']) ?> · <?= round((float) $aliment['calories']) ?> kcal</small></span>
            <input class="adm-qte" type="number" name="quantites[<?= (int) $aliment['id_aliment'] ?>]" min="1" max="2000" value="100" disabled>
            <em>g</em>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="adm-meal-form-actions">
      <button class="adm-meal-btn ghost" type="button" onclick="fillAdminMealSample()">Sample métier</button>
      <button class="adm-meal-btn ghost" type="button" onclick="closeAdminMealForm()">Annuler</button>
      <button class="adm-meal-btn primary" type="submit" <?= empty($aliments) || empty($users) ? 'disabled' : '' ?>>Enregistrer</button>
    </div>
  </form>

  <div class="adm-meal-toolbar">
    <input id="adminMealSearch" type="search" placeholder="Rechercher par repas ou utilisateur..." oninput="filterAdminMeals()">
    <select id="adminMealFilter" onchange="filterAdminMeals()">
      <option value="">Tous les scores</option>
      <option value="good">Score ≥ 60</option>
      <option value="risk">Score < 60</option>
    </select>
  </div>

  <div class="adm-meal-table">
    <table>
      <thead>
        <tr>
          <th>Utilisateur</th>
          <th>Repas</th>
          <th>Composition</th>
          <th>Calories</th>
          <th>Score éco</th>
          <th>CO₂</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="adminMealsBody">
        <?php foreach ($repas as $meal):
          $id = (int) $meal['id_repas'];
          $totaux = $details[$id]['totaux'];
          $score = $details[$id]['score'];
          $label = labelEcologique($score);
          $userLabel = trim(($meal['user_prenom'] ?? '') . ' ' . ($meal['user_nom'] ?? '')) ?: ($meal['user_email'] ?? 'Utilisateur #' . $meal['id_utilisateur']);
        ?>
        <tr class="adm-meal-row" data-search="<?= strtolower(htmlspecialchars($meal['nom_repas'] . ' ' . $userLabel)) ?>" data-score="<?= $score ?>">
          <td><strong><?= htmlspecialchars($userLabel) ?></strong><small>#<?= (int) $meal['id_utilisateur'] ?></small></td>
          <td><?= htmlspecialchars($meal['nom_repas']) ?></td>
          <td>
            <div class="adm-food-tags">
              <?php foreach ($details[$id]['aliments'] as $aliment): ?>
                <span><?= htmlspecialchars($aliment['nom']) ?> <small><?= (float) $aliment['quantite'] ?>g</small></span>
              <?php endforeach; ?>
            </div>
          </td>
          <td><?= round((float) ($totaux['total_calories'] ?? 0)) ?> kcal</td>
          <td><span class="adm-score" style="color:<?= $label['color'] ?>;border-color:<?= $label['color'] ?>;"><?= $score ?>/100</span></td>
          <td><?= round((float) ($totaux['total_co2'] ?? 0), 2) ?> kg</td>
          <td><?= date('d/m/Y H:i', strtotime($meal['date_repas'])) ?></td>
          <td>
            <div class="adm-row-actions">
              <button type="button" onclick="openAdminMealForm(<?= $id ?>)">Modifier</button>
              <a href="../../controller/repascontroller.php?action=delete&id=<?= $id ?>&redirect_to=../view/backend/admin.html%3Fmodule=meals" onclick="return confirm('Supprimer ce repas ?')">Supprimer</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($repas)): ?>
      <div class="adm-empty">Aucun repas enregistré. Utilisez le bouton Créer ou Sample métier.</div>
    <?php endif; ?>
    <div class="adm-empty" id="adminMealsEmpty" style="display:none;">Aucun repas ne correspond aux filtres.</div>
  </div>
</section>

<style>
.module-title{background:linear-gradient(135deg,var(--text),var(--blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.admin-meals-head{display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap;align-items:flex-start;}
.admin-meals-actions,.adm-meal-form-actions{display:flex;gap:10px;flex-wrap:wrap;}
.adm-meal-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 16px;border-radius:10px;text-decoration:none;font-weight:800;font-size:.84rem;border:0;cursor:pointer;font-family:inherit;}
.adm-meal-btn.primary{background:linear-gradient(135deg,var(--violet),var(--blue));color:#fff;box-shadow:0 8px 24px rgba(91,62,150,.28);}
.adm-meal-btn.ghost{background:rgba(91,62,150,.1);border:1px solid rgba(91,62,150,.28);color:var(--text);}
.adm-meal-btn:disabled{opacity:.45;cursor:not-allowed;}
.adm-meal-alert{padding:12px 16px;border-radius:10px;margin:14px 0;font-weight:800;font-size:.84rem;}
.adm-meal-alert.success{color:#2ecc71;background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.25);}
.adm-meal-alert.error{color:#e74c3c;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.25);}
.adm-meal-stats{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:14px;margin:24px 0;}
.adm-meal-stats>div{background:var(--card-bg);border:1px solid rgba(91,62,150,.18);border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:6px;}
.adm-meal-stats span{font-size:1.3rem}.adm-meal-stats strong{font-size:1.8rem;color:var(--text);line-height:1}.adm-meal-stats small{color:var(--muted);text-transform:uppercase;font-size:.72rem;letter-spacing:.06em;}
.adm-meal-form{background:var(--card-bg);border:1px solid rgba(91,62,150,.22);border-radius:16px;padding:20px;margin-bottom:20px;}
.adm-meal-form-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;}
.adm-meal-form-head h3{margin:0 0 4px;color:var(--text);font-family:'Cormorant Garamond',serif;font-size:1.45rem;}
.adm-meal-form-head p{margin:0;color:var(--muted);font-size:.82rem;}
.adm-meal-form-head button{width:34px;height:34px;border-radius:50%;border:1px solid rgba(231,76,60,.3);background:rgba(231,76,60,.12);color:#e74c3c;font-size:1.3rem;cursor:pointer;}
.adm-meal-form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;}
.adm-meal-form-grid span{display:block;color:var(--muted);font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
.adm-meal-form-grid input,.adm-meal-form-grid select,.adm-meal-toolbar input,.adm-meal-toolbar select{width:100%;background:rgba(91,62,150,.08);color:var(--text);border:1.5px solid rgba(91,62,150,.25);border-radius:10px;padding:10px 12px;outline:none;}
.adm-food-picker{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;max-height:300px;overflow:auto;padding:2px;}
.adm-food-pick{display:grid;grid-template-columns:auto 1fr 72px auto;gap:9px;align-items:center;padding:10px;border-radius:12px;border:1px solid rgba(91,62,150,.16);background:rgba(91,62,150,.06);cursor:pointer;}
.adm-food-pick:has(input[type="checkbox"]:checked){border-color:rgba(58,134,196,.55);background:rgba(58,134,196,.12);}
.adm-food-pick strong{color:var(--text);display:block;font-size:.84rem}.adm-food-pick small{color:var(--muted);display:block;font-size:.7rem}.adm-food-pick em{color:var(--muted);font-style:normal;font-size:.76rem;}
.adm-qte{background:rgba(0,0,0,.12);color:var(--text);border:1px solid rgba(91,62,150,.2);border-radius:9px;padding:7px;width:72px}.adm-qte:disabled{opacity:.35;}
.adm-meal-form-actions{justify-content:flex-end;margin-top:16px;}
.adm-meal-toolbar{display:flex;gap:12px;flex-wrap:wrap;margin:18px 0;}
.adm-meal-toolbar input{flex:1;min-width:260px}.adm-meal-toolbar select{width:auto;min-width:170px;}
.adm-meal-table{background:var(--card-bg);border:1px solid rgba(91,62,150,.16);border-radius:16px;overflow:auto;}
.adm-meal-table table{width:100%;border-collapse:collapse;min-width:980px;}
.adm-meal-table th,.adm-meal-table td{padding:13px 14px;text-align:left;border-bottom:1px solid rgba(91,62,150,.08);color:var(--text);vertical-align:top;font-size:.86rem;}
.adm-meal-table th{color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-size:.72rem;background:rgba(91,62,150,.1);}
.adm-meal-table td small{display:block;color:var(--muted);margin-top:3px;}
.adm-food-tags{display:flex;gap:6px;flex-wrap:wrap;max-width:320px}.adm-food-tags span{padding:4px 8px;border-radius:999px;background:rgba(58,134,196,.12);font-size:.74rem}.adm-food-tags small{display:inline;color:inherit;opacity:.65;}
.adm-score{display:inline-flex;border:1px solid currentColor;border-radius:999px;padding:4px 9px;font-weight:900;}
.adm-row-actions{display:flex;gap:8px;flex-wrap:wrap}.adm-row-actions button,.adm-row-actions a{border:0;background:transparent;color:var(--blue);font-weight:900;cursor:pointer;text-decoration:none;font-family:inherit}.adm-row-actions a{color:#e74c3c;}
.adm-empty{text-align:center;color:var(--muted);padding:34px;}
@media(max-width:900px){.adm-meal-stats,.adm-meal-form-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:560px){.adm-meal-stats,.adm-meal-form-grid{grid-template-columns:1fr;}}
</style>

<script>
var ADMIN_MEALS = <?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function openAdminMealForm(id) {
  var form = document.getElementById('adminMealForm');
  if (!form) return;
  resetAdminMealForm();
  document.getElementById('adminMealDate').value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  document.getElementById('adminMealAction').value = 'create';
  document.getElementById('adminMealFormTitle').textContent = 'Créer un repas';

  if (id && ADMIN_MEALS[id]) {
    var meal = ADMIN_MEALS[id];
    document.getElementById('adminMealAction').value = 'update';
    document.getElementById('adminMealId').value = meal.id_repas;
    document.getElementById('adminMealUser').value = meal.id_utilisateur;
    document.getElementById('adminMealName').value = meal.nom_repas;
    document.getElementById('adminMealDate').value = meal.date_repas;
    document.getElementById('adminMealFormTitle').textContent = 'Modifier le repas #' + meal.id_repas;
    meal.aliments.forEach(function(aliment) {
      var pick = form.querySelector('.adm-food-pick[data-aliment-id="' + aliment.id_aliment + '"]');
      if (!pick) return;
      var checkbox = pick.querySelector('input[type="checkbox"]');
      var qte = pick.querySelector('.adm-qte');
      checkbox.checked = true;
      qte.disabled = false;
      qte.value = aliment.quantite || 100;
    });
  }

  form.style.display = 'block';
  form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeAdminMealForm() {
  var form = document.getElementById('adminMealForm');
  if (form) form.style.display = 'none';
}

function resetAdminMealForm() {
  var form = document.getElementById('adminMealForm');
  if (!form) return;
  form.reset();
  document.getElementById('adminMealId').value = '';
  form.querySelectorAll('.adm-qte').forEach(function(input) { input.disabled = true; input.value = 100; });
}

function syncAdminMealQuantity(checkbox) {
  var qte = checkbox.closest('.adm-food-pick').querySelector('.adm-qte');
  qte.disabled = !checkbox.checked;
  if (checkbox.checked && Number(qte.value) <= 0) qte.value = 100;
}

function fillAdminMealSample() {
  openAdminMealForm();
  document.getElementById('adminMealName').value = 'Sample admin équilibré';
  Array.from(document.querySelectorAll('#adminMealForm .adm-food-pick')).slice(0, 3).forEach(function(pick, index) {
    var checkbox = pick.querySelector('input[type="checkbox"]');
    var qte = pick.querySelector('.adm-qte');
    checkbox.checked = true;
    qte.disabled = false;
    qte.value = [150, 120, 80][index] || 100;
  });
}

function filterAdminMeals() {
  var q = (document.getElementById('adminMealSearch')?.value || '').toLowerCase().trim();
  var filter = document.getElementById('adminMealFilter')?.value || '';
  var visible = 0;
  document.querySelectorAll('#adminMealsBody .adm-meal-row').forEach(function(row) {
    var score = Number(row.dataset.score || 0);
    var okText = !q || row.dataset.search.indexOf(q) !== -1;
    var okScore = !filter || (filter === 'good' ? score >= 60 : score < 60);
    var show = okText && okScore;
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  var empty = document.getElementById('adminMealsEmpty');
  if (empty) empty.style.display = visible ? 'none' : 'block';
}
</script>
