<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: health.html');
    exit;
}

$dossier = $ctrl->show($id);   // Assurez-vous que show() retourne un tableau associatif

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    $updatedDossier = new DossierMedical(
        null, 1, null, null,
        $_POST['groupe_sanguin'] ?? null,
        (float)$_POST['poids'],
        (float)$_POST['taille'],
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

    $ctrl->update($updatedDossier, $id);
    header('Location: health.html?msg=dossier_updated');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        :root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}
        body { background: var(--sable); }
        .glass {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(1,50,32,0.15);
        }
    </style>
</head>
<body>
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">✏️ Modifier le Dossier</h1>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-semibold mb-2">Poids (kg)</label>
                    <input type="number" step="0.1" name="poids" value="<?= htmlspecialchars($dossier['poids'] ?? '') ?>" 
                           class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>
                <div>
                    <label class="block font-semibold mb-2">Taille (cm)</label>
                    <input type="number" step="0.1" name="taille" value="<?= htmlspecialchars($dossier['taille'] ?? '') ?>" 
                           class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>
                <div>
                    <label class="block font-semibold mb-2">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="">— Sélectionner —</option>
                        <?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($dossier['groupe_sanguin'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold mb-2">Allergies</label>
                    <textarea name="allergie" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4"><?= htmlspecialchars($dossier['allergie'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold mb-2">Maladies</label>
                    <textarea name="maladies" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4"><?= htmlspecialchars($dossier['maladies'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2 mt-8">
                    <button type="submit" class="w-full bg-[#BA5BED] hover:bg-[#9d4dd4] text-white py-5 rounded-2xl font-semibold text-lg">
                        💾 Mettre à jour le dossier
                    </button>
                </div>
            </form>

            <a href="health.html" class="mt-6 inline-block text-[#013220] hover:underline">← Retour</a>
        </div>
    </main>
</body>
</html>