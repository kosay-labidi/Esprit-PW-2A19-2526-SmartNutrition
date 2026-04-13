<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
require_once(__DIR__ . '/../../../Model/Demandeplanning.php');

$error = "";
$demandeC = new DemandeplanningController();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listMesDemandes.php');
    exit;
}

$id = (int)$_GET['id'];
$demande = $demandeC->getDemandeById($id);

if (!$demande) {
    header('Location: listMesDemandes.php');
    exit;
}

if (isset($_POST["calories"]) && isset($_POST["budget"]) && isset($_POST["type_budget"]) && isset($_POST["duree"]) && isset($_POST["type_duree"])) {
    if (!empty($_POST["calories"]) && !empty($_POST["budget"]) && !empty($_POST["type_budget"]) && !empty($_POST["duree"]) && !empty($_POST["type_duree"])) {

        $updated = new Demandeplanning(
            $id,
            (int)$demande['id_utilisateur'],
            (int)$_POST['calories'],
            (float)$_POST['budget'],
            $_POST['type_budget'],
            (int)$_POST['duree'],
            $_POST['type_duree'],
            null
        );

        $demandeC->updateDemande($updated, $id);
        header('Location: listMesDemandes.php');
        exit;

    } else {
        $error = "Informations manquantes";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>GaiaLumen – Modifier Demande</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/dashboard.css"/>
  <style>
    .form-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 100px 20px 40px;
    }
    .form-card {
      background: var(--card-bg);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(91, 62, 150, .25);
      border-radius: var(--radius);
      padding: 48px;
      width: 100%;
      max-width: 580px;
      position: relative;
      overflow: hidden;
    }
    .form-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--blue), var(--violet));
    }
    .form-header { text-align: center; margin-bottom: 36px; }
    .form-header .form-icon { font-size: 3rem; margin-bottom: 12px; display: block; }
    .form-header h1 { font-size: 2rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .form-header p { color: var(--muted); font-size: .9rem; }
    .form-group { margin-bottom: 22px; }
    .form-group label {
      display: block;
      font-size: .88rem;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 8px;
      letter-spacing: .04em;
      text-transform: uppercase;
    }
    .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-control {
      width: 100%;
      background: rgba(91, 62, 150, .08);
      border: 1.5px solid rgba(91, 62, 150, .25);
      border-radius: 12px;
      padding: 12px 16px;
      color: var(--text);
      font-family: 'Lato', sans-serif;
      font-size: .95rem;
      outline: none;
      transition: border-color .25s, box-shadow .25s;
      appearance: none;
    }
    .form-control:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(58, 134, 196, .15);
    }
    .form-control::placeholder { color: rgba(168, 184, 160, .5); }
    select.form-control {
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%233A86C4' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px;
    }
    select.form-control option { background: #0f2318; color: var(--text); }
    .alert-error {
      background: rgba(231, 76, 60, .12);
      border: 1px solid rgba(231, 76, 60, .3);
      color: #e74c3c;
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 24px;
      font-size: .9rem;
    }
    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--blue), var(--violet));
      border: none;
      border-radius: 12px;
      color: #fff;
      font-family: 'Lato', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: transform .2s, box-shadow .3s;
      margin-top: 8px;
      letter-spacing: .04em;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(58, 134, 196, .45); }
    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--muted);
      text-decoration: none;
      font-size: .88rem;
      margin-bottom: 28px;
      transition: color .2s;
    }
    .btn-back:hover { color: var(--blue); }
    .divider { height: 1px; background: rgba(91, 62, 150, .15); margin: 28px 0; }
    .demande-id {
      display: inline-block;
      background: rgba(58, 134, 196, .15);
      border: 1px solid rgba(58, 134, 196, .3);
      color: var(--blue);
      padding: 4px 14px;
      border-radius: 20px;
      font-size: .82rem;
      font-weight: 600;
      margin-bottom: 16px;
    }
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
    <a href="listMesDemandes.php" class="btn-logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
        <path d="M19 12H5M12 5l-7 7 7 7"/>
      </svg>
      Retour
    </a>
  </div>
</nav>

<div class="form-page">
  <div class="form-card">

    <a href="listMesDemandes.php" class="btn-back">← Retour à mes demandes</a>
    <div class="demande-id">Demande #<?= $id ?></div>

    <div class="form-header">
      <span class="form-icon">✏️</span>
      <h1>Modifier la Demande</h1>
      <p>Mettez à jour votre planning nutritionnel</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="form-group">
        <label>🔥 Objectif calorique (kcal/jour)</label>
        <input type="number" name="calories" class="form-control"
               placeholder="ex: 2000" min="1"
               value="<?= htmlspecialchars($_POST['calories'] ?? $demande['calories']) ?>" required/>
      </div>

      <div class="form-group">
        <label>💰 Budget</label>
        <div class="input-row">
          <input type="number" name="budget" class="form-control"
                 placeholder="ex: 50" min="0.01" step="0.01"
                 value="<?= htmlspecialchars($_POST['budget'] ?? $demande['budget']) ?>" required/>
          <select name="type_budget" class="form-control" required>
            <option value="quotidien"    <?= ($demande['type_budget'] === 'quotidien')    ? 'selected' : '' ?>>Quotidien</option>
            <option value="hebdomadaire" <?= ($demande['type_budget'] === 'hebdomadaire') ? 'selected' : '' ?>>Hebdomadaire</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>⏱️ Durée du planning</label>
        <div class="input-row">
          <input type="number" name="duree" class="form-control"
                 placeholder="ex: 7" min="1"
                 value="<?= htmlspecialchars($_POST['duree'] ?? $demande['duree']) ?>" required/>
          <select name="type_duree" class="form-control" required>
            <option value="jours"    <?= ($demande['type_duree'] === 'jours')    ? 'selected' : '' ?>>Jours</option>
            <option value="semaines" <?= ($demande['type_duree'] === 'semaines') ? 'selected' : '' ?>>Semaines</option>
          </select>
        </div>
      </div>

      <div class="divider"></div>

      <button type="submit" class="btn-submit">💾 Enregistrer les modifications</button>

    </form>
  </div>
</div>

<script>
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
  document.querySelectorAll('a, button, .form-control').forEach(el => {
    el.addEventListener('mouseenter', () => cur.classList.add('hover'));
    el.addEventListener('mouseleave', () => cur.classList.remove('hover'));
  });
})();
</script>

</body>
</html>
