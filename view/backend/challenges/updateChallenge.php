<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');

$challengeC = new ChallengeController();
$isAjax     = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ── Traitement POST (update) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    if ($id <= 0) {
        $response = ['success' => false, 'message' => 'ID manquant.'];
    } elseif (
        empty($_POST['titre']) || empty($_POST['description']) || empty($_POST['type']) ||
        empty($_POST['objectif']) || !isset($_POST['valeur_cible']) ||
        empty($_POST['date_debut']) || empty($_POST['date_fin']) || empty($_POST['statut'])
    ) {
        $response = ['success' => false, 'message' => 'Informations manquantes.'];
    } else {
        $challenge = new Challenge(
            $id,
            trim($_POST['titre']),
            trim($_POST['description']),
            trim($_POST['type']),
            trim($_POST['objectif']),
            (int)$_POST['valeur_cible'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['statut'],
            trim($_POST['streak_icon'] ?? '🏆'),
            trim($_POST['image']       ?? '')
        );
        $ok = $challengeC->updateChallenge($challenge, $id);
        $response = ['success' => $ok, 'message' => $ok ? 'Défi mis à jour.' : 'Erreur lors de la mise à jour.'];
    }

    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($response['success']) {
        header('Location: listChallenges.php');
        exit;
    }
    $error = $response['message'];
}

// ── Charger les données existantes ───────────────────────────
$id        = (int)($_GET['id'] ?? 0);
$challenge = $id > 0 ? $challengeC->showChallenge($id) : null;
$error     = $error ?? '';

if (!$challenge && $id > 0) {
    header('Location: listChallenges.php');
    exit;
}

$statutOptions = [
    'en_attente' => '⏳ En attente',
    'actif'      => '✅ Actif',
    'termine'    => '📦 Terminé',
    'accepte'    => '✅ Accepté',
    'refuse'     => '❌ Refusé',
];

$iconOptions = [
    '🏆'=>'🏆 Trophée','🔥'=>'🔥 Flamme','💧'=>'💧 Eau','♻️'=>'♻️ Recyclage',
    '🚲'=>'🚲 Vélo','⚡'=>'⚡ Énergie','🌍'=>'🌍 Planète','🥗'=>'🥗 Repas',
    '🧘'=>'🧘 Méditation','🏃'=>'🏃 Course','💪'=>'💪 Fitness','📚'=>'📚 Lecture',
    '📵'=>'📵 Détox','🧎'=>'🧎 Yoga','⏰'=>'⏰ Timer',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>GaiaLumen | Modifier le défi</title>
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../css/admin.css"/>
    <link rel="stylesheet" href="../../css/challenges-admin.css"/>
    <style>
        body          { background:#0f0f1a; color:#e2e8f0; }
        .gl-form-card { background:#1e1e2e; border:1px solid rgba(99,102,241,0.3); border-radius:16px; padding:32px; max-width:720px; margin:30px auto; }
        .gl-label     { display:block; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
        .gl-input     { width:100%; box-sizing:border-box; background:#2d2d44; border:1px solid rgba(99,102,241,0.35); border-radius:9px; color:#e2e8f0; padding:10px 14px; font-size:14px; transition:border-color .2s; }
        .gl-input:focus { border-color:#6366f1; outline:none; }
        .gl-input.error { border-color:#ef4444; }
        .gl-error     { color:#ef4444; font-size:12px; margin-top:4px; }
        .gl-btn       { padding:11px 24px; border-radius:9px; border:none; cursor:pointer; font-size:14px; font-weight:600; }
        .gl-btn-primary { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
        .gl-btn-ghost   { background:none; border:1px solid rgba(255,255,255,0.15); color:#94a3b8; }
        .gl-grid-2    { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        @media(max-width:600px) { .gl-grid-2 { grid-template-columns:1fr; } }
        .gl-char-wrap { display:flex; justify-content:space-between; align-items:center; margin-top:4px; font-size:11px; color:#94a3b8; }
        .gl-char-bar  { height:3px; background:rgba(255,255,255,0.1); border-radius:99px; margin-top:4px; overflow:hidden; }
        .gl-char-fill { height:100%; background:#6366f1; border-radius:99px; transition:width .2s; }
    </style>
</head>
<body>

<main style="padding:20px;">
    <div class="gl-form-card">

        <!-- Titre -->
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:28px;">
            <div style="font-size:2.2rem;"><?= htmlspecialchars($challenge['streak_icon'] ?? '🏆') ?></div>
            <div>
                <h2 style="margin:0; font-size:1.3rem;">Modifier le défi</h2>
                <p style="color:#6366f1; font-size:13px; margin:4px 0 0;">ID #<?= $id ?></p>
            </div>
        </div>

        <?php if ($error): ?>
        <div style="background:#ef444422; border:1px solid #ef4444; border-radius:9px; padding:12px 16px; margin-bottom:20px; color:#ef4444; font-size:13px;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form id="update-form" method="POST" action="?id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Titre + Statut -->
            <div class="gl-grid-2" style="margin-bottom:18px;">
                <div>
                    <label class="gl-label">Titre <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="titre" id="u-titre" class="gl-input" required
                           value="<?= htmlspecialchars($challenge['titre'] ?? '') ?>">
                    <div class="gl-error" id="err-titre"></div>
                </div>
                <div>
                    <label class="gl-label">Statut <span style="color:#ef4444;">*</span></label>
                    <select name="statut" class="gl-input" required>
                        <?php foreach ($statutOptions as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($challenge['statut'] ?? '') === $val ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Description <span style="color:#ef4444;">*</span></label>
                <textarea name="description" id="u-desc" class="gl-input" rows="4" required maxlength="500"
                          oninput="updateCharCount(this, 'char-count-desc', 500)"><?= htmlspecialchars($challenge['description'] ?? '') ?></textarea>
                <div class="gl-char-wrap">
                    <span id="char-count-desc"><?= strlen($challenge['description'] ?? '') ?>/500</span>
                </div>
                <div class="gl-char-bar">
                    <div class="gl-char-fill" id="char-fill-desc"
                         style="width:<?= round(strlen($challenge['description'] ?? '') / 5) ?>%"></div>
                </div>
                <div class="gl-error" id="err-desc"></div>
            </div>

            <!-- Type + Objectif -->
            <div class="gl-grid-2" style="margin-bottom:18px;">
                <div>
                    <label class="gl-label">Type <span style="color:#ef4444;">*</span></label>
                    <select name="type" class="gl-input" required>
                        <?php foreach (['collectif'=>'👥 Collectif','individuel'=>'👤 Individuel',
                                        'fitness'=>'💪 Fitness','nutrition'=>'🥗 Nutrition',
                                        'bien-etre'=>'🌿 Bien-être','sport'=>'🏃 Sport','mental'=>'🧘 Mental'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($challenge['type'] ?? '') === $v ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="gl-label">Catégorie / Objectif <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="objectif" class="gl-input" required
                           placeholder="Ex: eau, repas, transport…"
                           value="<?= htmlspecialchars($challenge['objectif'] ?? '') ?>">
                </div>
            </div>

            <!-- Valeur cible + Icône -->
            <div class="gl-grid-2" style="margin-bottom:18px;">
                <div>
                    <label class="gl-label">Valeur cible (%) <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="valeur_cible" id="u-valeur" class="gl-input"
                           min="1" max="100" required
                           value="<?= (int)($challenge['valeur_cible'] ?? 50) ?>">
                    <div class="gl-error" id="err-valeur"></div>
                </div>
                <div>
                    <label class="gl-label">Icône / Emoji</label>
                    <select name="streak_icon" class="gl-input">
                        <?php foreach ($iconOptions as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($challenge['streak_icon'] ?? '') === $v ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Dates -->
            <div class="gl-grid-2" style="margin-bottom:18px;">
                <div>
                    <label class="gl-label">Date de début <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="date_debut" id="u-debut" class="gl-input" required
                           value="<?= htmlspecialchars($challenge['date_debut'] ?? '') ?>">
                    <div class="gl-error" id="err-debut"></div>
                </div>
                <div>
                    <label class="gl-label">Date de fin <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="date_fin" id="u-fin" class="gl-input" required
                           value="<?= htmlspecialchars($challenge['date_fin'] ?? '') ?>">
                    <div class="gl-error" id="err-fin"></div>
                </div>
            </div>

            <!-- Image -->
            <div style="margin-bottom:28px;">
                <label class="gl-label">Image (URL)</label>
                <input type="url" name="image" id="u-image" class="gl-input"
                       placeholder="https://…"
                       value="<?= htmlspecialchars($challenge['image'] ?? '') ?>">
                <div class="gl-error" id="err-image"></div>
                <div id="img-preview" style="margin-top:10px;display:none;">
                    <img id="img-preview-el" src="" alt="Aperçu"
                         style="max-height:120px; border-radius:10px; border:1px solid rgba(99,102,241,0.3);">
                </div>
            </div>

            <!-- Boutons -->
            <div style="display:flex; gap:12px;">
                <button type="submit" id="submit-btn" class="gl-btn gl-btn-primary" style="flex:1;">
                    💾 Enregistrer les modifications
                </button>
                <a href="listChallenges.php" class="gl-btn gl-btn-ghost">Annuler</a>
            </div>
        </form>
    </div>
</main>

<script>
// ── Compteur de caractères ────────────────────────────────────
function updateCharCount(el, displayId, max) {
    const n   = el.value.length;
    const pct = (n / max) * 100;
    const disp = document.getElementById(displayId);
    const fill = document.getElementById('char-fill-' + displayId.replace('char-count-',''));
    if (disp) disp.textContent = n + '/' + max;
    if (fill) {
        fill.style.width = pct + '%';
        fill.style.background = pct > 90 ? '#ef4444' : pct > 70 ? '#f59e0b' : '#6366f1';
    }
}

// ── Aperçu image ──────────────────────────────────────────────
document.getElementById('u-image').addEventListener('input', function() {
    const url = this.value.trim();
    const wrap = document.getElementById('img-preview');
    const img  = document.getElementById('img-preview-el');
    try {
        new URL(url);
        img.src = url;
        img.onload = () => { wrap.style.display = 'block'; };
        img.onerror = () => { wrap.style.display = 'none'; };
    } catch { wrap.style.display = 'none'; }
});

// ── Validation avant submit ───────────────────────────────────
document.getElementById('update-form').addEventListener('submit', function(e) {
    let valid = true;

    const titre = document.getElementById('u-titre').value.trim();
    if (titre.length < 3) {
        document.getElementById('u-titre').classList.add('error');
        document.getElementById('err-titre').textContent = 'Au moins 3 caractères.';
        valid = false;
    } else {
        document.getElementById('u-titre').classList.remove('error');
        document.getElementById('err-titre').textContent = '';
    }

    const desc = document.getElementById('u-desc').value.trim();
    if (desc.length < 10) {
        document.getElementById('u-desc').classList.add('error');
        document.getElementById('err-desc').textContent = 'Au moins 10 caractères.';
        valid = false;
    } else {
        document.getElementById('u-desc').classList.remove('error');
        document.getElementById('err-desc').textContent = '';
    }

    const val = parseInt(document.getElementById('u-valeur').value);
    if (isNaN(val) || val < 1 || val > 100) {
        document.getElementById('u-valeur').classList.add('error');
        document.getElementById('err-valeur').textContent = 'Entre 1 et 100%.';
        valid = false;
    } else {
        document.getElementById('u-valeur').classList.remove('error');
        document.getElementById('err-valeur').textContent = '';
    }

    const debut = document.getElementById('u-debut').value;
    const fin   = document.getElementById('u-fin').value;
    if (debut && fin && new Date(debut) > new Date(fin)) {
        document.getElementById('u-debut').classList.add('error');
        document.getElementById('err-debut').textContent = 'La date de début doit être avant la fin.';
        valid = false;
    } else {
        document.getElementById('u-debut').classList.remove('error');
        document.getElementById('err-debut').textContent = '';
    }

    if (!valid) e.preventDefault();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
