<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$dossier = $id ? $ctrl->show($id) : null;
$error = '';
$success = '';

if (!$dossier) {
    $error = "Dossier non trouvé.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    try {
        $d = new DossierMedical(
            $id, $dossier['id_utilisateur'], $dossier['date_creation'], null,
            $_POST['groupe_sanguin'] ?? null, 
            floatval($_POST['poids'] ?? 0), 
            floatval($_POST['taille'] ?? 0), 
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
        $ctrl->update($d, $id);
        $success = "✅ Dossier médical mis à jour avec succès!";
        $_POST = [];
        // Refresh the dossier
        $dossier = $ctrl->show($id);
    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">✏️ Modifier le Dossier #<?= htmlspecialchars($id ?? '') ?></h1>
            
            <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <?php if ($dossier): ?>
            <form id="updateForm" method="POST" class="grid grid-cols-2 gap-6" onsubmit="return validateDossierForm()">
                <div>
                    <label class="block text-sm mb-2 font-semibold">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="">— Sélectionner —</option>
                        <option value="O+" <?= ($dossier['groupe_sanguin'] == 'O+') ? 'selected' : '' ?>>O+</option>
                        <option value="O-" <?= ($dossier['groupe_sanguin'] == 'O-') ? 'selected' : '' ?>>O-</option>
                        <option value="A+" <?= ($dossier['groupe_sanguin'] == 'A+') ? 'selected' : '' ?>>A+</option>
                        <option value="A-" <?= ($dossier['groupe_sanguin'] == 'A-') ? 'selected' : '' ?>>A-</option>
                        <option value="B+" <?= ($dossier['groupe_sanguin'] == 'B+') ? 'selected' : '' ?>>B+</option>
                        <option value="B-" <?= ($dossier['groupe_sanguin'] == 'B-') ? 'selected' : '' ?>>B-</option>
                        <option value="AB+" <?= ($dossier['groupe_sanguin'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
                        <option value="AB-" <?= ($dossier['groupe_sanguin'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
                    </select>
                </div>
                <div><label class="block text-sm mb-2 font-semibold">Poids (kg)</label><input type="number" step="0.1" name="poids" id="poids" value="<?= htmlspecialchars($dossier['poids'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div><label class="block text-sm mb-2 font-semibold">Taille (cm)</label><input type="number" step="0.1" name="taille" id="taille" value="<?= htmlspecialchars($dossier['taille'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required></div>
                <div><label class="block text-sm mb-2 font-semibold">Régime spécial</label><input type="text" name="regime_special" value="<?= htmlspecialchars($dossier['regime_special'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Végétarien"></div>

                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Allergies (description)</label>
                    <textarea name="allergie" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Arachides, lactose, pollen..."><?= htmlspecialchars($dossier['allergie'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm mb-2 font-semibold">Gravité de l'allergie</label>
                    <select name="gravite_allergie" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="">— Sélectionner —</option>
                        <option value="légère" <?= ($dossier['gravite_allergie'] == 'légère') ? 'selected' : '' ?>>Légère</option>
                        <option value="modérée" <?= ($dossier['gravite_allergie'] == 'modérée') ? 'selected' : '' ?>>Modérée</option>
                        <option value="sévère" <?= ($dossier['gravite_allergie'] == 'sévère') ? 'selected' : '' ?>>Sévère</option>
                        <option value="anaphylactique" <?= ($dossier['gravite_allergie'] == 'anaphylactique') ? 'selected' : '' ?>>Anaphylactique</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Maladies chroniques</label>
                    <textarea name="maladies" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Diabète type 2, hypertension, asthme..."><?= htmlspecialchars($dossier['maladies'] ?? '') ?></textarea>
                </div>

                <div><label class="block text-sm mb-2 font-semibold">Traitement</label><input type="text" name="traitement" value="<?= htmlspecialchars($dossier['traitement'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="Médicaments actuels"></div>
                <div><label class="block text-sm mb-2 font-semibold">Médecin</label><input type="text" name="medecin" value="<?= htmlspecialchars($dossier['medecin'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="Dr. Dupont"></div>
                <div class="col-span-2"><label class="block text-sm mb-2 font-semibold">Contact en cas d'urgence</label><input type="text" name="contact_en_cas_durgence" value="<?= htmlspecialchars($dossier['contact_en_cas_durgence'] ?? '') ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="+33 6 XX XX XX XX"></div>
                <div class="col-span-2"><label class="block text-sm mb-2 font-semibold">Notes du médecin</label><textarea name="notes_medecin" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="Observations médicales..."><?= htmlspecialchars($dossier['notes_medecin'] ?? '') ?></textarea></div>

                <div class="col-span-2"><button type="submit" class="w-full bg-[#BA5BED] text-white py-5 rounded-2xl text-xl font-semibold">💾 Mettre à jour le dossier médical</button></div>
            </form>
            <?php else: ?>
            <p class="text-red-600 text-lg">Le dossier demandé n'existe pas.</p>
            <?php endif; ?>
            <a href="../modules/health-admin.html" class="mt-6 inline-block text-[#013220] hover:underline">← Retour au tableau de bord</a>
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