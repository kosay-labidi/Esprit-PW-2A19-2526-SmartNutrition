<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';
$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$dossier = $id ? $ctrl->show($id) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Dossier</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">Détails du Dossier #<?php echo $id; ?></h1>
            <div class="grid grid-cols-2 gap-6">
                <p><strong>Poids :</strong> <?php echo $dossier['poids'] ?? '-'; ?> kg</p>
                <p><strong>Taille :</strong> <?php echo $dossier['taille'] ?? '-'; ?> cm</p>
                <p><strong>Groupe sanguin :</strong> <?php echo $dossier['groupe_sanguin'] ?? '-'; ?></p>
                <p><strong>Allergie :</strong> <?php echo htmlspecialchars($dossier['allergie'] ?? '-'); ?></p>
            </div>
            <a href="health-admin.html" class="mt-8 inline-block bg-[#77B5FE] text-white px-8 py-4 rounded-2xl">Retour à la liste</a>
        </div>
    </main>
</body>
</html>