<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // ── Suppression AJAX ──────────────────────────────────────────────────────
    if ($isAjax && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $demandeC = new DemandeplanningController();
        $demandeC->deleteDemande((int)$_GET['id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        $userId = 1;
    } else {
        $userId = $_SESSION['user_id'];
    }

    $demandeC = new DemandeplanningController();
    $demandes = $demandeC->listDemandesByUser($userId);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($demandes);
        exit;
    }
} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    } else {
        die("Erreur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>GaiaLumen – Mes Demandes de Planning</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/dashboard.css"/>
  <style>
    .list-page {
      min-height: 100vh;
      padding: 100px 20px 60px;
    }
    .list-container {
      max-width: 900px;
      margin: 0 auto;
    }
    .page-header {
      text-align: center;
      margin-bottom: 48px;
    }
    .page-header .icon { font-size: 3.5rem; margin-bottom: 16px; display: block; }
    .page-header h1 { font-size: 2.5rem; font-weight: 700; color: var(--text); margin-bottom: 12px; }
    .page-header p { color: var(--muted); font-size: 1rem; }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }
    .stat-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(91, 62, 150, .25);
      border-radius: var(--radius);
      padding: 24px;
      text-align: center;
      transition: transform .3s, box-shadow .3s;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(91, 62, 150, .3);
    }
    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--violet);
      margin-bottom: 8px;
    }
    .stat-label {
      color: var(--muted);
      font-size: .85rem;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .actions-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      gap: 16px;
      flex-wrap: wrap;
    }
    .search-box {
      flex: 1;
      min-width: 200px;
      position: relative;
    }
    .search-input {
      width: 100%;
      background: rgba(91, 62, 150, .08);
      border: 1.5px solid rgba(91, 62, 150, .25);
      border-radius: 12px;
      padding: 11px 16px 11px 44px;
      color: var(--text);
      font-size: .95rem;
      outline: none;
      transition: border-color .25s;
      box-sizing: border-box;
    }
    .search-input:focus { border-color: var(--violet); }
    .search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      pointer-events: none;
    }
    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 22px;
      background: linear-gradient(135deg, var(--violet), var(--blue));
      border: none;
      border-radius: 12px;
      color: #fff;
      font-weight: 600;
      font-size: .92rem;
      text-decoration: none;
      cursor: pointer;
      transition: transform .2s, box-shadow .3s;
      white-space: nowrap;
    }
    .btn-add:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(91, 62, 150, .45);
    }

    .demandes-grid { display: grid; gap: 20px; }

    .demande-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(91, 62, 150, .25);
      border-radius: var(--radius);
      padding: 28px;
      transition: transform .3s, box-shadow .3s;
      position: relative;
      overflow: hidden;
    }
    .demande-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--violet), var(--blue));
    }
    .demande-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(91, 62, 150, .3);
    }
    .demande-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 20px;
    }
    .demande-id {
      font-size: .82rem;
      color: var(--muted);
      font-weight: 600;
      letter-spacing: .05em;
    }
    .demande-date { font-size: .82rem; color: var(--muted); }

    .demande-body {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 20px;
    }
    .demande-info { display: flex; flex-direction: column; gap: 4px; }
    .info-label {
      font-size: .78rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .info-value {
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--text);
    }
    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-left: 5px;
    }
    .badge-quotidien    { background: rgba(52, 152, 219, .2); color: #3498db; }
    .badge-hebdomadaire { background: rgba(155, 89, 182, .2); color: #9b59b6; }
    .badge-jours        { background: rgba(46, 204, 113, .2); color: #2ecc71; }
    .badge-semaines     { background: rgba(241, 196, 15, .2);  color: #f1c40f; }

    .demande-actions {
      display: flex;
      gap: 12px;
      padding-top: 16px;
      border-top: 1px solid rgba(91, 62, 150, .15);
    }
    .btn-action {
      flex: 1;
      padding: 10px;
      border: 1.5px solid rgba(91, 62, 150, .25);
      border-radius: 10px;
      background: transparent;
      color: var(--text);
      font-size: .88rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .25s;
      text-decoration: none;
      text-align: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .btn-action:hover { border-color: var(--violet); background: rgba(91, 62, 150, .1); }
    .btn-delete { border-color: rgba(231, 76, 60, .3); color: #e74c3c; }
    .btn-delete:hover { border-color: #e74c3c; background: rgba(231, 76, 60, .1); }
    .btn-show   { border-color: rgba(46, 204, 113, .3); color: #2ecc71; }
    .btn-show:hover { border-color: #2ecc71; background: rgba(46, 204, 113, .1); }

    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: var(--muted);
    }
    .empty-state .icon { font-size: 4rem; margin-bottom: 20px; opacity: .5; }
    .empty-state h3 { font-size: 1.5rem; margin-bottom: 12px; color: var(--text); }
    .empty-state p { margin-bottom: 24px; }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      text-decoration: none;
      font-size: .9rem;
      margin-bottom: 32px;
      transition: color .2s;
    }
    .btn-back:hover { color: var(--blue); }

    /* ── Modal Show ─────────────────────────────────────────────────────────── */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.65);
      backdrop-filter: blur(6px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-overlay.active { display: flex; }

    .modal {
      background: var(--card-bg);
      border: 1px solid rgba(91, 62, 150, .35);
      border-radius: 20px;
      width: 100%;
      max-width: 580px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      animation: modalIn .25s ease;
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(20px) scale(.97); }
      to   { opacity: 1; transform: translateY(0)   scale(1); }
    }
    .modal-bar {
      height: 4px;
      border-radius: 20px 20px 0 0;
      background: linear-gradient(90deg, var(--violet), var(--blue));
    }
    .modal-inner { padding: 32px; }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 28px;
    }
    .modal-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--text);
    }
    .modal-subtitle { font-size: .82rem; color: var(--muted); margin-top: 4px; }
    .modal-close {
      background: rgba(91,62,150,.15);
      border: none;
      color: var(--text);
      width: 36px; height: 36px;
      border-radius: 50%;
      font-size: 1.1rem;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s;
      flex-shrink: 0;
    }
    .modal-close:hover { background: rgba(231,76,60,.2); color: #e74c3c; }

    .modal-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .modal-field {
      background: rgba(91,62,150,.07);
      border: 1px solid rgba(91,62,150,.18);
      border-radius: 14px;
      padding: 18px 20px;
    }
    .modal-field.full { grid-column: 1 / -1; }
    .modal-field-label {
      font-size: .75rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 8px;
    }
    .modal-field-value {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text);
      word-break: break-word;
    }
    .modal-field-value .badge { margin-left: 0; margin-top: 6px; display: inline-block; }

    .modal-footer {
      display: flex;
      gap: 12px;
      margin-top: 28px;
      padding-top: 20px;
      border-top: 1px solid rgba(91,62,150,.15);
    }

    /* ── Toast notification ─────────────────────────────────────────────────── */
    .toast {
      position: fixed;
      bottom: 28px;
      right: 28px;
      padding: 14px 22px;
      border-radius: 12px;
      font-weight: 600;
      font-size: .9rem;
      color: #fff;
      z-index: 99999;
      opacity: 0;
      transform: translateY(12px);
      transition: opacity .3s, transform .3s;
      pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast-success { background: linear-gradient(135deg,#2ecc71,#27ae60); }
    .toast-error   { background: linear-gradient(135deg,#e74c3c,#c0392b); }

    @media (max-width: 600px) {
      .stats-grid { grid-template-columns: 1fr; }
      .demande-body { grid-template-columns: 1fr 1fr; }
      .modal-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div id="cursor"></div>
<div id="cursor-trail"></div>

<!-- ── Modal Détails ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal" id="modal">
    <div class="modal-bar"></div>
    <div class="modal-inner">
      <div class="modal-header">
        <div>
          <div class="modal-title" id="modalTitle">Détails de la demande</div>
          <div class="modal-subtitle" id="modalSubtitle"></div>
        </div>
        <button class="modal-close" onclick="closeModalDirect()">✕</button>
      </div>
      <div class="modal-grid" id="modalBody"></div>
      <div class="modal-footer">
        <a id="modalEditBtn" href="#" class="btn-action" style="flex:1">✏️ Modifier</a>
        <button class="btn-action btn-delete" style="flex:1" id="modalDeleteBtn">🗑️ Supprimer</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Toast ─────────────────────────────────────────────────────────────── -->
<div class="toast" id="toast"></div>

<nav id="navbar">
  <a href="../dashboard.html" class="nav-logo">
    <svg viewBox="0 0 60 60" fill="none">
      <circle cx="30" cy="30" r="28" stroke="url(#ag)" stroke-width="1.5" opacity=".6"/>
      <defs>
        <radialGradient id="ag" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#3A86C4"/>
          <stop offset="100%" stop-color="#5B3E96"/>
        </radialGradient>
        <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#1F3D2B"/>
          <stop offset="100%" stop-color="#3A86C4"/>
        </linearGradient>
      </defs>
      <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="url(#lg)"/>
      <path d="M30 14 L30 46" stroke="rgba(242,232,207,.5)" stroke-width="1" stroke-linecap="round"/>
    </svg>
    <span class="nav-logo-text">GaiaLumen</span>
  </a>
  <div class="nav-actions">
    <button id="theme-toggle" title="Changer le thème">🌙 Sombre</button>
    <a href="../dashboard.html" class="btn-logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
        <path d="M19 12H5M12 5l-7 7 7 7"/>
      </svg>
      Retour
    </a>
  </div>
</nav>

<div class="list-page">
  <div class="list-container">

    <a href="../dashboard.html" class="btn-back">← Retour au dashboard</a>

    <div class="page-header">
      <span class="icon">📅</span>
      <h1>Mes Demandes de Planning</h1>
      <p>Gérez vos plannings nutritionnels personnalisés</p>
    </div>

    <?php
    $totalDemandes = count($demandes);
    $quotidien     = count(array_filter($demandes, fn($d) => $d['type_budget'] === 'quotidien'));
    $hebdomadaire  = count(array_filter($demandes, fn($d) => $d['type_budget'] === 'hebdomadaire'));
    ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value" id="statTotal"><?= $totalDemandes ?></div>
        <div class="stat-label">Total demandes</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $quotidien ?></div>
        <div class="stat-label">Budget quotidien</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $hebdomadaire ?></div>
        <div class="stat-label">Budget hebdomadaire</div>
      </div>
    </div>

    <div class="actions-bar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" class="search-input" id="searchInput"
               placeholder="Rechercher dans mes demandes..."
               oninput="filterDemandes()"/>
      </div>
      <a href="addDemandeplanning.php" class="btn-add">➕ Nouvelle demande</a>
    </div>

    <div class="demandes-grid" id="demandesGrid">
      <?php if (empty($demandes)): ?>
        <div class="empty-state">
          <div class="icon">📭</div>
          <h3>Aucune demande pour le moment</h3>
          <p>Créez votre première demande de planning nutritionnel</p>
          <a href="addDemandeplanning.php" class="btn-add">➕ Créer ma première demande</a>
        </div>
      <?php else: ?>
        <?php foreach ($demandes as $d): ?>
        <div class="demande-card"
             id="card-<?= $d['id'] ?>"
             data-search="<?= htmlspecialchars(strtolower(json_encode($d))) ?>"
             data-demande='<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>'>

          <div class="demande-header">
            <div class="demande-id">DEMANDE #<?= htmlspecialchars($d['id']) ?></div>
            <div class="demande-date">
              <?= $d['date_demande'] ? date('d/m/Y', strtotime($d['date_demande'])) : '—' ?>
            </div>
          </div>

          <div class="demande-body">
            <div class="demande-info">
              <div class="info-label">🔥 Calories</div>
              <div class="info-value"><?= htmlspecialchars($d['calories']) ?> kcal</div>
            </div>
            <div class="demande-info">
              <div class="info-label">💰 Budget</div>
              <div class="info-value">
                <?= number_format((float)$d['budget'], 2) ?> €
                <span class="badge badge-<?= htmlspecialchars($d['type_budget']) ?>">
                  <?= htmlspecialchars($d['type_budget']) ?>
                </span>
              </div>
            </div>
            <div class="demande-info">
              <div class="info-label">⏱️ Durée</div>
              <div class="info-value">
                <?= htmlspecialchars($d['duree']) ?>
                <span class="badge badge-<?= htmlspecialchars($d['type_duree']) ?>">
                  <?= htmlspecialchars($d['type_duree']) ?>
                </span>
              </div>
            </div>
          </div>

          <div class="demande-actions">
            <button class="btn-action btn-show"
                    onclick="showDemande(<?= $d['id'] ?>)">
              👁️ Voir
            </button>
            <a href="updateDemandeplanning.php?id=<?= $d['id'] ?>" class="btn-action">
              ✏️ Modifier
            </a>
            <button class="btn-action btn-delete"
                    onclick="deleteDemande(<?= $d['id'] ?>, this)">
              🗑️ Supprimer
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
/* ── Thème ──────────────────────────────────────────────────────────────── */
const themeBtn = document.getElementById('theme-toggle');
const html = document.documentElement;
const saved = localStorage.getItem('theme') || 'dark';
html.setAttribute('data-theme', saved);
themeBtn.textContent = saved === 'dark' ? '🌙 Sombre' : '☀️ Clair';
themeBtn.addEventListener('click', () => {
  const t = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', t);
  localStorage.setItem('theme', t);
  themeBtn.textContent = t === 'dark' ? '🌙 Sombre' : '☀️ Clair';
});

/* ── Recherche ──────────────────────────────────────────────────────────── */
function filterDemandes() {
  const val = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.demande-card').forEach(card => {
    card.style.display = card.getAttribute('data-search').includes(val) ? '' : 'none';
  });
}

/* ── Toast ──────────────────────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast toast-${type} show`;
  setTimeout(() => { t.classList.remove('show'); }, 3200);
}

/* ── Suppression AJAX (sans rechargement de page) ───────────────────────── */
function deleteDemande(id, btn) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')) return;

  // Désactiver le bouton pendant la requête
  btn.disabled = true;
  btn.textContent = '⏳';

  fetch(`?action=delete&id=${id}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Supprimer la carte avec une animation
      const card = document.getElementById(`card-${id}`);
      card.style.transition = 'opacity .3s, transform .3s';
      card.style.opacity = '0';
      card.style.transform = 'scale(.95)';
      setTimeout(() => {
        card.remove();
        // Mettre à jour le compteur
        const statEl = document.getElementById('statTotal');
        if (statEl) statEl.textContent = parseInt(statEl.textContent) - 1;
        // Si modal ouvert sur cette demande, le fermer
        if (currentModalId === id) closeModalDirect();
      }, 300);
      showToast('✅ Demande supprimée avec succès');
    } else {
      showToast('❌ Erreur : ' + (data.error || 'Suppression échouée'), 'error');
      btn.disabled = false;
      btn.innerHTML = '🗑️ Supprimer';
    }
  })
  .catch(() => {
    showToast('❌ Erreur réseau, réessayez.', 'error');
    btn.disabled = false;
    btn.innerHTML = '🗑️ Supprimer';
  });
}

/* ── Modal Show ─────────────────────────────────────────────────────────── */
let currentModalId = null;

function showDemande(id) {
  const card = document.getElementById(`card-${id}`);
  if (!card) return;

  const d = JSON.parse(card.getAttribute('data-demande'));
  currentModalId = id;

  // Titre & sous-titre
  document.getElementById('modalTitle').textContent = `Demande #${d.id}`;
  document.getElementById('modalSubtitle').textContent = d.date_demande
    ? 'Créée le ' + formatDate(d.date_demande)
    : '';

  // Contenu
  const fields = [
    { label: '🔥 Calories',       value: `${d.calories} kcal` },
    { label: '💰 Budget',         value: `${parseFloat(d.budget).toFixed(2)} €`, badge: d.type_budget },
    { label: '⏱️ Durée',          value: d.duree,              badge: d.type_duree },
    { label: '📋 Type budget',    value: d.type_budget },
    { label: '📏 Type durée',     value: d.type_duree },
    { label: '📅 Date demande',   value: d.date_demande ? formatDate(d.date_demande) : '—', full: true },
  ];

  // Ajouter tous les autres champs présents dans l'objet
  const known = ['id','calories','budget','type_budget','duree','type_duree','date_demande'];
  Object.entries(d).forEach(([k, v]) => {
    if (!known.includes(k) && v !== null && v !== '') {
      fields.push({ label: '📌 ' + k.replace(/_/g,' '), value: String(v) });
    }
  });

  document.getElementById('modalBody').innerHTML = fields.map(f => `
    <div class="modal-field${f.full ? ' full' : ''}">
      <div class="modal-field-label">${f.label}</div>
      <div class="modal-field-value">
        ${f.value}
        ${f.badge ? `<span class="badge badge-${f.badge}">${f.badge}</span>` : ''}
      </div>
    </div>
  `).join('');

  // Boutons du footer
  document.getElementById('modalEditBtn').href = `updateDemandeplanning.php?id=${d.id}`;
  document.getElementById('modalDeleteBtn').onclick = () => {
    closeModalDirect();
    // Simuler un clic sur le bouton supprimer de la carte
    const cardBtn = card.querySelector('.btn-delete');
    if (cardBtn) cardBtn.click();
  };

  document.getElementById('modalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal(e) {
  // Fermer uniquement si clic sur l'overlay (pas sur le modal lui-même)
  if (e.target === document.getElementById('modalOverlay')) closeModalDirect();
}

function closeModalDirect() {
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = '';
  currentModalId = null;
}

// Fermer avec Echap
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModalDirect();
});

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('fr-FR');
}

/* ── Curseur ────────────────────────────────────────────────────────────── */
(function(){
  const cur = document.getElementById('cursor');
  const trail = document.getElementById('cursor-trail');
  if (!cur || !trail) return;
  let mx = 0, my = 0, tx = 0, ty = 0;
  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cur.style.left = mx + 'px'; cur.style.top = my + 'px';
  });
  (function loop(){
    tx += (mx - tx) * .12; ty += (my - ty) * .12;
    trail.style.left = tx + 'px'; trail.style.top = ty + 'px';
    requestAnimationFrame(loop);
  })();
  document.querySelectorAll('a, button, .search-input').forEach(el => {
    el.addEventListener('mouseenter', () => cur.classList.add('hover'));
    el.addEventListener('mouseleave', () => cur.classList.remove('hover'));
  });
})();
</script>

</body>
</html>