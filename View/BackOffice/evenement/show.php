<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controller/EvenementController.php';

$evenementC = new EvenementController();
if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$data = $evenementC->showEvenement($_GET['id']);
if (!$data) {
    die("Événement non trouvé");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails de l'Événement - GaiaLumen</title>
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
        .header-section h2,
        .card-header-custom h3 {
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

        /* Card principale */
        .detail-card {
            border-radius: var(--radius);
            border: none;
            box-shadow: var(--shadow);
            overflow: hidden;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
        }

        /* En-tête de la carte */
        .card-header-custom {
            background: var(--green);
            padding: 25px 30px;
            border: none;
            position: relative;
        }

        .card-header-custom h3 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--sand);
        }

        .card-header-custom h3 i {
            font-size: 2rem;
            color: var(--violet);
        }

        .event-id {
            position: absolute;
            top: 20px;
            right: 30px;
            background: var(--glass);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--sand);
        }

        /* Corps de la carte */
        .card-body-custom {
            padding: 35px;
        }

        /* Grille d'informations */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-item {
            background: var(--surface);
            border-radius: 15px;
            padding: 18px 20px;
            transition: all var(--tr);
            border-left: 4px solid var(--violet);
        }

        .info-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .info-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            color: var(--violet);
            font-size: 1rem;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        /* Description */
        .description-section {
            background: var(--surface);
            border-radius: 15px;
            padding: 20px;
            margin-top: 10px;
        }

        .description-section h5 {
            color: var(--green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .description-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            line-height: 1.6;
            color: var(--text);
            border: 1px solid var(--glass);
        }

        /* Badge type */
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .badge-repas { background: var(--sand); color: var(--green); }
        .badge-sport { background: var(--green); color: var(--sand); }
        .badge-medical { background: var(--blue); color: white; }
        .badge-atelier { background: var(--violet); color: white; }

        /* Boutons */
        .btn-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .btn-back {
            background: var(--glass);
            border: 1px solid var(--sand);
            color: var(--sand);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: var(--violet);
            border-color: var(--violet);
            transform: translateY(-2px);
            color: var(--sand);
        }

        .btn-edit {
            background: var(--green);
            color: var(--sand);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .btn-edit:hover {
            background: var(--violet);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--sand);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            color: white;
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
            <a href="list.php" class="text-white" style="text-decoration: none; opacity: 0.9;">
                <i class="fas fa-calendar-alt me-1"></i> Événements
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="header-section">
        <h2>
            <i class="fas fa-info-circle"></i>
            Détails de l'Événement
        </h2>
    </div>

    <div class="detail-card">
        <div class="card-header-custom">
            <h3>
                <i class="fas fa-calendar-star"></i>
                <?= htmlspecialchars($data['titre']) ?>
            </h3>
            <div class="event-id">
                <i class="fas fa-hashtag"></i> ID: <?= htmlspecialchars($_GET['id']) ?>
            </div>
        </div>
        
        <div class="card-body-custom">
            <!-- Grille d'informations -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-calendar-day"></i> DATE
                    </div>
                    <p class="info-value">
                        <i class="far fa-calendar-alt me-2"></i>
                        <?= date('d/m/Y', strtotime(htmlspecialchars($data['date']))) ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-clock"></i> HEURE
                    </div>
                    <p class="info-value">
                        <i class="far fa-clock me-2"></i>
                        <?= htmlspecialchars($data['heure']) ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-tag"></i> TYPE
                    </div>
                    <p class="info-value">
                        <?php
                        $typeIcon = '';
                        $badgeClass = 'badge-type';
                        switch($data['type']) {
                            case 'repas': $typeIcon = '🍲'; $badgeClass .= ' badge-repas'; break;
                            case 'sport': $typeIcon = '🧘'; $badgeClass .= ' badge-sport'; break;
                            case 'medical': $typeIcon = '🥗'; $badgeClass .= ' badge-medical'; break;
                            case 'atelier': $typeIcon = '🌱'; $badgeClass .= ' badge-atelier'; break;
                            default: $typeIcon = '📌'; $badgeClass .= ' bg-secondary';
                        }
                        ?>
                        <span class="<?= $badgeClass ?>">
                            <?= $typeIcon ?> <?= htmlspecialchars($data['type']) ?>
                        </span>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-qrcode"></i> RÉFÉRENCE
                    </div>
                    <p class="info-value">
                        <code style="background: var(--glass); color: var(--text); padding: 4px 8px; border-radius: 8px;">EVT-<?= str_pad(htmlspecialchars($_GET['id']), 4, '0', STR_PAD_LEFT) ?></code>
                    </p>
                </div>
            </div>
            
            <!-- Description -->
            <div class="description-section">
                <h5>
                    <i class="fas fa-align-left"></i>
                    Description
                </h5>
                <div class="description-content">
                    <?php if (!empty($data['description'])): ?>
                        <?= nl2br(htmlspecialchars($data['description'])) ?>
                    <?php else: ?>
                        <span class="text-muted">
                            <i class="fas fa-comment-slash me-2"></i>
                            Aucune description fournie pour cet événement.
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Boutons d'action -->
            <div class="btn-actions">
                <a href="list.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <div class="d-flex gap-2">
                    <a href="update.php?id=<?= htmlspecialchars($_GET['id']) ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="delete.php?id=<?= htmlspecialchars($_GET['id']) ?>" 
                       onclick="return confirm('⚠️ Supprimer définitivement cet événement ? Cette action est irréversible.')" 
                       class="btn-delete">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </a>
                </div>
            </div>
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