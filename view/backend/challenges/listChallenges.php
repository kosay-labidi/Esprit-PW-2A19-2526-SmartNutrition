<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../controller/participant.controller.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');
require_once(__DIR__ . '/../../../helpers/auth_user.php');

$challengeC = new ChallengeController();
$participantC = new ParticipantController();

// ═══════════════════════════════════════════════════════════════
// HANDLER AJAX CENTRAL — toutes les actions passent ici
// ═══════════════════════════════════════════════════════════════
$action = $_GET['action'] ?? '';
$isAjax = !empty($action)
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || isset($_GET['ajax']);

if ($action !== '') {
    if (ob_get_length()) ob_clean();

    switch ($action) {

        // ── Lister les défis (simple, pour le tableau admin) ──
        case 'list':
            header('Content-Type: application/json');
            $idUser = gl_current_user_id($_GET);
            echo json_encode($challengeC->listChallenges($idUser));
            break;

        // ── Statistiques du dashboard ──────────────────────────
        case 'stats':
            header('Content-Type: application/json');
            echo json_encode($challengeC->getStatistiques());
            break;

        // ── Changer le statut (Accepté / Refusé / etc.) ────────
        case 'updateStatut':
            header('Content-Type: application/json');
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $id     = (int)($body['id']     ?? $_POST['id']     ?? 0);
            $statut = trim($body['statut']  ?? $_POST['statut'] ?? '');
            echo json_encode(['success' => $challengeC->updateStatut($id, $statut)]);
            break;

        // ── Mettre à jour l'ordre drag & drop ─────────────────
        case 'updateOrdre':
            header('Content-Type: application/json');
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode(['success' => $challengeC->updateOrdre($body)]);
            break;

        // ── Toggle like ────────────────────────────────────────
        case 'toggleLike':
            header('Content-Type: application/json');
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $idC     = (int)($body['id_challenge'] ?? 0);
            $idUser  = gl_current_user_id($body);
            if ($idUser <= 0) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
                break;
            }
            echo json_encode($challengeC->toggleLike($idC, $idUser));
            break;

        // ── Incrémenter les vues ───────────────────────────────
        case 'incrementVues':
            header('Content-Type: application/json');
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $id   = (int)($body['id'] ?? 0);
            if ($id > 0) $challengeC->incrementVues($id);
            echo json_encode(['success' => true]);
            break;

        // ── Notifier les participants par email ────────────────
        case 'notifier':
            header('Content-Type: application/json');
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $idC     = (int)  ($body['id_challenge'] ?? 0);
            $sujet   = trim(  $body['sujet']         ?? '');
            $message = trim(  $body['message']       ?? '');
            if ($idC <= 0 || $sujet === '' || $message === '') {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
            } else {
                echo json_encode($challengeC->notifierParticipants($idC, $sujet, $message));
            }
            break;

        // ── Export CSV ─────────────────────────────────────────
        case 'exportCSV':
            $challengeC->exportCSV();  // inclut exit()
            break;

        // ── Export PDF ─────────────────────────────────────────
        case 'exportPDF':
            $challengeC->exportPDF();  // inclut exit()
            break;

        // ── Classement intelligent ─────────────────────────────
        case 'ranking':
            header('Content-Type: application/json');
            $participantC->updateSmartRankings(); // On force la mise à jour avant de lire
            echo json_encode($participantC->getRanking(10));
            break;

        default:
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Action inconnue: ' . htmlspecialchars($action)]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
// RÉPONSE AJAX SIMPLE (compatibilité ancien code : ?ajax=1)
// ═══════════════════════════════════════════════════════════════
if ($isAjax) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    // Gestion formulaire POST (add / update challenge)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_challenge'])) {
        if (
            !empty($_POST['titre']) && !empty($_POST['description']) && !empty($_POST['type']) &&
            !empty($_POST['objectif']) && !empty($_POST['valeur_cible']) &&
            !empty($_POST['date_debut']) && !empty($_POST['date_fin']) && !empty($_POST['statut'])
        ) {
            $challenge = new Challenge(
                null,
                $_POST['titre'],
                $_POST['description'],
                $_POST['type'],
                $_POST['objectif'],
                (int)$_POST['valeur_cible'],
                $_POST['date_debut'],
                $_POST['date_fin'],
                $_POST['statut'],
                $_POST['streak_icon'] ?? '🏆',
                $_POST['image'] ?? '',
                !empty($_POST['est_payant']) ? 1 : 0,
                !empty($_POST['est_payant']) ? max(0, (float)($_POST['prix'] ?? 0)) : 0
            );
            $ok = $challengeC->addChallenge($challenge);
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Champs manquants']);
        }
        exit;
    }

    // Retour liste simple
    $idUser = gl_current_user_id($_GET);
    echo json_encode($challengeC->listChallenges($idUser));
    exit;
}

// ═══════════════════════════════════════════════════════════════
// RENDU HTML NORMAL (non-AJAX)
// ═══════════════════════════════════════════════════════════════

// Handle POST classique (sans JS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_challenge'])) {
    if (
        !empty($_POST['titre']) && !empty($_POST['description']) &&
        !empty($_POST['type']) && !empty($_POST['objectif']) &&
        !empty($_POST['valeur_cible']) && !empty($_POST['date_debut']) &&
        !empty($_POST['date_fin']) && !empty($_POST['statut'])
    ) {
        $challenge = new Challenge(
            null,
            $_POST['titre'],
            $_POST['description'],
            $_POST['type'],
            $_POST['objectif'],
            (int)$_POST['valeur_cible'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['statut'],
            $_POST['streak_icon'] ?? '🏆',
            $_POST['image'] ?? '',
            !empty($_POST['est_payant']) ? 1 : 0,
            !empty($_POST['est_payant']) ? max(0, (float)($_POST['prix'] ?? 0)) : 0
        );
        $challengeC->addChallenge($challenge);
    }
    header('Location: listChallenges.php');
    exit;
}

$idUser = gl_current_user_id($_GET);
$list = $challengeC->listChallenges($idUser);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion des Défis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
    <style>
        body { background:#0f0f1a; color:#e2e8f0; font-family:Arial,sans-serif; }
        .container-main { max-width:1200px; margin:auto; padding:30px 20px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#6366f1; color:#fff; padding:12px; text-align:left; }
        td { padding:10px 12px; border-bottom:1px solid rgba(255,255,255,0.07); }
        tr:hover { background:rgba(99,102,241,0.08); }
        .badge-statut { padding:4px 12px; border-radius:20px; font-size:12px; color:#fff; }
        .badge-actif { background:#22c55e; }
        .badge-termine { background:#6b7280; }
        .badge-en_attente { background:#f59e0b; }
        .badge-accepte { background:#3b82f6; }
        .badge-refuse { background:#ef4444; }
    </style>
</head>
<body>
<div class="container-main">
    <h2>🏆 Gestion des Défis</h2>
    <table>
        <thead>
            <tr>
                <th>Défi</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Participants</th>
                <th>Prix</th>
                <th>Date Fin</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['streak_icon'] . ' ' . $c['titre']) ?></td>
                <td><?= htmlspecialchars($c['type']) ?></td>
                <td>
                    <span class="badge-statut badge-<?= htmlspecialchars($c['statut']) ?>">
                        <?= htmlspecialchars($c['statut']) ?>
                    </span>
                </td>
                <td><?= (int)$c['participants_count'] ?></td>
                <td>
                    <?= ((int)($c['est_payant'] ?? 0) === 1)
                        ? htmlspecialchars(number_format((float)($c['prix'] ?? 0), 2, ',', ' ') . ' DT')
                        : 'Gratuit' ?>
                </td>
                <td><?= htmlspecialchars($c['date_fin']) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="showAISummary(<?= $c['id'] ?>)">✨ IA</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Simple AI Modal for standalone page -->
<div id="aiModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-light border-info">
      <div class="modal-header border-info">
        <h5 class="modal-title">✨ Résumé IA du Défi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="aiContent">
        Chargement...
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function showAISummary(id) {
    const modal = new bootstrap.Modal(document.getElementById('aiModal'));
    const content = document.getElementById('aiContent');
    content.innerHTML = '<div class="text-center p-4">⌛ L\'IA analyse les données...</div>';
    modal.show();

    try {
        const r = await fetch('../api/ai-challenge-summary.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ challenge_id: id })
        });
        const data = await r.json();
        if (!data.ok) throw new Error(data.error);

        const s = data.summary;
        const formatVal = (v) => {
            if (!v) return '-';
            if (typeof v === 'string') return v;
            if (Array.isArray(v)) return v.join(', ');
            if (typeof v === 'object') return JSON.stringify(v);
            return String(v);
        };

        content.innerHTML = `
            <h6>👥 Participants</h6><p class="small">${formatVal(s.synth_participants)}</p>
            <h6>📈 Tendances</h6><p class="small">${formatVal(s.tendances)}</p>
            <h6 class="text-danger">⚠️ Risques</h6><p class="small">${formatVal(s.risques)}</p>
            <h6 class="text-success">💡 Recommandations</h6><p class="small">${formatVal(s.recommandations)}</p>
            <div class="mt-3"><strong>Score Santé : ${s.score_sante}/100</strong></div>
        `;
    } catch (e) {
        content.innerHTML = '<div class="alert alert-danger">Erreur : ' + e.message + '</div>';
    }
}
</script>
</body>
</html>
