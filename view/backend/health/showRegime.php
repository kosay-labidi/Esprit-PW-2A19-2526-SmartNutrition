<?php
require_once '../../../config.php';
require_once '../../../Controller/regime.controller.php';
$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;
$regime = $id ? $ctrl->show($id) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Régime</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">Détails du Régime #<?php echo $id; ?></h1>
            <div class="grid grid-cols-2 gap-6 text-[#013220]">
                <p><strong>Nom :</strong> <?php echo htmlspecialchars($regime['nom_regime'] ?? '-'); ?></p>
                <p><strong>Slug :</strong> <?php echo htmlspecialchars($regime['slug'] ?? '-'); ?></p>
                <p><strong>Type :</strong> <?php echo $regime['type_regime'] ?? '-'; ?></p>
                <p><strong>Niveau :</strong> <?php echo $regime['niveau_difficulte'] ?? '-'; ?></p>
                <p class="col-span-2"><strong>Description :</strong> <?php echo htmlspecialchars($regime['description'] ?? '-'); ?></p>
            </div>
            <a href="health-admin.html" class="mt-8 inline-block bg-[#77B5FE] text-white px-8 py-4 rounded-2xl">Retour à la liste</a>
        </div>
    </main>
</body>
</html>