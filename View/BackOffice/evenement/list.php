<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controller/EvenementController.php';

$evenementC = new EvenementController();
$list = $evenementC->listEvenements();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Événements - GaiaLumen</title>
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
        .header-section h2 {
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
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--sand) !important;
        }

        .navbar-brand i {
            font-size: 1.8rem;
            margin-right: 10px;
            color: var(--violet);
        }

        /* Header section */
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

        /* Bouton ajouter */
        .btn-add {
            background: var(--green);
            color: var(--sand);
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Lato', sans-serif;
        }

        .btn-add:hover {
            background: var(--violet);
            color: var(--sand);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
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

        /* Badges types */
        .badge-type {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        .badge-repas { background: var(--sand); color: var(--green); }
        .badge-sport { background: var(--green); color: var(--sand); }
        .badge-medical { background: var(--blue); color: white; }
        .badge-atelier { background: var(--violet); color: white; }

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

        /* Message vide */
        .empty-row td {
            text-align: center;
            padding: 40px !important;
            color: var(--muted);
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
            <i class="fas fa-calendar-alt"></i>
            Liste des Événements
            <span class="counter-badge">
                <i class="fas fa-chart-simple me-1"></i><?= $list ? $list->rowCount() : 0 ?> événements
            </span>
        </h2>
        <a href="add.php" class="btn-add">
            <i class="fas fa-plus-circle"></i> Ajouter un événement
        </a>
    </div>

    <div class="table-container">
        <table class="table-custom table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag me-1"></i> ID</th>
                    <th><i class="fas fa-heading me-1"></i> Titre</th>
                    <th><i class="fas fa-calendar-day me-1"></i> Date</th>
                    <th><i class="fas fa-clock me-1"></i> Heure</th>
                    <th><i class="fas fa-tag me-1"></i> Type</th>
                    <th><i class="fas fa-cogs me-1"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $hasEvents = false;
                foreach ($list as $evenement): 
                    $hasEvents = true;
                    $badgeClass = 'badge-type';
                    switch($evenement['type']) {
                        case 'repas': $badgeClass .= ' badge-repas'; break;
                        case 'sport': $badgeClass .= ' badge-sport'; break;
                        case 'medical': $badgeClass .= ' badge-medical'; break;
                        case 'atelier': $badgeClass .= ' badge-atelier'; break;
                        default: $badgeClass .= ' bg-secondary';
                    }
                    
                    $typeIcon = '';
                    switch($evenement['type']) {
                        case 'repas': $typeIcon = '🍲'; break;
                        case 'sport': $typeIcon = '🧘'; break;
                        case 'medical': $typeIcon = '🥗'; break;
                        case 'atelier': $typeIcon = '🌱'; break;
                        default: $typeIcon = '📌';
                    }
                ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($evenement['id_event']) ?></strong></td>
                    <td><?= htmlspecialchars($evenement['titre']) ?></td>
                    <td><?= date('d/m/Y', strtotime(htmlspecialchars($evenement['date']))) ?></td>
                    <td><?= htmlspecialchars($evenement['heure']) ?></td>
                    <td>
                        <span class="<?= $badgeClass ?>">
                            <?= $typeIcon ?> <?= htmlspecialchars($evenement['type']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="show.php?id=<?= $evenement['id_event'] ?>" class="btn btn-sm btn-info btn-action" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="update.php?id=<?= $evenement['id_event'] ?>" class="btn btn-sm btn-warning btn-action" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?= $evenement['id_event'] ?>" 
                           onclick="return confirm('⚠️ Supprimer cet événement ? Cette action est irréversible.')" 
                           class="btn btn-sm btn-danger btn-action" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (!$hasEvents): ?>
                <tr class="empty-row">
                    <td colspan="6">
                        <i class="fas fa-calendar-times fa-3x mb-2 d-block" style="color: var(--muted);"></i>
                        Aucun événement trouvé<br>
                        <a href="add.php" class="btn btn-sm btn-add mt-2">+ Créer le premier événement</a>
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