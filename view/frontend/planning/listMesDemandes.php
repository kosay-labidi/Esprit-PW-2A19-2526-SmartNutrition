<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

try {
    require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

    // Si c'est une requête AJAX
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Pour les tests: utiliser l'ID 1 si pas de session
    // TODO: Remplacer par une vraie gestion de session
    if (!isset($_SESSION['user_id'])) {
        $userId = 1; // ID utilisateur par défaut pour les tests
    } else {
        $userId = $_SESSION['user_id'];
    }

    $demandeC = new DemandeplanningController();
    $demandes = $demandeC->listDemandesByUser($userId);

    // Si c'est une requête AJAX, retourner du JSON
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
      padding: 100px 20px 40px;
    }
    .list-container {
      max-width: 1200px;
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
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
      font-size: .9rem;
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
      min-width: 250px;
      position: relative;
    }
    .search-input {
      width: 100%;
      background: rgba(91, 62, 150, .08);
      border: 1.5px solid rgba(91, 62, 150, .25);
      border-radius: 12px;
      padding: 12px 16px 12px 44px;
      color: var(--text);
      font-size: .95rem;
      outline: none;
      transition: border-color .25s;
    }
    .search-input:focus {
      border-color: var(--violet);
    }
    .search-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
    }
    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: linear-gradient(135deg, var(--violet), var(--blue));
      border: none;
      border-radius: 12px;
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: transform .2s, box-shadow .3s;
    }
    .btn-add:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(91, 62, 150, .45);
    }
    
    .demandes-grid {
      display: grid;
      gap: 20px;
    }
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
      font-size: .85rem;
      color: var(--muted);
      font-weight: 600;
      letter-spacing: .05em;
    }
    .demande-date {
      font-size: .85rem;
      color: var(--muted);
    }
    .demande-body {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }
    .demande-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .info-label {
      font-size: .8rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .info-value {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text);
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: .75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-left: 6px;
    }
    .badge-quotidien {
      background: rgba(52, 152, 219, .2);
      color: #3498db;
    }
    .badge-hebdomadaire {
      background: rgba(155, 89, 182, .2);
      color: #9b59b6;
    }
    .badge-jours {
      background: rgba(46, 204, 113, .2);
      color: #2ecc71;
    }
    .badge-semaines {
      background: rgba(241, 196, 15, .2);
      color: #f1c40f;
    }
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
      font-size: .9rem;
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
    .btn-action:hover {
      border-color: var(--violet);
      background: rgba(91, 62, 150, .1);
    }
    .btn-delete {
      border-color: rgba(231, 76, 60, .3);
      color: #e74c3c;
    }
    .btn-delete:hover {
      border-color: #e74c3c;
      background: rgba(231, 76, 60, .1);
    }
    
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
  </style>
</head>
<body>

<div id="cursor"></div>
<div id="cursor-trail"></div>

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
    $quotidien = count(array_filter($demandes, fn($d) => $d['type_budget'] === 'quotidien'));
    $hebdomadaire = count(array_filter($demandes, fn($d) => $d['type_budget'] === 'hebdomadaire'));
    ?>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?= $totalDemandes ?></div>
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
      <a href="addDemandeplanning.php" class="btn-add">
        ➕ Nouvelle demande
      </a>
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
        <div class="demande-card" data-search="<?= htmlspecialchars(json_encode($d)) ?>">
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
            <a href="updateDemandeplanning.php?id=<?= $d['id'] ?>" class="btn-action">
              ✏️ Modifier
            </a>
            <a href="deleteDemandeplanning.php?id=<?= $d['id'] ?>" 
               class="btn-action btn-delete"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')">
              🗑️ Supprimer
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
// Theme toggle
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

// Search filter
function filterDemandes() {
  const val = document.getElementById('searchInput').value.toLowerCase();
  const cards = document.querySelectorAll('.demande-card');
  
  cards.forEach(card => {
    const searchData = card.getAttribute('data-search').toLowerCase();
    card.style.display = searchData.includes(val) ? '' : 'none';
  });
}

// Cursor effects
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
