<?php
require_once(__DIR__ . '/../../../controller/participant.controller.php');
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../Model/Participant.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');

$participantC = new ParticipantController();
$challengeC   = new ChallengeController();

// ═══════════════════════════════════════════════════════════════
// HANDLER AJAX CENTRAL — participants
// ═══════════════════════════════════════════════════════════════
$action = $_GET['action'] ?? '';
$isAjax = !empty($action)
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($action !== '') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    switch ($action) {

        // ── Lister avec filtres + pagination ─────────────────
        case 'list':
            $search      = trim($_GET['search']      ?? '');
            $id_challenge = (int)($_GET['id_challenge'] ?? 0);
            $engagement  = $_GET['engagement']       ?? '';
            $page        = max(1, (int)($_GET['page']  ?? 1));
            $limit       = max(1, (int)($_GET['limit'] ?? 8));
            echo json_encode(
                $participantC->listParticipantsFiltres($search, $id_challenge, $engagement, $page, $limit)
            );
            break;

        // ── Statistiques participants ─────────────────────────
        case 'stats':
            echo json_encode($participantC->getStatistiquesParticipants());
            break;

        // ── Export CSV ────────────────────────────────────────
        case 'exportCSV':
            $participantC->exportCSV(); // inclut exit()
            break;

        // ── Vérifier doublon email ────────────────────────────
        case 'checkEmail':
            $body  = json_decode(file_get_contents('php://input'), true) ?? [];
            $email = trim($body['email'] ?? $_GET['email'] ?? '');
            $idC   = (int)($body['id_challenge'] ?? $_GET['id_challenge'] ?? 0);
            echo json_encode(['exists' => $participantC->emailDejaInscrit($email, $idC)]);
            break;

        default:
            echo json_encode(['error' => 'Action inconnue: ' . htmlspecialchars($action)]);
    }
    exit;
}

// ── Réponse AJAX simple (compatibilité ?ajax=1) ──────────────
if ($isAjax) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    $id_challenge = 0;
    if (isset($_GET['id']))           $id_challenge = (int)$_GET['id'];
    elseif (isset($_GET['id_challenge'])) $id_challenge = (int)$_GET['id_challenge'];

    $challenge    = $id_challenge > 0 ? $challengeC->showChallenge($id_challenge) : null;
    $participants = $participantC->listParticipants($id_challenge > 0 ? $id_challenge : null);

    echo json_encode([
        'success'      => true,
        'id_challenge' => $id_challenge,
        'challenge'    => $challenge,
        'participants' => $participants
    ]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// RENDU HTML NORMAL
// ═══════════════════════════════════════════════════════════════

$id_challenge = 0;
if (isset($_GET['id']))               $id_challenge = (int)$_GET['id'];
elseif (isset($_GET['id_challenge'])) $id_challenge = (int)$_GET['id_challenge'];

$challenge    = $id_challenge > 0 ? $challengeC->showChallenge($id_challenge) : null;
$participants = $participantC->listParticipants($id_challenge > 0 ? $id_challenge : null);
$stats        = $participantC->getStatistiquesParticipants();

$badgeColors = [
    'actif' => '#22c55e', 'termine' => '#6b7280',
    'en_attente' => '#f59e0b', 'accepte' => '#3b82f6', 'refuse' => '#ef4444'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>GaiaLumen | Participants</title>
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../../css/admin.css"/>
    <link rel="stylesheet" href="../../css/challenges-admin.css"/>
    <style>
        body { background:#0f0f1a; color:#e2e8f0; }
        .gl-card { background:#1e1e2e; border:1px solid rgba(99,102,241,0.25); border-radius:14px; padding:22px; margin-bottom:20px; }
        .gl-table thead th { background:#6366f1; color:#fff; padding:11px 14px; }
        .gl-table tbody tr:hover { background:rgba(99,102,241,0.07); }
        .gl-table td { padding:10px 14px; border-bottom:1px solid rgba(255,255,255,0.05); color:#cbd5e1; }
        .gl-badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .gl-avatar { width:36px; height:36px; border-radius:50%; display:inline-flex; align-items:center;
                     justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }
        .gl-search-bar { background:#1e1e2e; border:1px solid rgba(99,102,241,0.3); border-radius:10px; padding:16px; margin-bottom:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .gl-search-bar input, .gl-search-bar select { background:#2d2d44; border:1px solid rgba(99,102,241,0.35); border-radius:8px; color:#e2e8f0; padding:8px 12px; font-size:13px; }
        .gl-btn { padding:8px 18px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; }
        .gl-btn-primary { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; }
        .gl-btn-success { background:#22c55e; color:#fff; }
        .gl-btn-danger  { background:#ef4444; color:#fff; }
        .gl-btn-warning { background:#f59e0b; color:#fff; }
        .gl-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:20px; }
        .gl-stat-card { background:#2d2d44; border-radius:12px; padding:16px; text-align:center; border-left:3px solid #6366f1; }
        .gl-stat-card .val { font-size:1.8rem; font-weight:700; color:#818cf8; }
        .gl-stat-card .lbl { font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin-top:4px; }
        .gl-progress { height:7px; background:rgba(255,255,255,0.1); border-radius:99px; overflow:hidden; }
        .gl-progress-fill { height:100%; background:linear-gradient(90deg,#6366f1,#8b5cf6); border-radius:99px; }
        .gl-pagination { display:flex; align-items:center; gap:8px; justify-content:flex-end; margin-top:16px; flex-wrap:wrap; }
        .gl-page-btn { background:#2d2d44; border:1px solid rgba(99,102,241,0.3); color:#818cf8; padding:6px 12px; border-radius:7px; cursor:pointer; font-size:13px; }
        .gl-page-btn:hover, .gl-page-btn.active { background:#6366f1; color:#fff; border-color:#6366f1; }
        .gl-page-btn:disabled { opacity:0.4; cursor:not-allowed; }
        #toast-container { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>
<?php if (file_exists('../../includes/sidebar.php') === false): ?>
<div style="display:none"><!-- sidebar non chargé, standalone --></div>
<?php endif; ?>

<main class="main-wrapper" style="padding:30px 24px; max-width:1200px; margin:auto;">

    <!-- ── Header ─────────────────────────────────────── -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:1.5rem;">
                👥 <?= $challenge ? htmlspecialchars($challenge['streak_icon'] . ' ' . $challenge['titre']) : 'Tous les participants' ?>
            </h2>
            <?php if ($challenge): ?>
            <p style="color:#94a3b8; font-size:13px; margin:4px 0 0;">
                <?= htmlspecialchars($challenge['date_debut']) ?> →
                <?= htmlspecialchars($challenge['date_fin']) ?> ·
                <span style="color:<?= $badgeColors[$challenge['statut']] ?? '#94a3b8' ?>">
                    <?= htmlspecialchars($challenge['statut']) ?>
                </span>
            </p>
            <?php endif; ?>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="addParticipant.php<?= $id_challenge > 0 ? '?id_challenge=' . $id_challenge : '' ?>"
               class="gl-btn gl-btn-primary">
                <i class="lni lni-plus"></i> Ajouter un participant
            </a>
            <button onclick="window.location.href='showParticipant.php?action=exportCSV<?= $id_challenge > 0 ? '&id_challenge='.$id_challenge : '' ?>'"
                    class="gl-btn gl-btn-success">
                <i class="lni lni-download"></i> Export CSV
            </button>
            <a href="listChallenges.php" class="gl-btn" style="background:#2d2d44; color:#e2e8f0; border:1px solid rgba(255,255,255,0.1);">
                ← Retour
            </a>
        </div>
    </div>

    <!-- ── Stats ──────────────────────────────────────── -->
    <div class="gl-stat-grid">
        <div class="gl-stat-card">
            <div class="val"><?= count($participants) ?></div>
            <div class="lbl">👥 Participants</div>
        </div>
        <div class="gl-stat-card">
            <div class="val"><?= count(array_filter($participants, fn($p) => (int)$p['engagement'] === 1)) ?></div>
            <div class="lbl">🔥 Engagés</div>
        </div>
        <div class="gl-stat-card">
            <div class="val"><?= count($participants) > 0 ? round(array_sum(array_column($participants, 'objectif')) / count($participants)) : 0 ?>%</div>
            <div class="lbl">📈 Avg objectif</div>
        </div>
        <div class="gl-stat-card">
            <div class="val"><?= count(array_filter($participants, fn($p) => (int)$p['notifications'] === 1)) ?></div>
            <div class="lbl">🔔 Notifs actives</div>
        </div>
    </div>

    <!-- ── Barre de recherche / filtres ───────────────── -->
    <div class="gl-search-bar">
        <input type="text" id="search-p" placeholder="🔍 Rechercher (nom, email)…"
               style="flex:1; min-width:180px;" oninput="filterTable()">
        <select id="filter-engagement" onchange="filterTable()">
            <option value="">Tous les niveaux</option>
            <option value="1">🔥 Engagés</option>
            <option value="0">😴 Inactifs</option>
        </select>
        <select id="filter-notif" onchange="filterTable()">
            <option value="">Notifications : toutes</option>
            <option value="1">✅ Notifications ON</option>
            <option value="0">❌ Notifications OFF</option>
        </select>
        <button onclick="resetFilters()" class="gl-btn" style="background:#2d2d44; color:#94a3b8; border:1px solid rgba(255,255,255,0.1);">
            ↺ Reset
        </button>
        <span id="count-label" style="color:#94a3b8; font-size:13px; margin-left:auto;">
            <?= count($participants) ?> participant(s)
        </span>
    </div>

    <!-- ── Tableau ─────────────────────────────────────── -->
    <div class="gl-card" style="padding:0; overflow:hidden;">
        <table class="gl-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Participant</th>
                    <?php if (!$challenge): ?><th>Défi</th><?php endif; ?>
                    <th>Objectif</th>
                    <th>Engagement</th>
                    <th>Notifications</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="participants-table-body">
            <?php if (empty($participants)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                        <div style="font-size:3rem; margin-bottom:10px;">👤</div>
                        Aucun participant pour ce défi.
                        <br><br>
                        <a href="addParticipant.php<?= $id_challenge > 0 ? '?id_challenge='.$id_challenge : '' ?>"
                           class="gl-btn gl-btn-primary">Ajouter le premier participant</a>
                    </td>
                </tr>
            <?php else: foreach ($participants as $p):
                $prog = min(100, max(0, (int)$p['objectif']));
                $progColor = $prog >= 70 ? '#22c55e' : ($prog >= 40 ? '#f59e0b' : '#ef4444');
                $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $p['nom']), 0, 2))));
                $colors = ['#5B3E96','#3A86C4','#27ae60','#e67e22','#e74c3c'];
                $avatarColor = $colors[abs(crc32($p['nom'])) % count($colors)];
            ?>
                <tr class="participant-row"
                    data-nom="<?= htmlspecialchars(strtolower($p['nom'])) ?>"
                    data-email="<?= htmlspecialchars(strtolower($p['email'])) ?>"
                    data-engagement="<?= (int)$p['engagement'] ?>"
                    data-notif="<?= (int)$p['notifications'] ?>">
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="gl-avatar" style="background:<?= $avatarColor ?>"><?= $initials ?></div>
                            <div>
                                <div style="font-weight:600; color:#e2e8f0;"><?= htmlspecialchars($p['nom']) ?></div>
                                <div style="font-size:12px; color:#94a3b8;"><?= htmlspecialchars($p['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <?php if (!$challenge): ?>
                    <td>
                        <span style="font-size:13px;">
                            <?= htmlspecialchars($p['challenge_icon'] ?? '') ?>
                            <?= htmlspecialchars($p['challenge_titre'] ?? '#' . $p['id_challenge']) ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="gl-progress" style="flex:1; min-width:60px;">
                                <div class="gl-progress-fill" style="width:<?= $prog ?>%; background:<?= $progColor ?>;"></div>
                            </div>
                            <span style="color:<?= $progColor ?>; font-weight:700; font-size:13px;"><?= $prog ?>%</span>
                        </div>
                    </td>
                    <td>
                        <?php if ((int)$p['engagement'] === 1): ?>
                            <span class="gl-badge" style="background:#22c55e22; color:#22c55e; border:1px solid #22c55e55;">🔥 Engagé</span>
                        <?php else: ?>
                            <span class="gl-badge" style="background:#6b728022; color:#94a3b8; border:1px solid #6b728055;">😴 Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$p['notifications'] === 1): ?>
                            <span class="gl-badge" style="background:#3b82f622; color:#3b82f6; border:1px solid #3b82f655;">🔔 Oui</span>
                        <?php else: ?>
                            <span class="gl-badge" style="background:#6b728022; color:#94a3b8; border:1px solid #6b728055;">🔕 Non</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px; color:#94a3b8;">
                        <?= htmlspecialchars(date('d/m/Y', strtotime($p['date_inscription'] ?? 'now'))) ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="updateParticipant.php?id=<?= (int)$p['id'] ?>"
                               class="gl-btn gl-btn-warning" style="padding:5px 10px; font-size:12px;" title="Modifier">
                                ✏️
                            </a>
                            <a href="deleteParticipant.php?id=<?= (int)$p['id'] ?>&id_challenge=<?= (int)$p['id_challenge'] ?>"
                               class="gl-btn gl-btn-danger" style="padding:5px 10px; font-size:12px;" title="Supprimer"
                               onclick="return confirm('Supprimer ce participant ?')">
                                🗑️
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ── Pagination HTML ────────────────────────────── -->
    <div class="gl-pagination" id="pagination-wrap"></div>

</main>

<div id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Filtrage côté client (rapide, pas de rechargement) ──────
function filterTable() {
    const search     = document.getElementById('search-p').value.toLowerCase();
    const engFilter  = document.getElementById('filter-engagement').value;
    const notifFilter= document.getElementById('filter-notif').value;
    const rows       = document.querySelectorAll('#participants-table-body .participant-row');
    let visible = 0;

    rows.forEach(row => {
        const nom  = row.dataset.nom   || '';
        const mail = row.dataset.email || '';
        const eng  = row.dataset.engagement;
        const notif= row.dataset.notif;

        const matchSearch  = !search || nom.includes(search) || mail.includes(search);
        const matchEng     = !engFilter  || eng  === engFilter;
        const matchNotif   = !notifFilter || notif === notifFilter;

        const show = matchSearch && matchEng && matchNotif;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const lbl = document.getElementById('count-label');
    if (lbl) lbl.textContent = visible + ' participant(s)';
}

function resetFilters() {
    document.getElementById('search-p').value = '';
    document.getElementById('filter-engagement').value = '';
    document.getElementById('filter-notif').value = '';
    filterTable();
}

// ── Toast minimal ───────────────────────────────────────────
function showToast(msg, type = 'success') {
    const colors = { success:'#22c55e', error:'#ef4444', warning:'#f59e0b', info:'#3b82f6' };
    const icons  = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️' };
    const c = colors[type] || colors.info;
    const container = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.style.cssText = `background:#1e1e2e;color:#e2e8f0;padding:12px 18px;border-radius:10px;
        border-left:4px solid ${c};box-shadow:0 4px 20px rgba(0,0,0,.5);font-size:13px;
        display:flex;gap:10px;min-width:260px;animation:tIn .3s ease;pointer-events:all;`;
    t.innerHTML = `<style>@keyframes tIn{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}</style>
        <span>${icons[type]}</span><span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(100%)'; t.style.transition='all .3s'; setTimeout(()=>t.remove(),300); }, 3500);
}
</script>
</body>
</html>
