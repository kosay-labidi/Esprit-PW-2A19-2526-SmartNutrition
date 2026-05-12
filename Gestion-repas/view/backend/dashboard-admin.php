<?php
/* dashboard-admin.php — Menu Back-Office GaiaLumen */
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard-admin.css">
</head>
<body>
    <aside class="sidebar">
        <a href="dashboard-admin.php" class="sidebar-logo">
            <svg width="30" height="30" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="28" stroke="#a78bfa" stroke-width="1.5" opacity=".6"/>
                <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
            </svg>
            <span>GaiaLumen</span>
        </a>
        <div class="sidebar-badge">Administration</div>
        <div class="sidebar-section">Module Repas</div>
        <a href="modules/repas_admin.html" class="nav-item active">
            <i class="fas fa-utensils"></i> Gestion Repas
        </a>
        <div class="sidebar-footer">Back-Office v1.0</div>
    </aside>
    <main class="admin-main">
        <h1 class="hf">Tableau de bord</h1>
        <p>Sélectionnez un module dans le menu de gauche.</p>
        <div class="modules-grid">
            <a href="modules/repas_admin.html" class="module-card">
                <i class="fas fa-utensils"></i>
                <h3>Gestion des Repas</h3>
                <p>Consultez et gérez tous les repas du système.</p>
            </a>
        </div>
    </main>
    <script src="js/dashboard-admin.js"></script>
</body>
</html>