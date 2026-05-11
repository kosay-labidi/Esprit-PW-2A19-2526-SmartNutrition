<?php
require_once(__DIR__ . '/../../../controller/participant.controller.php');
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../controller/paiementDefi.controller.php');
require_once(__DIR__ . '/../../../Model/Participant.php');
require_once(__DIR__ . '/../../../helpers/auth_user.php');

$participantC = new ParticipantController();
$challengeC   = new ChallengeController();
$paiementC    = new PaiementDefiController();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ── Gestion du JSON (pour AJAX) ──────────────────────────────
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if ($data) {
        foreach ($data as $key => $value) {
            $_POST[$key] = $value;
        }
    }
}

// ── Traitement POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUserId = gl_current_user_id($_POST);
    $required = ['id_challenge', 'nom', 'email', 'objectif'];
    $missing  = array_filter($required, fn($k) => empty($_POST[$k]));

    if ($missing) {
        $response = ['success' => false, 'message' => 'Champs obligatoires manquants : ' . implode(', ', $missing)];
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $response = ['success' => false, 'message' => 'Adresse email invalide.'];
    } elseif ($participantC->emailDejaInscrit(trim($_POST['email']), (int)$_POST['id_challenge'])) {
        $response = ['success' => false, 'message' => 'Cette adresse email est déjà inscrite à ce défi.'];
    } else {
        $challenge = $challengeC->showChallenge((int)$_POST['id_challenge']);
        $estPayant = (int)($challenge['est_payant'] ?? 0) === 1;
        $prix = max(0, (float)($challenge['prix'] ?? 0));
        $paiementReference = trim((string)($_POST['paiement_reference'] ?? ''));
        $paiementValide = null;

        if ($estPayant && $prix > 0) {
            $paiementValide = $paiementC->paiementValidePourInscription(
                (int)$_POST['id_challenge'],
                trim($_POST['email']),
                $paiementReference,
                $prix
            );
        }

        if ($estPayant && $prix > 0 && !$paiementValide) {
            $response = [
                'success' => false,
                'paiement_required' => true,
                'message' => 'Paiement validé requis avant inscription.',
                'prix' => $prix,
                'challenge' => $challenge['titre'] ?? 'Défi payant'
            ];
        } else {
        $participant = new Participant(
            (int)   $_POST['id_challenge'],
            trim(   $_POST['nom']),
            trim(   $_POST['email']),
            (int)  ($_POST['objectif']      ?? 0),
            trim(  $_POST['motivation']     ?? ''),
            trim(  $_POST['action']         ?? ''),
            (int)  ($_POST['engagement']    ?? 0),
            (int)  ($_POST['notifications'] ?? 0)
        );
            $participantId = $participantC->addParticipant($participant, $currentUserId);
            $ok = $participantId !== false;
            $paiement = null;

            if ($ok && $estPayant && $prix > 0) {
                $linked = $paiementC->lierParticipant($paiementReference, (int)$participantId);
                if (!$linked) {
                    $response = ['success' => false, 'message' => 'Participant ajouté, mais paiement non relié.'];
                } else {
                    $paiement = [
                        'success' => true,
                        'reference' => $paiementReference,
                        'statut' => $paiementValide['statut'] ?? 'paye',
                        'methode' => $paiementValide['methode'] ?? '',
                    ];
                }
            }

            if (!isset($response)) {
                $response = [
                    'success' => $ok,
                    'message' => $ok ? 'Participant ajouté avec succès !' : 'Erreur lors de l\'ajout.',
                    'participant_id' => $ok ? (int)$participantId : 0,
                    'paiement' => $paiement,
                ];
            }
        }
    }

    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    if ($response['success']) {
        header('Location: showParticipant.php?id_challenge=' . (int)$_POST['id_challenge']);
        exit;
    }

    $error = $response['message'];
}

// ── Pré-remplir le select défi si id_challenge en GET ────────
$id_challenge_pre = (int)($_GET['id_challenge'] ?? 0);
$challenges       = $challengeC->listChallenges();
$error            = $error ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>GaiaLumen | Ajouter un participant</title>
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../css/admin.css"/>
    <link rel="stylesheet" href="../../css/challenges-admin.css"/>
    <style>
        body         { background:#0f0f1a; color:#e2e8f0; }
        .gl-form-card{ background:#1e1e2e; border:1px solid rgba(99,102,241,0.3); border-radius:16px; padding:32px; max-width:640px; margin:30px auto; }
        .gl-label    { display:block; color:#94a3b8; font-size:12px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
        .gl-input    { width:100%; box-sizing:border-box; background:#2d2d44; border:1px solid rgba(99,102,241,0.35); border-radius:9px; color:#e2e8f0; padding:10px 14px; font-size:14px; transition:border-color .2s; }
        .gl-input:focus{ border-color:#6366f1; outline:none; }
        .gl-input.error{ border-color:#ef4444; }
        .gl-error    { color:#ef4444; font-size:12px; margin-top:4px; }
        .gl-btn      { padding:11px 24px; border-radius:9px; border:none; cursor:pointer; font-size:14px; font-weight:600; }
        .gl-btn-primary{ background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
        .gl-btn-ghost  { background:none; border:1px solid rgba(255,255,255,0.15); color:#94a3b8; }
        .gl-range-wrap { display:flex; align-items:center; gap:12px; }
        .gl-range-wrap input[type=range]{ flex:1; accent-color:#6366f1; }
        .gl-range-val  { min-width:38px; text-align:right; color:#818cf8; font-weight:700; }
        .gl-toggle-row { display:flex; justify-content:space-between; align-items:center; }
        .gl-toggle     { position:relative; width:44px; height:24px; }
        .gl-toggle input{ opacity:0; width:0; height:0; }
        .gl-toggle-slider{ position:absolute; inset:0; background:#3d3d5c; border-radius:99px; transition:.3s; cursor:pointer; }
        .gl-toggle-slider::before{ content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.3s; }
        .gl-toggle input:checked + .gl-toggle-slider{ background:#6366f1; }
        .gl-toggle input:checked + .gl-toggle-slider::before{ transform:translateX(20px); }
    </style>
</head>
<body>

<main style="padding:20px;">
    <div class="gl-form-card">

        <!-- Titre -->
        <div style="text-align:center; margin-bottom:28px;">
            <div style="font-size:2.5rem; margin-bottom:8px;">👤</div>
            <h2 style="margin:0; font-size:1.4rem;">Ajouter un participant</h2>
            <p style="color:#94a3b8; font-size:13px; margin:6px 0 0;">Inscrivez une personne à un défi GaiaLumen</p>
        </div>

        <?php if ($error): ?>
        <div style="background:#ef444422; border:1px solid #ef4444; border-radius:9px; padding:12px 16px; margin-bottom:20px; color:#ef4444; font-size:13px;">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form id="add-participant-form" method="POST" action="">

            <!-- Défi -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Défi <span style="color:#ef4444;">*</span></label>
                <select name="id_challenge" id="id_challenge" class="gl-input" required onchange="checkDuplicate()">
                    <option value="">— Choisir un défi —</option>
                    <?php foreach ($challenges as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= ((int)$c['id'] === $id_challenge_pre) ? 'selected' : '' ?>
                        data-statut="<?= htmlspecialchars($c['statut']) ?>">
                        <?= htmlspecialchars($c['streak_icon'] . ' ' . $c['titre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Nom -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Nom complet <span style="color:#ef4444;">*</span></label>
                <input type="text" name="nom" id="nom" class="gl-input" placeholder="Ex: Amine Ben Salah" required
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                <div class="gl-error" id="error-nom"></div>
            </div>

            <!-- Email -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Email <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" id="email" class="gl-input"
                       placeholder="prenom.nom@exemple.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       onblur="checkDuplicate()">
                <div class="gl-error" id="error-email"></div>
            </div>

            <!-- Objectif -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Objectif personnel (%)</label>
                <div class="gl-range-wrap">
                    <input type="range" name="objectif" id="objectif" min="0" max="100" value="<?= (int)($_POST['objectif'] ?? 50) ?>"
                           oninput="document.getElementById('objectif-val').textContent = this.value + '%'">
                    <span class="gl-range-val" id="objectif-val"><?= (int)($_POST['objectif'] ?? 50) ?>%</span>
                </div>
            </div>

            <!-- Motivation -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Motivation</label>
                <textarea name="motivation" class="gl-input" rows="3"
                          placeholder="Pourquoi rejoindre ce défi ?"><?= htmlspecialchars($_POST['motivation'] ?? '') ?></textarea>
            </div>

            <!-- Plan d'action -->
            <div style="margin-bottom:18px;">
                <label class="gl-label">Plan d'action</label>
                <textarea name="action" class="gl-input" rows="2"
                          placeholder="Quelle action concrète allez-vous prendre ?"><?= htmlspecialchars($_POST['action'] ?? '') ?></textarea>
            </div>

            <!-- Engagement -->
            <div style="margin-bottom:18px;">
                <div class="gl-toggle-row">
                    <div>
                        <label class="gl-label" style="margin:0;">Niveau d'engagement</label>
                        <div style="color:#94a3b8; font-size:12px;">Le participant est-il fortement motivé ?</div>
                    </div>
                    <label class="gl-toggle">
                        <input type="checkbox" name="engagement" id="engagement" value="1"
                               <?= (!empty($_POST['engagement'])) ? 'checked' : '' ?>>
                        <span class="gl-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Notifications -->
            <div style="margin-bottom:28px;">
                <div class="gl-toggle-row">
                    <div>
                        <label class="gl-label" style="margin:0;">Recevoir les notifications</label>
                        <div style="color:#94a3b8; font-size:12px;">Emails de rappel et d'actualités</div>
                    </div>
                    <label class="gl-toggle">
                        <input type="checkbox" name="notifications" id="notifications" value="1"
                               <?= (empty($_POST) || !empty($_POST['notifications'])) ? 'checked' : '' ?>>
                        <span class="gl-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Boutons -->
            <div style="display:flex; gap:12px;">
                <button type="submit" id="submit-btn" class="gl-btn gl-btn-primary" style="flex:1;">
                    🚀 Inscrire le participant
                </button>
                <a href="showParticipant.php<?= $id_challenge_pre > 0 ? '?id_challenge='.$id_challenge_pre : '' ?>"
                   class="gl-btn gl-btn-ghost">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</main>

<script>
// ── Vérification doublon email (AJAX) ──────────────────────────
let checkTimeout = null;
function checkDuplicate() {
    clearTimeout(checkTimeout);
    checkTimeout = setTimeout(() => {
        const email = document.getElementById('email').value.trim();
        const idC   = document.getElementById('id_challenge').value;
        const errEl = document.getElementById('error-email');
        if (!email || !idC) return;

        fetch(`showParticipant.php?action=checkEmail&email=${encodeURIComponent(email)}&id_challenge=${idC}`)
            .then(r => r.json())
            .then(data => {
                if (data.exists) {
                    document.getElementById('email').classList.add('error');
                    errEl.textContent = '⚠️ Cette adresse est déjà inscrite à ce défi.';
                    document.getElementById('submit-btn').disabled = true;
                } else {
                    document.getElementById('email').classList.remove('error');
                    errEl.textContent = '';
                    document.getElementById('submit-btn').disabled = false;
                }
            })
            .catch(() => {});
    }, 500);
}

// ── Validation côté client ────────────────────────────────────
document.getElementById('add-participant-form').addEventListener('submit', function(e) {
    const nom  = document.getElementById('nom').value.trim();
    const mail = document.getElementById('email').value.trim();
    let valid  = true;

    if (nom.length < 2) {
        document.getElementById('nom').classList.add('error');
        document.getElementById('error-nom').textContent = 'Le nom doit comporter au moins 2 caractères.';
        valid = false;
    } else {
        document.getElementById('nom').classList.remove('error');
        document.getElementById('error-nom').textContent = '';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail)) {
        document.getElementById('email').classList.add('error');
        document.getElementById('error-email').textContent = 'Email invalide.';
        valid = false;
    }

    if (!valid) e.preventDefault();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
