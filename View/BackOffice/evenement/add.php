<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../Controller/EvenementController.php';
include __DIR__ . '/../../../Model/Evenement.php';

$evenementC = new EvenementController();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['titre']) && !empty($_POST['date']) && !empty($_POST['heure']) && !empty($_POST['type'])) {
        try {
            $date = new DateTime($_POST['date']);
            $evenement = new Evenement(
                null,
                $_POST['titre'],
                $_POST['description'] ?? '',
                $date,
                $_POST['heure'],
                $_POST['type']
            );
            $evenementC->addEvenement($evenement);
            header("Location: list.php?success=added");
            exit();
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Tous les champs obligatoires doivent être remplis.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Événement - GaiaLumen</title>
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
        .card-header-custom h2 {
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

        /* Card principale */
        .form-card {
            border-radius: var(--radius);
            border: none;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all var(--tr);
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
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid var(--glass);
            padding: 12px 15px;
            transition: all var(--tr);
            background-color: var(--surface);
            color: var(--text);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(91, 62, 150, 0.2);
            background-color: var(--surface);
            color: var(--text);
        }

        .form-control::placeholder {
            color: var(--muted);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Alertes */
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }

        /* Boutons */
        .btn-create {
            background: var(--green);
            color: var(--sand);
            padding: 14px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all var(--tr);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-create:hover {
            background: var(--violet);
            color: var(--sand);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary-custom {
            background: var(--glass);
            color: var(--sand);
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--sand);
        }

        .btn-secondary-custom:hover {
            background: var(--violet);
            transform: translateY(-2px);
            color: var(--sand);
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
                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
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
                        <i class="fas fa-calendar-plus"></i>
                        Ajouter un Événement
                    </h2>
                    <p>Créez un nouvel événement pour la communauté Gaia</p>
                </div>
                
                <div class="card-body-custom">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> Titre *
                            </label>
                            <input type="text" name="titre" class="form-control" placeholder="Ex: Atelier Bien-être Naturel" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i> Description
                            </label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Décrivez votre événement..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-calendar-day"></i> Date *
                                </label>
                                <input type="date" name="date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i> Heure *
                                </label>
                                <input type="time" name="heure" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-layer-group"></i> Type d'événement *
                            </label>
                            <select name="type" class="form-select" required>
                                <option value="" disabled selected>-- Sélectionner un type --</option>
                                <option value="repas">🍲 Repas / Cuisine</option>
                                <option value="sport">🧘 Sport / Yoga</option>
                                <option value="medical">🥗 Consultation Nutrition</option>
                                <option value="atelier">🌱 Atelier Pratique</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="list.php" class="btn-secondary-custom">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn-create">
                                <i class="fas fa-save"></i> Créer l'événement
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