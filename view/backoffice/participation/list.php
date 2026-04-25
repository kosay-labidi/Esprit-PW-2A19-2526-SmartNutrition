<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../controller/ParticipationController.php';

$participationC = new ParticipationController();

// AJAX : données d'une participation en JSON (pour les modals) — doit être en premier
if (isset($_GET['action']) && $_GET['action'] === 'getdata' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $data = $participationC->showParticipation((int)$_GET['id']);
    echo json_encode($data ? $data : []);
    exit();
}

// Filtre par événement (depuis la liste des événements)
$filtreEvenementId    = isset($_GET['id_event']) ? (int)$_GET['id_event'] : null;
$filtreEvenementTitre = null;

if ($filtreEvenementId) {
    $list = $participationC->listParticipationsByEvent($filtreEvenementId);
    $filtreEvenementTitre = $participationC->getEvenementTitre($filtreEvenementId);
} else {
    $list = $participationC->listParticipations();
}

$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'deleted') $success_message = '✅ Participation supprimée avec succès !';
    elseif ($_GET['success'] == 'updated') $success_message = '✅ Statut mis à jour avec succès !';
}
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'missing_id') $error_message = '❌ ID de participation manquant.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Participations - GaiaLumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #1F3D2B; --sand: #F2E8CF; --violet: #5B3E96; --blue: #3A86C4;
            --bg: #0a1a10; --surface: #0f2318; --text: #F2E8CF; --muted: #a8b8a0;
            --card-bg: rgba(15, 35, 24, 0.72); --glass: rgba(31, 61, 43, 0.45);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.4); --radius: 18px;
            --tr: 0.4s cubic-bezier(.4, 0, .2, 1);
        }
        [data-theme="light"] {
            --green: #2d5a3f; --bg: #F2E8CF; --surface: #ffffff; --text: #1F3D2B;
            --muted: #5a6b5a; --card-bg: rgba(255, 255, 255, 0.9);
            --glass: rgba(45, 90, 63, 0.1); --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); font-family: 'Lato', sans-serif; min-height: 100vh; transition: all var(--tr); color: var(--text); }
        h1,h2,h3,h4,h5,h6,.navbar-brand,.main-title { font-family: 'Cormorant Garamond', serif; font-weight: 700; }
        .navbar-custom { background: var(--green); backdrop-filter: blur(10px); box-shadow: var(--shadow); padding: 1rem 0; }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; letter-spacing: 1px; color: var(--sand) !important; transition: all var(--tr); }
        .navbar-brand i { font-size: 1.8rem; margin-right: 10px; color: var(--violet); }
        .navbar-brand:hover { transform: scale(1.05); }
        .theme-toggle { background: var(--glass); border: 1px solid var(--sand); border-radius: 50px; padding: 8px 20px; color: var(--sand); cursor: pointer; transition: all var(--tr); font-weight: 500; }
        .theme-toggle:hover { background: var(--violet); border-color: var(--violet); transform: scale(1.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .header-section h2 { color: var(--text); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 15px; font-size: 2rem; }
        .header-section h2 i { font-size: 2.2rem; color: var(--violet); }
        .counter-badge { background: var(--glass); border-radius: 50px; padding: 6px 18px; font-size: 0.9rem; color: var(--violet); font-weight: 600; margin-left: 12px; }
        .btn-secondary-custom { background: var(--glass); border: 1px solid var(--sand); color: var(--sand); padding: 12px 28px; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all var(--tr); font-weight: 600; }
        .btn-secondary-custom:hover { background: var(--violet); transform: translateY(-3px); border-color: var(--violet); }
        .table-container { background: var(--card-bg); backdrop-filter: blur(12px); border-radius: var(--radius); overflow-x: auto; box-shadow: var(--shadow); border: 1px solid rgba(91, 62, 150, 0.1); }
        .table-custom { margin-bottom: 0; width: 100%; min-width: 900px; }
        .table-custom thead { background: var(--green); }
        .table-custom thead th { padding: 18px 15px; font-weight: 700; font-size: 0.9rem; border: none; color: var(--sand); text-transform: uppercase; letter-spacing: 0.5px; }
        .table-custom tbody tr { transition: all 0.3s ease; border-bottom: 1px solid rgba(91, 62, 150, 0.1); }
        .table-custom tbody tr:hover { background: rgba(91, 62, 150, 0.15); transform: scale(1.01); }
        .table-custom tbody td { padding: 16px 15px; vertical-align: middle; color: var(--text); font-size: 0.95rem; }
        .badge { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .badge-warning { background: rgba(241, 196, 15, 0.2); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .badge-danger { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }
        .btn-action { border-radius: 8px; padding: 8px 12px; margin: 0 4px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; border: none; cursor: pointer; font-size: 0.85rem; }
        .btn-action:hover { transform: translateY(-3px); }
        .btn-info { background: rgba(58, 134, 196, 0.15); color: var(--blue); }
        .btn-info:hover { background: var(--blue); color: white; box-shadow: 0 4px 12px rgba(58, 134, 196, 0.4); }
        .btn-warning { background: rgba(91, 62, 150, 0.15); color: var(--violet); }
        .btn-warning:hover { background: var(--violet); color: white; box-shadow: 0 4px 12px rgba(91, 62, 150, 0.4); }
        .btn-danger { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
        .btn-danger:hover { background: #e74c3c; color: white; box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4); }
        .alert-custom { border-radius: 12px; border: none; padding: 15px 20px; margin-bottom: 25px; }
        .alert-success { background: rgba(46, 204, 113, 0.15); border-left: 4px solid #2ecc71; color: #2ecc71; }
        .alert-danger { background: rgba(231, 76, 60, 0.15); border-left: 4px solid #e74c3c; color: #e74c3c; }
        .empty-row td { text-align: center; padding: 60px 20px !important; color: var(--muted); }
        .footer { background: var(--surface); color: var(--muted); padding: 25px 0; margin-top: 60px; text-align: center; border-top: 1px solid var(--glass); }

        /* ═══ MODALS ═══ */
        .modal-content { background: var(--surface); border: 1px solid var(--glass); border-radius: var(--radius); color: var(--text); }
        .modal-header-custom { background: var(--green); padding: 20px 25px; border-radius: var(--radius) var(--radius) 0 0; display: flex; align-items: center; justify-content: space-between; }
        .modal-header-custom h5 { color: var(--sand); margin: 0; font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; display: flex; align-items: center; gap: 10px; }
        .modal-header-custom h5 i { color: var(--violet); }
        .btn-close-custom { background: none; border: none; color: var(--sand); font-size: 1.2rem; cursor: pointer; opacity: .8; transition: opacity .2s; }
        .btn-close-custom:hover { opacity: 1; }
        .modal-body { padding: 25px; }
        .modal-footer-custom { padding: 15px 25px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass); }

        /* Show modal - grille */
        .show-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 10px; }
        .show-info-item { background: var(--card-bg); border-radius: 12px; padding: 14px 16px; border-left: 4px solid var(--violet); transition: all var(--tr); }
        .show-info-item:hover { transform: translateX(4px); }
        .show-info-label { font-size: .72rem; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .show-info-label i { color: var(--violet); }
        .show-info-value { font-size: 1rem; font-weight: 600; color: var(--text); margin: 0; word-break: break-word; }

        /* Edit modal - form */
        .modal .form-label { font-weight: 600; color: var(--text); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .modal .form-label i { color: var(--violet); }
        .modal .form-select { border-radius: 12px; border: 1px solid var(--glass); padding: 12px 15px; background-color: var(--card-bg); color: var(--text); transition: all var(--tr); }
        .modal .form-select:focus { border-color: var(--violet); box-shadow: 0 0 0 3px rgba(91,62,150,.2); outline: none; }
        .modal .form-select option { background: var(--surface); color: var(--text); }

        /* Info résumé dans modal edit */
        .edit-info-card { background: var(--card-bg); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; border-left: 4px solid var(--violet); }
        .edit-info-card p { margin: 4px 0; color: var(--muted); font-size: .9rem; }
        .edit-info-card strong { color: var(--violet); }

        .btn-modal-submit { padding: 11px 28px; border-radius: 50px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all var(--tr); font-family: 'Lato', sans-serif; }
        .btn-modal-submit.violet-btn { background: var(--violet); color: var(--sand); }
        .btn-modal-submit.violet-btn:hover { background: var(--green); transform: translateY(-2px); }
        .btn-modal-cancel { background: var(--glass); border: 1px solid var(--sand); color: var(--sand); padding: 11px 22px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all var(--tr); font-family: 'Lato', sans-serif; }
        .btn-modal-cancel:hover { background: var(--violet); border-color: var(--violet); }

        /* Spinner */
        .modal-spinner { display: flex; justify-content: center; align-items: center; padding: 50px; color: var(--violet); font-size: 2.2rem; }
        .modal-error { text-align: center; padding: 30px; color: #ff6b7a; }
        .modal-error i { font-size: 2rem; display: block; margin-bottom: 10px; }

        @media (max-width: 768px) {
            .header-section { flex-direction: column; align-items: stretch; }
            .btn-secondary-custom { justify-content: center; }
            .table-custom thead th, .table-custom tbody td { padding: 12px 8px; font-size: 0.8rem; }
            .show-info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body data-theme="dark">

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="/view/backoffice/modules/dashboard-admin.html">
            <i class="fas fa-leaf"></i> GaiaLumen
        </a>
        <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun me-1"></i> Mode clair
            </button>
            <a href="/view/backoffice/modules/dashboard-admin.html" class="text-white" style="text-decoration: none; opacity: 0.9;">
                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="header-section">
        <h2>
            <i class="fas fa-users"></i>
            <?php if ($filtreEvenementTitre): ?>
                Participations — <span style="color:var(--violet);"><?= htmlspecialchars($filtreEvenementTitre) ?></span>
            <?php else: ?>
                Liste des Participations
            <?php endif; ?>
            <span class="counter-badge">
                <i class="fas fa-chart-simple me-1"></i><?= $list ? $list->rowCount() : 0 ?> participations
            </span>
        </h2>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($filtreEvenementId): ?>
            <a href="list.php" class="btn-secondary-custom">
                <i class="fas fa-list me-1"></i> Toutes les participations
            </a>
            <a href="../evenement/list.php" class="btn-secondary-custom">
                <i class="fas fa-calendar-alt me-1"></i> Retour aux événements
            </a>
            <?php else: ?>
            <a href="/view/backoffice/modules/dashboard-admin.html" class="btn-secondary-custom">
                <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($filtreEvenementTitre): ?>
        <div class="alert-custom" style="background:rgba(91,62,150,0.12);border-left:4px solid var(--violet);color:var(--text);padding:14px 20px;margin-bottom:20px;border-radius:12px;">
            <i class="fas fa-filter me-2" style="color:var(--violet);"></i>
            Affichage filtré pour l'événement : <strong style="color:var(--violet);"><?= htmlspecialchars($filtreEvenementTitre) ?></strong>
            &nbsp;·&nbsp;
            <a href="list.php" style="color:var(--blue);text-decoration:underline;">Voir toutes les participations</a>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-custom">
            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-custom">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="table-custom table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag me-1"></i> ID</th>
                    <th><i class="fas fa-calendar-alt me-1"></i> Événement</th>
                    <th><i class="fas fa-user me-1"></i> Nom Complet</th>
                    <th><i class="fas fa-envelope me-1"></i> Email</th>
                    <th><i class="fas fa-phone me-1"></i> Téléphone</th>
                    <th><i class="fas fa-heart me-1"></i> Centre d'intérêt</th>
                    <th><i class="fas fa-flag-checkered me-1"></i> Statut</th>
                    <th><i class="fas fa-cogs me-1"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $hasEvents = false; foreach ($list as $p): $hasEvents = true;
                    if ($p['statut'] == 'confirmée') $badgeClass = 'badge-success';
                    elseif ($p['statut'] == 'annulée') $badgeClass = 'badge-danger';
                    else $badgeClass = 'badge-warning';
                ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($p['id_participation']) ?></strong></td>
                    <td><?= htmlspecialchars($p['evenement_titre'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['nom_complet']) ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['telephone'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($p['centre_interet'] ?? 'Non spécifié') ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($p['statut']) ?></span></td>
                    <td>
                        <!-- Voir : modal Show -->
                        <button class="btn-action btn-info" title="Voir"
                                onclick="openShowModal(<?= $p['id_participation'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <!-- Modifier : modal Edit -->
                        <button class="btn-action btn-warning" title="Modifier le statut"
                                onclick="openEditModal(<?= $p['id_participation'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- Supprimer : delete.php inchangé -->
                        <a href="delete.php?id=<?= $p['id_participation'] ?><?= $filtreEvenementId ? '&id_event='.$filtreEvenementId : '' ?>"
                           onclick="return confirm('Supprimer cette participation ?')"
                           class="btn-action btn-danger" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$hasEvents): ?>
                <tr class="empty-row">
                    <td colspan="8">
                        <i class="fas fa-users-slash fa-3x mb-3 d-block" style="color: var(--muted);"></i>
                        <h4 style="margin-bottom: 10px;">Aucune participation trouvée</h4>
                        <p style="margin-bottom: 0;">Aucun participant n'a encore rejoint vos événements</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL AFFICHER
══════════════════════════════════════════ -->
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5><i class="fas fa-info-circle"></i> Détails de la Participation</h5>
                <button class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div id="showSpinner" class="modal-spinner"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="showError" class="modal-error" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i> Impossible de charger les données.
            </div>
            <div id="showContent" style="display:none;">
                <div class="modal-body">
                    <div class="show-info-grid">
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-user"></i> NOM COMPLET</div>
                            <p class="show-info-value" id="showNom">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-envelope"></i> EMAIL</div>
                            <p class="show-info-value" id="showEmail">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-phone"></i> TÉLÉPHONE</div>
                            <p class="show-info-value" id="showTel">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-heart"></i> CENTRE D'INTÉRÊT</div>
                            <p class="show-info-value" id="showInteret">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-calendar-alt"></i> ÉVÉNEMENT</div>
                            <p class="show-info-value" id="showEvenement">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-flag-checkered"></i> STATUT</div>
                            <p class="show-info-value" id="showStatut">—</p>
                        </div>
                        <div class="show-info-item" style="grid-column:span 2;">
                            <div class="show-info-label"><i class="fas fa-qrcode"></i> RÉFÉRENCE</div>
                            <p class="show-info-value" id="showRef">—</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Fermer
                    </button>
                    <button type="button" class="btn-modal-submit violet-btn" id="showToEditBtn">
                        <i class="fas fa-edit"></i> Modifier le statut
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL MODIFIER (statut uniquement)
     Soumet vers update.php?id=X (fichier inchangé)
══════════════════════════════════════════ -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5>
                    <i class="fas fa-edit"></i> Modifier le Statut
                    <span id="editModalIdLabel" style="opacity:.7;font-size:1rem;font-family:'Lato',sans-serif;"></span>
                </h5>
                <button class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div id="editSpinner" class="modal-spinner"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="editError" class="modal-error" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i> Impossible de charger les données.
            </div>
            <!-- action mis à jour dynamiquement → update.php?id=X (inchangé) -->
            <form method="POST" id="editForm" style="display:none;">
                <div class="modal-body">
                    <div class="edit-info-card" id="editInfoCard"></div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-exchange-alt"></i> Nouveau statut</label>
                        <select name="statut" id="editStatut" class="form-select" required>
                            <option value="en_attente">⏳ En attente</option>
                            <option value="confirmée">✅ Confirmée</option>
                            <option value="annulée">❌ Annulée</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Annuler
                    </button>
                    <button type="submit" class="btn-modal-submit violet-btn">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; 2026 GaiaLumen - Héritage de Gaia. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* ── Thème ── */
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme  = localStorage.getItem('theme');
    if (savedTheme) { document.body.setAttribute('data-theme', savedTheme); updateBtnText(savedTheme); }
    themeToggle.addEventListener('click', () => {
        const t = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
        updateBtnText(t);
    });
    function updateBtnText(t) {
        themeToggle.innerHTML = t === 'dark'
            ? '<i class="fas fa-sun me-1"></i> Mode clair'
            : '<i class="fas fa-moon me-1"></i> Mode sombre';
    }

    /* ── Helpers ── */
    const statutBadges = {
        'confirmée': '<span class="badge badge-success">✅ confirmée</span>',
        'annulée':   '<span class="badge badge-danger">❌ annulée</span>',
        'en_attente':'<span class="badge badge-warning">⏳ en_attente</span>'
    };

    function fetchParticipation(id) {
        return fetch(`list.php?action=getdata&id=${id}`).then(r => r.json());
    }

    /* ════════════════════════════════
       MODAL AFFICHER
    ════════════════════════════════ */
    let currentShowId = null;

    function openShowModal(id) {
        currentShowId = id;
        const modal = new bootstrap.Modal(document.getElementById('showModal'));
        document.getElementById('showSpinner').style.display  = 'flex';
        document.getElementById('showContent').style.display  = 'none';
        document.getElementById('showError').style.display    = 'none';
        modal.show();

        fetchParticipation(id)
            .then(data => {
                if (!data || !data.id_participation) throw new Error();

                document.getElementById('showNom').textContent      = data.nom_complet      || '—';
                document.getElementById('showEmail').textContent    = data.email            || '—';
                document.getElementById('showTel').textContent      = data.telephone        || 'Non renseigné';
                document.getElementById('showInteret').textContent  = data.centre_interet   || 'Non spécifié';
                document.getElementById('showEvenement').textContent= data.evenement_titre  || '—';
                document.getElementById('showStatut').innerHTML     = statutBadges[data.statut] || data.statut;
                document.getElementById('showRef').innerHTML        =
                    `<code style="background:var(--glass);color:var(--text);padding:3px 8px;border-radius:6px;">PART-${String(data.id_participation).padStart(4,'0')}</code>`;

                document.getElementById('showSpinner').style.display = 'none';
                document.getElementById('showContent').style.display = 'block';
            })
            .catch(() => {
                document.getElementById('showSpinner').style.display = 'none';
                document.getElementById('showError').style.display   = 'block';
            });
    }

    /* Bouton "Modifier" depuis Show → ouvre Edit */
    document.getElementById('showToEditBtn').addEventListener('click', () => {
        bootstrap.Modal.getInstance(document.getElementById('showModal')).hide();
        setTimeout(() => openEditModal(currentShowId), 350);
    });

    /* ════════════════════════════════
       MODAL MODIFIER
       Soumet vers update.php?id=X (inchangé)
    ════════════════════════════════ */
    function openEditModal(id) {
        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        document.getElementById('editSpinner').style.display  = 'flex';
        document.getElementById('editForm').style.display     = 'none';
        document.getElementById('editError').style.display    = 'none';
        document.getElementById('editModalIdLabel').textContent = `— #${id}`;
        modal.show();

        fetchParticipation(id)
            .then(data => {
                if (!data || !data.id_participation) throw new Error();

                // Pointer le form vers update.php (fichier original inchangé)
                document.getElementById('editForm').action = `update.php?id=${data.id_participation}<?= $filtreEvenementId ? '&id_event='.$filtreEvenementId : '' ?>`;

                // Résumé du participant dans le modal
                document.getElementById('editInfoCard').innerHTML =
                    `<p><strong>${data.nom_complet}</strong> — ${data.email}</p>` +
                    `<p>Événement : <strong>${data.evenement_titre || '—'}</strong></p>` +
                    `<p>Statut actuel : ${statutBadges[data.statut] || data.statut}</p>`;

                // Pré-sélectionner le statut actuel
                document.getElementById('editStatut').value = data.statut || 'en_attente';

                document.getElementById('editSpinner').style.display = 'none';
                document.getElementById('editForm').style.display    = 'block';
            })
            .catch(() => {
                document.getElementById('editSpinner').style.display = 'none';
                document.getElementById('editError').style.display   = 'block';
            });
    }
</script>
</body>
</html>