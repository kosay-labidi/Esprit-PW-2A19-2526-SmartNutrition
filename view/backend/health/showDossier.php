<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$dossier = $id ? $ctrl->show($id) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;} body {background: var(--sable);} .glass { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 900px; margin: 40px auto; }</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <?php if ($dossier): ?>
            <h1 class="text-3xl font-bold text-[#013220] mb-8">👁️ Détails du Dossier #<?= htmlspecialchars($id) ?></h1>
            <div class="grid grid-cols-2 gap-6" style="margin-bottom: 30px;">
                <p><strong style="color: var(--vert);">ID :</strong> <?= htmlspecialchars($dossier['id_dossier']) ?></p>
                <p><strong style="color: var(--vert);">Poids :</strong> <?= htmlspecialchars($dossier['poids']) ?> kg</p>
                <p><strong style="color: var(--vert);">Taille :</strong> <?= htmlspecialchars($dossier['taille']) ?> cm</p>
                <p><strong style="color: var(--vert);">IMC :</strong> <?= number_format($dossier['imc'] ?? 0, 1) ?></p>
                <p><strong style="color: var(--vert);">Groupe sanguin :</strong> <?= htmlspecialchars($dossier['groupe_sanguin'] ?? '-') ?></p>
                <p><strong style="color: var(--vert);">Régime :</strong> <?= htmlspecialchars($dossier['regime_special'] ?? '-') ?></p>
                <p colspan="2" style="grid-column: 1 / -1;"><strong style="color: var(--vert);">Allergies :</strong> <?= htmlspecialchars($dossier['allergie'] ?? '-') ?></p>
                <p colspan="2" style="grid-column: 1 / -1;"><strong style="color: var(--vert);">Maladies :</strong> <?= htmlspecialchars($dossier['maladies'] ?? '-') ?></p>
            </div>
            <a href="../modules/health-admin.html" class="mt-8 inline-block bg-[#77B5FE] text-white px-8 py-4 rounded-2xl" style="text-decoration: none;">← Retour à la liste</a>
            <?php else: ?>
            <p style="color: red; font-size: 18px;">❌ Dossier non trouvé</p>
            <a href="../modules/health-admin.html" class="mt-4 inline-block text-[#013220]" style="text-decoration: none;">← Retour</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>