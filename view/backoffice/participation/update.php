<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../controller/ParticipationController.php';

$participationC = new ParticipationController();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];
$idEvent = isset($_GET['id_event']) ? (int)$_GET['id_event'] : null;
$retour = $idEvent ? "list.php?id_event={$idEvent}" : "list.php";

$data = $participationC->showParticipation($id);

if (!$data) {
    die("Participation non trouvée");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $participationC->updateParticipationStatut($id, $_POST['statut']);
    
    if ($result) {
        $sep = strpos($retour, '?') !== false ? '&' : '?';
        header("Location: {$retour}{$sep}success=updated");
        exit();
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
        .form-card { border-radius: var(--radius); border: 1px solid rgba(91, 62, 150, 0.1); box-shadow: var(--shadow); overflow: hidden; background: var(--card-bg); backdrop-filter: blur(12px); }
        .card-header-custom { background: var(--green); padding: 25px 30px; border-bottom: 1px solid rgba(91, 62, 150, 0.2); }
        .card-header-custom h2 { margin: 0; font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; gap: 12px; color: var(--sand); }
        .card-header-custom h2 i { font-size: 2rem; color: var(--violet); }
        .card-header-custom p { margin: 8px 0 0 0; opacity: 0.9; font-size: 0.9rem; color: var(--sand); }
        .card-body-custom { padding: 35px; }
        .info-card { background: var(--surface); border-radius: 14px; padding: 18px 22px; margin-bottom: 30px; display: flex; align-items: center; gap: 18px; border-left: 4px solid var(--violet); transition: all var(--tr); }
        .info-card:hover { transform: translateX(5px); }
        .info-card i { font-size: 2rem; color: var(--violet); }
        .info-card p { margin: 0; color: var(--muted); }
        .info-card strong { color: var(--violet); font-size: 1.1rem; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 35px; }
        .info-item { background: var(--surface); border-radius: 12px; padding: 18px 20px; border-left: 3px solid var(--violet); transition: all var(--tr); }
        .info-item:hover { transform: translateX(5px); }
        .info-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .info-label i { color: var(--violet); font-size: 0.8rem; }
        .info-value { font-size: 1rem; font-weight: 600; color: var(--text); margin: 0; word-break: break-word; }
        .badge-status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-en_attente { background: rgba(241,196,15,.2); color: #f1c40f; border: 1px solid rgba(241,196,15,.3); }
        .badge-confirmée { background: rgba(46,204,113,.2); color: #2ecc71; border: 1px solid rgba(46,204,113,.3); }
        .badge-annulée { background: rgba(231,76,60,.2); color: #e74c3c; border: 1px solid rgba(231,76,60,.3); }
        .form-label { font-weight: 600; color: var(--text); margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
        .form-label i { color: var(--violet); }
        .form-select { border-radius: 12px; border: 1px solid rgba(91,62,150,.2); padding: 12px 16px; background-color: var(--surface); color: var(--text); transition: all var(--tr); cursor: pointer; width: 100%; }
        .form-select:focus { border-color: var(--violet); box-shadow: 0 0 0 3px rgba(91,62,150,.2); outline: none; }
        .form-select option { background: var(--surface); color: var(--text); }
        .alert-custom { border-radius: 12px; border: none; padding: 15px 20px; margin-bottom: 25px; }
        .alert-danger { background: rgba(231,76,60,.15); border-left: 4px solid #e74c3c; color: #e74c3c; }
        .btn-update { background: linear-gradient(135deg, var(--violet), var(--blue)); color: white; padding: 14px 32px; border-radius: 50px; font-weight: 600; transition: all var(--tr); border: none; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(91,62,150,.3); cursor: pointer; }
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(91,62,150,.5); }
        .btn-secondary-custom { background: var(--glass); border: 1px solid var(--sand); color: var(--sand); padding: 14px 32px; border-radius: 50px; font-weight: 600; transition: all var(--tr); text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
        .btn-secondary-custom:hover { background: var(--violet); transform: translateY(-3px); color: var(--sand); border-color: var(--violet); }
        .footer { background: var(--surface); color: var(--muted); padding: 25px 0; margin-top: 60px; text-align: center; border-top: 1px solid var(--glass); }
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .card-body-custom { padding: 25px; }
            .header-section { flex-direction: column; align-items: stretch; }
            .btn-secondary-custom, .btn-update { justify-content: center; }
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
    <div class="header-section">
        <h2><i class="fas fa-edit"></i> Modifier la Participation</h2>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="card-header-custom">
                    <h2><i class="fas fa-user-check"></i> Participation n° <?= htmlspecialchars($id) ?></h2>
                    <p>Modifiez le statut de la participation</p>
                </div>

                <div class="card-body-custom">
                    <div class="info-card">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <p><strong>#<?= htmlspecialchars($data['id_participation'] ?? $id) ?></strong></p>
                            <p class="small">ID de la participation</p>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-custom">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user"></i> NOM COMPLET</div>
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
                                <span class="badge-status badge-<?= htmlspecialchars($data['statut']) ?>">
                                    <?php
                                    $statusText = ['en_attente'=>'⏳ En attente','confirmée'=>'✅ Confirmée','annulée'=>'❌ Annulée'];
                                    echo $statusText[$data['statut']] ?? $data['statut'];
                                    ?>
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
                                <option value="confirmée"  <?= ($data['statut'] == 'confirmée')  ? 'selected' : '' ?>>✅ Confirmée</option>
                                <option value="annulée"    <?= ($data['statut'] == 'annulée')    ? 'selected' : '' ?>>❌ Annulée</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= htmlspecialchars($retour) ?>" class="btn-secondary-custom">
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
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) { document.body.setAttribute('data-theme', savedTheme); updateButtonText(savedTheme); }
    themeToggle.addEventListener('click', () => {
        const t = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
        updateButtonText(t);
    });
    function updateButtonText(t) {
        themeToggle.innerHTML = t === 'dark'
            ? '<i class="fas fa-sun me-1"></i> Mode clair'
            : '<i class="fas fa-moon me-1"></i> Mode sombre';
    }
</script>
</body>
</html>