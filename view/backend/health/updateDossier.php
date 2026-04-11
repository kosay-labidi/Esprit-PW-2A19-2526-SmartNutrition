<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$dossier = $id ? $ctrl->show($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    $d = new DossierMedical(null, 1, null, null,
        $_POST['groupe_sanguin'], (float)$_POST['poids'], (float)$_POST['taille'], null,
        $_POST['regime_special'], $_POST['notes_medecin'], $_POST['allergie'],
        $_POST['gravite_allergie'], $_POST['maladies'], $_POST['traitement'],
        $_POST['medecin'], $_POST['contact_en_cas_durgence']
    );
    $ctrl->update($d, $id);
    header('Location: health-admin.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">Modifier Dossier #<?php echo $id; ?></h1>
            <form id="updateForm" method="POST" class="grid grid-cols-2 gap-6" onsubmit="return validateDossierForm()">
                <div><label>Poids (kg)</label><input type="number" step="0.1" name="poids" value="<?php echo $dossier['poids'] ?? ''; ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div><label>Taille (cm)</label><input type="number" step="0.1" name="taille" value="<?php echo $dossier['taille'] ?? ''; ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div class="col-span-2"><button type="submit" class="w-full bg-[#BA5BED] text-white py-5 rounded-2xl">Mettre à jour</button></div>
            </form>
        </div>
    </main>
    <script>
        function validateDossierForm() {
            const poids = parseFloat(document.getElementById('poids').value);
            const taille = parseFloat(document.getElementById('taille').value);
            if (poids <= 0 || taille <= 0) {
                alert('Poids et taille doivent être supérieurs à 0');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>