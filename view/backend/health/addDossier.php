<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = new DossierMedical(
        null, 1, null, null,
        $_POST['groupe_sanguin'] ?? null,
        (float)($_POST['poids'] ?? 0),
        (float)($_POST['taille'] ?? 0),
        null,
        $_POST['regime_special'] ?? null,
        $_POST['notes_medecin'] ?? null,
        $_POST['allergie'] ?? null,
        $_POST['gravite_allergie'] ?? null,
        $_POST['maladies'] ?? null,
        $_POST['traitement'] ?? null,
        $_POST['medecin'] ?? null,
        $_POST['contact_en_cas_durgence'] ?? null
    );
    $ctrl->add($d);
    header('Location: health-admin.html?success=dossier_added');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">📋 Ajouter un Dossier Médical</h1>
            <form id="dossierForm" method="POST" class="grid grid-cols-2 gap-6" onsubmit="return validateDossierForm()">
                <div><label class="block text-sm">Poids (kg)</label><input type="number" step="0.1" name="poids" id="poids" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div><label class="block text-sm">Taille (cm)</label><input type="number" step="0.1" name="taille" id="taille" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div><label class="block text-sm">Groupe sanguin</label><input type="text" name="groupe_sanguin" id="groupe" class="w-full rounded-2xl border border-[#77B5FE] p-4"></div>
                <div><label class="block text-sm">Allergie</label><input type="text" name="allergie" id="allergie" class="w-full rounded-2xl border border-[#77B5FE] p-4"></div>
                <div class="col-span-2"><button type="submit" class="w-full bg-[#BA5BED] text-white py-5 rounded-2xl text-xl">Enregistrer Dossier</button></div>
            </form>
        </div>
    </main>
    <script>
        function validateDossierForm() {
            const poids = parseFloat(document.getElementById('poids').value);
            const taille = parseFloat(document.getElementById('taille').value);
            if (poids <= 0) { alert('Poids doit être supérieur à 0'); return false; }
            if (taille <= 0) { alert('Taille doit être supérieure à 0'); return false; }
            return true;
        }
    </script>
</body>
</html>