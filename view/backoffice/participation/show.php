<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../controller/ParticipationController.php';

$participationC = new ParticipationController();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$data = $participationC->showParticipation($_GET['id']);

if (!$data) {
    die("Participation non trouvée");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Participation - GaiaLumen</title>
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
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--sand) !important;
            transition: all var(--tr);
        }

        .navbar-brand i {
            font-size: 1.8rem;
            margin-right: 10px;
            color: var(--violet);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        /* Theme toggle */
        .theme-toggle {
            background: var(--glass);
            border: 1px solid var(--sand);
            border-radius: 50px;
            padding: 8px 20px;
            color: var(--sand);
            cursor: pointer;
            transition: all var(--tr);
            font-weight: 500;
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
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-section h2 {
            color: var(--text);
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 2rem;
        }

        .header-section h2 i {
            font-size: 2.2rem;
            color: var(--violet);
        }

        /* Card principale */
        .detail-card {
            border-radius: var(--radius);
            border: none;
            box-shadow: var(--shadow);
            overflow: hidden;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(91, 62, 150, 0.1);
        }

        /* En-tête de la carte */
        .card-header-custom {
            background: linear-gradient(135deg, var(--green), var(--green));
            padding: 25px 30px;
            border: none;
            position: relative;
            border-bottom: 1px solid rgba(91, 62, 150, 0.2);
        }

        .card-header-custom h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--sand);
        }

        .card-header-custom h3 i {
            font-size: 2.2rem;
            color: var(--violet);
        }

        .participation-id {
            position: absolute;
            top: 20px;
            right: 30px;
            background: var(--glass);
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--sand);
            backdrop-filter: blur(10px);
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
            margin-bottom: 35px;
        }

        .info-item {
            background: var(--surface);
            border-radius: 14px;
            padding: 20px 22px;
            transition: all var(--tr);
            border-left: 4px solid var(--violet);
        }

        .info-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow);
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            color: var(--violet);
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
            word-break: break-word;
        }

        .info-value code {
            background: var(--glass);
            color: var(--text);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        /* Badge statut */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-success { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); }
        .badge-warning { background: rgba(241, 196, 15, 0.2); color: #f1c40f; border: 1px solid rgba(241, 196, 15, 0.3); }
        .badge-danger { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3); }

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
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back:hover {
            background: var(--violet);
            border-color: var(--violet);
            transform: translateY(-3px);
            color: var(--sand);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--violet), var(--blue));
            color: white;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(91, 62, 150, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(91, 62, 150, 0.5);
            color: white;
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #e74c3c;
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
        }

        /* Footer */
        .footer {
            background: var(--surface);
            color: var(--muted);
            padding: 25px 0;
            margin-top: 60px;
            text-align: center;
            border-top: 1px solid var(--glass);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .card-body-custom {
                padding: 25px;
            }
            
            .header-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-actions {
                flex-direction: column;
            }
            
            .btn-back, .btn-edit, .btn-delete {
                justify-content: center;
            }
            
            .participation-id {
                position: static;
                margin-top: 15px;
                display: inline-block;
            }
            
            .card-header-custom h3 {
                flex-direction: column;
                align-items: flex-start;
            }
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
                <i class="fas fa-list me-1"></i> Liste
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="header-section">
        <h2>
            <i class="fas fa-info-circle"></i>
            Détails de la Participation
        </h2>
    </div>

    <div class="detail-card">
        <div class="card-header-custom">
            <h3>
                <i class="fas fa-user-check"></i>
                <?= htmlspecialchars($data['nom_complet']) ?>
            </h3>
            <div class="participation-id">
                <i class="fas fa-hashtag"></i> ID: <?= htmlspecialchars($_GET['id']) ?>
            </div>
        </div>
        
        <div class="card-body-custom">
            <!-- Grille d'informations -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i> EMAIL
                    </div>
                    <p class="info-value">
                        <i class="far fa-envelope me-2"></i>
                        <?= htmlspecialchars($data['email']) ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-phone"></i> TÉLÉPHONE
                    </div>
                    <p class="info-value">
                        <i class="fas fa-phone-alt me-2"></i>
                        <?= htmlspecialchars($data['telephone'] ?? 'Non renseigné') ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-heart"></i> CENTRE D'INTÉRÊT
                    </div>
                    <p class="info-value">
                        <i class="fas fa-seedling me-2"></i>
                        <?= htmlspecialchars($data['centre_interet'] ?? 'Non spécifié') ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-calendar-alt"></i> ÉVÉNEMENT
                    </div>
                    <p class="info-value">
                        <i class="fas fa-calendar-check me-2"></i>
                        <?= htmlspecialchars($data['evenement_titre'] ?? '—') ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-flag-checkered"></i> STATUT
                    </div>
                    <p class="info-value">
                        <?php
                        $badgeClass = '';
                        $badgeIcon = '';
                        if ($data['statut'] == 'confirmée') {
                            $badgeClass = 'badge-success';
                            $badgeIcon = '<i class="fas fa-check-circle"></i>';
                        } elseif ($data['statut'] == 'annulée') {
                            $badgeClass = 'badge-danger';
                            $badgeIcon = '<i class="fas fa-times-circle"></i>';
                        } else {
                            $badgeClass = 'badge-warning';
                            $badgeIcon = '<i class="fas fa-clock"></i>';
                        }
                        ?>
                        <span class="badge <?= $badgeClass ?>">
                            <?= $badgeIcon ?> <?= ucfirst(htmlspecialchars($data['statut'])) ?>
                        </span>
                    </p>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-qrcode"></i> RÉFÉRENCE
                    </div>
                    <p class="info-value">
                        <code>PART-<?= str_pad(htmlspecialchars($_GET['id']), 4, '0', STR_PAD_LEFT) ?></code>
                    </p>
                </div>
            </div>
            
            <!-- Boutons d'action -->
            <div class="btn-actions">
                <a href="list.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <div class="d-flex gap-2">
                    <a href="update.php?id=<?= htmlspecialchars($_GET['id']) ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="delete.php?id=<?= htmlspecialchars($_GET['id']) ?>" 
                       onclick="return confirm('⚠️ Supprimer définitivement cette participation ? Cette action est irréversible.')" 
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