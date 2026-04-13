<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../Controller/ParticipationController.php';

$participationC = new ParticipationController();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];
$data = $participationC->showParticipation($id);

if (!$data) {
    die("Participation non trouvée");
}

$success = false;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $participationC->updateParticipationStatut($id, $_POST['statut']);
    
    if ($result) {
        $success = true;
        // Recharger les données mises à jour
        $data = $participationC->showParticipation($id);
    } else {
        $error = "Erreur lors de la mise à jour du statut.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Participation - GaiaLumen</title>
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

        /* Card principale */
        .form-card {
            border-radius: var(--radius);
            border: none;
            box-shadow: var(--shadow);
            overflow: hidden;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
        }

        .card-header-custom {
            background: var(--green);
            padding: 25px 30px;
            border: none;
        }

        .card-header-custom h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--sand);
        }

        .card-header-custom h2 i {
            font-size: 2rem;
            color: var(--violet);
        }

        .card-header-custom p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
            color: var(--sand);
        }

        /* Corps du formulaire */
        .card-body-custom {
            padding: 35px;
        }

        /* Info card */
        .info-card {
            background: var(--surface);
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid var(--violet);
        }

        .info-card i {
            font-size: 1.8rem;
            color: var(--violet);
        }

        .info-card p {
            margin: 0;
            color: var(--muted);
        }

        .info-card strong {
            color: var(--violet);
        }

        /* Grille d'informations */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: var(--surface);
            border-radius: 12px;
            padding: 15px 18px;
            border-left: 3px solid var(--violet);
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-label i {
            color: var(--violet);
            font-size: 0.8rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
            word-break: break-word;
        }

        /* Form */
        .form-label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--violet);
        }

        .form-select {
            border-radius: 12px;
            border: 1px solid var(--glass);
            padding: 12px 15px;
            background-color: var(--surface);
            color: var(--text);
            transition: all var(--tr);
        }

        .form-select:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(91, 62, 150, 0.2);
            outline: none;
        }

        /* Alertes */
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
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

        /* Boutons */
        .btn-update {
            background: var(--violet);
            color: var(--sand);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-update:hover {
            background: var(--green);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: var(--sand);
        }

        .btn-secondary-custom {
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

        .btn-secondary-custom:hover {
            background: var(--violet);
            transform: translateY(-2px);
            color: var(--sand);
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
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="card-header-custom">
                    <h2>
                        <i class="fas fa-edit"></i>
                        Modifier la Participation
                    </h2>
                    <p>Modifiez le statut de la participation</p>
                </div>
                
                <div class="card-body-custom">
                    <!-- Info card -->
                    <div class="info-card">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <p>Participation n° <strong>#<?= htmlspecialchars($id) ?></strong></p>
                            <p class="small">ID: <?= htmlspecialchars($data['id_participation'] ?? $id) ?></p>
                        </div>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-custom">
                            <i class="fas fa-check-circle me-2"></i> ✅ Statut mis à jour avec succès !
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Informations du participant -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user"></i> NOM</div>
                            <p class="info-value"><?= htmlspecialchars($data['nom_complet']) ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> EMAIL</div>
                            <p class="info-value"><?= htmlspecialchars($data['email']) ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> TÉLÉPHONE</div>
                            <p class="info-value"><?= htmlspecialchars($data['telephone'] ?? 'Non renseigné') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-heart"></i> CENTRE D'INTÉRÊT</div>
                            <p class="info-value"><?= htmlspecialchars($data['centre_interet'] ?? 'Non spécifié') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> ÉVÉNEMENT</div>
                            <p class="info-value"><?= htmlspecialchars($data['evenement_titre'] ?? '—') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-flag-checkered"></i> STATUT ACTUEL</div>
                            <p class="info-value">
                                <span class="badge" style="background: var(--violet); color: white; padding: 4px 10px; border-radius: 20px;">
                                    <?= htmlspecialchars($data['statut']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-exchange-alt"></i> Nouveau statut
                            </label>
                            <select name="statut" class="form-select" required>
                                <option value="en_attente" <?= ($data['statut'] == 'en_attente') ? 'selected' : '' ?>>⏳ En attente</option>
                                <option value="confirmée" <?= ($data['statut'] == 'confirmée') ? 'selected' : '' ?>>✅ Confirmée</option>
                                <option value="annulée"   <?= ($data['statut'] == 'annulée') ? 'selected' : '' ?>>❌ Annulée</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="list.php" class="btn-secondary-custom">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn-update">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
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