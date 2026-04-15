<?php
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
require_once(__DIR__ . '/../../../Model/Demandeplanning.php');

$errors = [];

// ── Validation côté serveur (PHP) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_utilisateur = trim($_POST['id_utilisateur'] ?? '');
    $calories       = trim($_POST['calories']       ?? '');
    $budget         = trim($_POST['budget']         ?? '');
    $type_budget    = $_POST['type_budget']          ?? '';
    $duree          = trim($_POST['duree']           ?? '');
    $type_duree     = $_POST['type_duree']           ?? '';

    if ($id_utilisateur === '') {
        $errors['id_utilisateur'] = "L'identifiant utilisateur est requis.";
    } elseif (!ctype_digit($id_utilisateur) || (int)$id_utilisateur <= 0) {
        $errors['id_utilisateur'] = "L'identifiant doit être un nombre entier positif (chiffres uniquement).";
    }

    if ($calories === '') {
        $errors['calories'] = "L'objectif calorique est requis.";
    } elseif (!ctype_digit($calories) || (int)$calories <= 0) {
        $errors['calories'] = "Les calories doivent être un entier positif (ex: 2000). Les lettres ne sont pas acceptées.";
    } elseif ((int)$calories < 500 || (int)$calories > 10000) {
        $errors['calories'] = "Les calories doivent être comprises entre 500 et 10 000 kcal.";
    }

    if ($budget === '') {
        $errors['budget'] = "Le budget est requis.";
    } elseif (!is_numeric(str_replace(',', '.', $budget)) || (float)str_replace(',', '.', $budget) <= 0) {
        $errors['budget'] = "Le budget doit être un nombre positif (ex: 50.00). Les lettres ne sont pas acceptées.";
    } elseif ((float)str_replace(',', '.', $budget) > 100000) {
        $errors['budget'] = "Le budget semble trop élevé (max 100 000).";
    }

    if (!in_array($type_budget, ['quotidien', 'hebdomadaire'])) {
        $errors['type_budget'] = "Veuillez sélectionner une période valide (Quotidien / Hebdomadaire).";
    }

    if ($duree === '') {
        $errors['duree'] = "La durée est requise.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $errors['duree'] = "La durée doit être un entier positif (ex: 7). Les lettres ne sont pas acceptées.";
    } elseif ((int)$duree > 365) {
        $errors['duree'] = "La durée ne peut pas dépasser 365 unités.";
    }

    if (!in_array($type_duree, ['jours', 'semaines'])) {
        $errors['type_duree'] = "Veuillez sélectionner une unité valide (Jours / Semaines).";
    }

    if (empty($errors)) {
        $demandeC = new DemandeplanningController();
        $demande  = new Demandeplanning(
            null,
            (int)$id_utilisateur,
            (int)$calories,
            (float)str_replace(',', '.', $budget),
            $type_budget,
            (int)$duree,
            $type_duree,
            null
        );
        $demandeC->addDemande2($demande);
        header('Location: listMesDemandes.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>GaiaLumen – Nouvelle Demande de Planning</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../css/dashboard.css"/>
  <style>
    .form-page { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:100px 20px 40px; }
    .form-card { background:var(--card-bg); backdrop-filter:blur(20px); border:1px solid rgba(91,62,150,.25); border-radius:var(--radius); padding:48px; width:100%; max-width:560px; margin:0 auto; position:relative; overflow:hidden; }
    .form-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--violet),var(--blue)); }
    .form-header { text-align:center; margin-bottom:36px; }
    .form-header .form-icon { font-size:3rem; margin-bottom:12px; display:block; }
    .form-header h1 { font-size:2rem; font-weight:700; color:var(--text); margin-bottom:8px; }
    .form-header p  { color:var(--muted); font-size:.9rem; }
    .form-group { margin-bottom:22px; }
    .form-group label { display:block; font-size:.88rem; font-weight:600; color:var(--muted); margin-bottom:8px; letter-spacing:.04em; text-transform:uppercase; }
    .input-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-control { width:100%; background:rgba(91,62,150,.08); border:1.5px solid rgba(91,62,150,.25); border-radius:12px; padding:12px 16px; color:var(--text); font-family:'Lato',sans-serif; font-size:.95rem; outline:none; transition:border-color .25s,box-shadow .25s; appearance:none; box-sizing:border-box; }
    .form-control:focus { border-color:var(--violet); box-shadow:0 0 0 3px rgba(91,62,150,.15); }
    .form-control::placeholder { color:rgba(168,184,160,.5); }
    .form-control.is-invalid { border-color:#e74c3c !important; box-shadow:0 0 0 3px rgba(231,76,60,.15) !important; background:rgba(231,76,60,.06) !important; }
    .form-control.is-valid   { border-color:#2ecc71 !important; box-shadow:0 0 0 3px rgba(46,204,113,.12) !important; }
    .field-error { display:flex; align-items:center; gap:6px; margin-top:6px; font-size:.82rem; color:#e74c3c; font-weight:500; animation:fadeInDown .2s ease; }
    .field-error::before { content:'⚠'; font-size:.75rem; }
    @keyframes fadeInDown { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }
    .alert-error { background:rgba(231,76,60,.12); border:1px solid rgba(231,76,60,.3); color:#e74c3c; border-radius:12px; padding:14px 18px; margin-bottom:24px; font-size:.9rem; display:flex; align-items:center; gap:10px; }
    .alert-error strong { font-weight:700; }
    select.form-control { cursor:pointer; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%235B3E96' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:36px; }
    select.form-control option { background:#0f2318; color:var(--text); }
    .field-hint { font-size:.78rem; color:var(--muted); margin-top:5px; opacity:.75; }
    .btn-submit { width:100%; padding:14px; background:linear-gradient(135deg,var(--violet),var(--blue)); border:none; border-radius:12px; color:#fff; font-family:'Lato',sans-serif; font-size:1rem; font-weight:700; cursor:pointer; transition:transform .2s,box-shadow .3s; margin-top:8px; letter-spacing:.04em; }
    .btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(91,62,150,.45); }
    .btn-back { display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; font-size:.88rem; margin-bottom:28px; transition:color .2s; }
    .btn-back:hover { color:var(--blue); }
    .divider { height:1px; background:rgba(91,62,150,.15); margin:28px 0; }
    .label-wrap { display:flex; align-items:center; gap:6px; margin-bottom:8px; }
    .label-wrap label { margin-bottom:0; }
    .tooltip-icon { width:16px; height:16px; border-radius:50%; background:rgba(91,62,150,.3); color:var(--muted); font-size:.7rem; display:inline-flex; align-items:center; justify-content:center; cursor:help; position:relative; }
    .tooltip-icon:hover::after { content:attr(data-tip); position:absolute; left:50%; top:-8px; transform:translate(-50%,-100%); background:#1a1a2e; color:#eee; font-size:.78rem; padding:6px 10px; border-radius:8px; white-space:nowrap; border:1px solid rgba(91,62,150,.4); z-index:100; pointer-events:none; }
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
        <radialGradient id="ag" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#3A86C4"/><stop offset="100%" stop-color="#5B3E96"/></radialGradient>
        <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#1F3D2B"/><stop offset="100%" stop-color="#3A86C4"/></linearGradient>
      </defs>
      <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="url(#lg)"/>
      <path d="M30 14 L30 46" stroke="rgba(242,232,207,.5)" stroke-width="1" stroke-linecap="round"/>
    </svg>
    <span class="nav-logo-text">GaiaLumen</span>
  </a>
  <div class="nav-actions">
    <button id="theme-toggle" title="Changer le thème">🌙 Sombre</button>
    <a href="../dashboard.html" class="btn-logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Retour
    </a>
  </div>
</nav>

<div class="form-page">
  <div class="form-card">

    <a href="../dashboard.html" class="btn-back">← Retour au dashboard</a>

    <div class="form-header">
      <span class="form-icon">📅</span>
      <h1>Nouvelle Demande</h1>
      <p>Créer un nouveau planning nutritionnel personnalisé</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert-error">
        ⛔ <span><strong><?= count($errors) ?> erreur(s) détectée(s).</strong> Veuillez corriger les champs en rouge.</span>
      </div>
    <?php endif; ?>

    <!-- novalidate : désactive toute validation HTML5 native du navigateur -->
    <form method="POST" action="" id="planningForm" novalidate>

      <!-- ID Utilisateur -->
      <div class="form-group">
        <div class="label-wrap">
          <label for="id_utilisateur">👤 ID Utilisateur</label>
          <span class="tooltip-icon" data-tip="Entier positif, ex: 42">?</span>
        </div>
        <input type="text" id="id_utilisateur" name="id_utilisateur"
               class="form-control <?= isset($errors['id_utilisateur']) ? 'is-invalid' : (isset($_POST['id_utilisateur']) && !isset($errors['id_utilisateur']) ? 'is-valid' : '') ?>"
               placeholder="ex: 1"
               value="<?= htmlspecialchars($_POST['id_utilisateur'] ?? '') ?>"
               autocomplete="off"/>
        <?php if (isset($errors['id_utilisateur'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['id_utilisateur']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Calories -->
      <div class="form-group">
        <div class="label-wrap">
          <label for="calories">🔥 Objectif calorique (kcal/jour)</label>
          <span class="tooltip-icon" data-tip="Entre 500 et 10 000 kcal">?</span>
        </div>
        <input type="text" id="calories" name="calories"
               class="form-control <?= isset($errors['calories']) ? 'is-invalid' : (isset($_POST['calories']) && !isset($errors['calories']) ? 'is-valid' : '') ?>"
               placeholder="ex: 2000"
               value="<?= htmlspecialchars($_POST['calories'] ?? '') ?>"
               autocomplete="off"/>
        <div class="field-hint">Valeur recommandée : 1 500 – 3 000 kcal/jour</div>
        <?php if (isset($errors['calories'])): ?>
          <div class="field-error"><?= htmlspecialchars($errors['calories']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Budget -->
      <div class="form-group">
        <div class="label-wrap">
          <label>💰 Budget</label>
          <span class="tooltip-icon" data-tip="Montant numérique positif + période">?</span>
        </div>
        <div class="input-row">
          <div>
            <input type="text" id="budget" name="budget"
                   class="form-control <?= isset($errors['budget']) ? 'is-invalid' : (isset($_POST['budget']) && !isset($errors['budget']) ? 'is-valid' : '') ?>"
                   placeholder="ex: 50.00"
                   value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>"
                   autocomplete="off"/>
            <?php if (isset($errors['budget'])): ?>
              <div class="field-error"><?= htmlspecialchars($errors['budget']) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <select id="type_budget" name="type_budget"
                    class="form-control <?= isset($errors['type_budget']) ? 'is-invalid' : (isset($_POST['type_budget']) && !isset($errors['type_budget']) ? 'is-valid' : '') ?>">
              <option value="" <?= empty($_POST['type_budget']) ? 'selected' : '' ?>>-- Période --</option>
              <option value="quotidien"    <?= ($_POST['type_budget'] ?? '') === 'quotidien'    ? 'selected' : '' ?>>Quotidien</option>
              <option value="hebdomadaire" <?= ($_POST['type_budget'] ?? '') === 'hebdomadaire' ? 'selected' : '' ?>>Hebdomadaire</option>
            </select>
            <?php if (isset($errors['type_budget'])): ?>
              <div class="field-error"><?= htmlspecialchars($errors['type_budget']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Durée -->
      <div class="form-group">
        <div class="label-wrap">
          <label>⏱️ Durée du planning</label>
          <span class="tooltip-icon" data-tip="Maximum 365 unités">?</span>
        </div>
        <div class="input-row">
          <div>
            <input type="text" id="duree" name="duree"
                   class="form-control <?= isset($errors['duree']) ? 'is-invalid' : (isset($_POST['duree']) && !isset($errors['duree']) ? 'is-valid' : '') ?>"
                   placeholder="ex: 7"
                   value="<?= htmlspecialchars($_POST['duree'] ?? '') ?>"
                   autocomplete="off"/>
            <?php if (isset($errors['duree'])): ?>
              <div class="field-error"><?= htmlspecialchars($errors['duree']) ?></div>
            <?php endif; ?>
          </div>
          <div>
            <select id="type_duree" name="type_duree"
                    class="form-control <?= isset($errors['type_duree']) ? 'is-invalid' : (isset($_POST['type_duree']) && !isset($errors['type_duree']) ? 'is-valid' : '') ?>">
              <option value="" <?= empty($_POST['type_duree']) ? 'selected' : '' ?>>-- Unité --</option>
              <option value="jours"    <?= ($_POST['type_duree'] ?? '') === 'jours'    ? 'selected' : '' ?>>Jours</option>
              <option value="semaines" <?= ($_POST['type_duree'] ?? '') === 'semaines' ? 'selected' : '' ?>>Semaines</option>
            </select>
            <?php if (isset($errors['type_duree'])): ?>
              <div class="field-error"><?= htmlspecialchars($errors['type_duree']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="divider"></div>

      <button type="submit" class="btn-submit" id="submitBtn">➕ Soumettre ma demande</button>

    </form>
  </div>
</div>

<script>
/* ── Thème ───────────────────────────────────────────────────────────────── */
var themeBtn = document.getElementById('theme-toggle');
var html     = document.documentElement;
var saved    = localStorage.getItem('theme') || 'dark';
html.setAttribute('data-theme', saved);
themeBtn.textContent = saved === 'dark' ? '🌙 Sombre' : '☀️ Clair';
themeBtn.addEventListener('click', function() {
  var t = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', t);
  localStorage.setItem('theme', t);
  themeBtn.textContent = t === 'dark' ? '🌙 Sombre' : '☀️ Clair';
});

/* ── Curseur ─────────────────────────────────────────────────────────────── */
(function(){
  var cur   = document.getElementById('cursor');
  var trail = document.getElementById('cursor-trail');
  if (!cur || !trail) return;
  var mx=0, my=0, tx=0, ty=0;
  document.addEventListener('mousemove', function(e) {
    mx = e.clientX; my = e.clientY;
    cur.style.left = mx + 'px'; cur.style.top = my + 'px';
  });
  (function loop(){
    tx += (mx - tx) * .12; ty += (my - ty) * .12;
    trail.style.left = tx + 'px'; trail.style.top = ty + 'px';
    requestAnimationFrame(loop);
  })();
  var els = document.querySelectorAll('a, button, .form-control');
  for (var i=0; i<els.length; i++) {
    els[i].addEventListener('mouseenter', function(){ cur.classList.add('hover'); });
    els[i].addEventListener('mouseleave', function(){ cur.classList.remove('hover'); });
  }
})();

/* ══════════════════════════════════════════════════════════════════════════
   VALIDATION CÔTÉ CLIENT — JavaScript pur (aucun attribut HTML5)
══════════════════════════════════════════════════════════════════════════ */

/* Règles de validation par nom de champ */
var rules = {
  id_utilisateur: function(v) {
    if (v === '')        return "L'identifiant utilisateur est requis.";
    if (!/^\d+$/.test(v) || parseInt(v, 10) <= 0)
                         return "L'identifiant doit être un entier positif (chiffres uniquement — les lettres ne sont pas acceptées).";
    return null;
  },
  calories: function(v) {
    if (v === '')        return "L'objectif calorique est requis.";
    if (!/^\d+$/.test(v)) return "Saisissez un entier (les lettres et symboles ne sont pas acceptés).";
    var n = parseInt(v, 10);
    if (n <= 0)          return "La valeur doit être supérieure à 0.";
    if (n < 500)         return "Minimum 500 kcal/jour.";
    if (n > 10000)       return "Maximum 10 000 kcal/jour.";
    return null;
  },
  budget: function(v) {
    if (v === '')        return "Le budget est requis.";
    if (!/^\d+([.,]\d{1,2})?$/.test(v)) return "Format invalide — exemple valide : 50 ou 50.00 (les lettres ne sont pas acceptées).";
    var n = parseFloat(v.replace(',', '.'));
    if (n <= 0)          return "Le budget doit être supérieur à 0.";
    if (n > 100000)      return "Ce montant semble trop élevé (max 100 000).";
    return null;
  },
  type_budget: function(v) {
    if (!v || v === '')  return "Veuillez choisir une période (Quotidien / Hebdomadaire).";
    return null;
  },
  duree: function(v) {
    if (v === '')        return "La durée est requise.";
    if (!/^\d+$/.test(v)) return "Saisissez un entier positif (les lettres ne sont pas acceptées).";
    var n = parseInt(v, 10);
    if (n <= 0)          return "La durée doit être supérieure à 0.";
    if (n > 365)         return "La durée ne peut pas dépasser 365 unités.";
    return null;
  },
  type_duree: function(v) {
    if (!v || v === '')  return "Veuillez choisir une unité (Jours / Semaines).";
    return null;
  }
};

/* Affiche une erreur sous le champ */
function showError(input, msg) {
  input.classList.remove('is-valid');
  input.classList.add('is-invalid');
  var parent = input.parentNode;
  var existing = parent.querySelector('.field-error-js');
  if (!existing) {
    existing = document.createElement('div');
    existing.className = 'field-error field-error-js';
    input.insertAdjacentElement('afterend', existing);
  }
  existing.textContent = msg;
}

/* Efface l'erreur et passe le champ en valide */
function clearError(input) {
  input.classList.remove('is-invalid');
  input.classList.add('is-valid');
  var parent = input.parentNode;
  var existing = parent.querySelector('.field-error-js');
  if (existing) existing.remove();
}

/* Valide un champ unique */
function validateField(input) {
  var name = input.name;
  if (!rules[name]) return true;
  var msg = rules[name](input.value.trim());
  if (msg) { showError(input, msg); return false; }
  clearError(input);
  return true;
}

/* Attache les événements à tous les champs */
var fields = document.querySelectorAll('.form-control');
for (var i = 0; i < fields.length; i++) {
  (function(input) {
    input.addEventListener('blur', function() { validateField(input); });
    input.addEventListener('input', function() {
      if (input.classList.contains('is-invalid')) validateField(input);
    });
    input.addEventListener('change', function() { validateField(input); });
  })(fields[i]);
}

/* Validation complète à la soumission du formulaire */
document.getElementById('planningForm').addEventListener('submit', function(e) {
  var valid = true;
  var allFields = document.querySelectorAll('.form-control');
  for (var i = 0; i < allFields.length; i++) {
    if (!validateField(allFields[i])) valid = false;
  }
  if (!valid) {
    e.preventDefault();
    var first = document.querySelector('.is-invalid');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>

</body>
</html>