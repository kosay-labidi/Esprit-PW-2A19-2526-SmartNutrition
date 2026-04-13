<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../Controller/ParticipationController.php';

$participationC = new ParticipationController();
$list = $participationC->listParticipations();

$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'deleted') {
        $success_message = '✅ Participation supprimée avec succès !';
    } elseif ($_GET['success'] == 'updated') {
        $success_message = '✅ Statut mis à jour avec succès !';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'missing_id') {
        $error_message = '❌ ID de participation manquant.';
    }
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
        /* ---------- PALETTE EXACTE ---------- */
        :root {
            --green: #1F3D2B;
            --sand: #F2E8CF;
            --violet: #5B3E96;
            --blue: #3A86C4;
            --bg: #0a1a10;
            --surface: #0f2318;
            --text: #F2E8CF;
            --muted: #a8b8a0;
            --card-bg: rgba(15, 35, 24, 0.72);
            --glass: rgba(31, 61, 43, 0.45);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            --radius: 18px;
            --tr: 0.4s cubic-bezier(.4, 0, .2, 1);
        }

        /* Mode clair */
        [data-theme="light"] {
            --green: #2d5a3f;
            --bg: #F2E8CF;
            --surface: #ffffff;
            --text: #1F3D2B;
            --muted: #5a6b5a;
            --card-bg: rgba(255, 255, 255, 0.9);
            --glass: rgba(45, 90, 63, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            transition: all var(--tr);
            color: var(--text);
        }

        /* Titres */
        h1, h2, h3, h4, h5, h6,
        .navbar-brand,
        .main-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
        }

        /* Navbar */
        .navbar-custom {
            background: var(--green);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--sand) !important;
        }

        .navbar-brand i {
            font-size: 1.8rem;
            margin-right: 10px;
            color: var(--violet);
        }

        /* Theme toggle */
        .theme-toggle {
            background: var(--glass);
            border: 1px solid var(--sand);
            border-radius: 50px;
            padding: 8px 16px;
            color: var(--sand);
            cursor: pointer;
            transition: all var(--tr);
        }

        .theme-toggle:hover {
            background: var(--violet);
            border-color: var(--violet);
            transform: scale(1.05);
        }

        /* Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-section h2 {
            color: var(--green);
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-section h2 i {
            font-size: 2rem;
            color: var(--violet);
        }

        /* Tableau */
        .table-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }

        .table-custom thead {
            background: var(--green);
            color: var(--sand);
        }

        .table-custom thead th {
            padding: 15px 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            font-family: 'Lato', sans-serif;
        }

        .table-custom tbody tr {
            transition: all var(--tr);
            border-bottom: 1px solid var(--glass);
        }

        .table-custom tbody tr:hover {
            background-color: var(--glass);
        }

        .table-custom tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            color: var(--text);
            font-family: 'Lato', sans-serif;
        }

        /* Badges statut */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        .badge-success { background: var(--green); color: var(--sand); }
        .badge-warning { background: var(--violet); color: white; }
        .badge-danger { background: #dc3545; color: white; }

        /* Boutons actions */
        .btn-action {
            border-radius: 8px;
            padding: 6px 10px;
            margin: 0 3px;
            transition: all var(--tr);
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        /* Alertes */
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            padding: 15px;
        }

        .alert-success {
            background: rgba(91, 62, 150, 0.2);
            border: 1px solid var(--violet);
            color: var(--sand);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #ff6b6b;
        }

        /* Footer */
        .footer {
            background: var(--surface);
            color: var(--muted);
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            border-top: 1px solid var(--glass);
        }

        /* Compteur */
        .counter-badge {
            background: var(--glass);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
            color: var(--violet);
            font-weight: 600;
        }

        /* Message vide */
        .empty-row td {
            text-align: center;
            padding: 40px !important;
            color: var(--muted);
        }
    </style>
</head>
<body data-theme="dark">

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="../index.html">
            <i class="fas fa-leaf"></i> GaiaLumen
        </a>
        <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun me-1"></i> Mode clair
            </button>
            <a href="../index.html" class="text-white" style="text-decoration: none; opacity: 0.9;">
                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="header-section">
        <h2>
            <i class="fas fa-users"></i>
            Liste des Participations
            <span class="counter-badge">
                <i class="fas fa-chart-simple me-1"></i><?= $list ? $list->rowCount() : 0 ?> participations
            </span>
        </h2>
        <a href="../index.html" class="btn btn-secondary-custom" style="background: var(--glass); border: 1px solid var(--sand); color: var(--sand); padding: 10px 20px; border-radius: 50px; text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard
        </a>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-custom"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-custom"><?= $error_message ?></div>
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
                <?php 
                $hasEvents = false;
                foreach ($list as $p): 
                    $hasEvents = true;
                    $badgeClass = '';
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
                    <td>
                        <span class="badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($p['statut']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="show.php?id=<?= $p['id_participation'] ?>" class="btn btn-sm btn-info btn-action" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="update.php?id=<?= $p['id_participation'] ?>" class="btn btn-sm btn-warning btn-action" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?= $p['id_participation'] ?>" 
                           onclick="return confirm('Supprimer cette participation ?')" 
                           class="btn btn-sm btn-danger btn-action" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (!$hasEvents): ?>
                <tr class="empty-row">
                    <td colspan="8">
                        <i class="fas fa-users-slash fa-3x mb-2 d-block" style="color: var(--muted);"></i>
                        Aucune participation trouvée
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; 2026 GaiaLumen - Héritage de Gaia. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        body.setAttribute('data-theme', savedTheme);
        updateButtonText(savedTheme);
    }

    themeToggle.addEventListener('click', () => {
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        body.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateButtonText(newTheme);
    });

    function updateButtonText(theme) {
        if (theme === 'dark') {
            themeToggle.innerHTML = '<i class="fas fa-sun me-1"></i> Mode clair';
        } else {
            themeToggle.innerHTML = '<i class="fas fa-moon me-1"></i> Mode sombre';
        }
    }
</script>
</body>
</html>