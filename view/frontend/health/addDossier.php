<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';

$ctrl = new DossierMedicalController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['poids']) || empty($_POST['taille']) || $_POST['poids'] <= 0 || $_POST['taille'] <= 0) {
        $error = "Poids et taille doivent être supérieurs à 0.";
    } else {
        $dossier = new DossierMedical(
            null, 
            1, // user_id (à adapter selon votre système d'authentification)
            null, 
            null,
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

        $result = $ctrl->add($dossier);  // Assurez-vous que votre controller a une méthode add()
        
        if ($result) {
            header('Location: health.html?msg=dossier_added');
            exit;
        } else {
            $error = "Erreur lors de l'ajout du dossier.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        :root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}
        body { background: var(--sable); font-family: system-ui, sans-serif; }
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
            <h1 class="text-3xl font-bold text-[#013220] mb-8 flex items-center gap-3">
                ➕ Ajouter un nouveau dossier médical
            </h1>

            <?php if (isset($error)): ?>
                <p class="text-red-600 bg-red-100 p-4 rounded-2xl mb-6"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Biométrie -->
                <div class="md:col-span-2">
                    <h3 class="text-xl font-semibold text-[#013220] mb-4">Biométrie</h3>
                </div>
                <div>
                    <label class="block font-semibold mb-2 text-[#013220]">Groupe sanguin</label>
                    <select name="groupe_sanguin" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="">— Sélectionner —</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold mb-2 text-[#013220]">Poids (kg)</label>
                    <input type="number" step="0.1" name="poids" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>
                <div>
                    <label class="block font-semibold mb-2 text-[#013220]">Taille (cm)</label>
                    <input type="number" step="0.1" name="taille" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>

                <!-- Allergies & Maladies -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-xl font-semibold text-[#013220] mb-4">Allergies & Maladies</h3>
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold mb-2 text-[#013220]">Allergies</label>
                    <textarea name="allergie" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Arachides, lactose..."></textarea>
                </div>
                <div>
                    <label class="block font-semibold mb-2 text-[#013220]">Gravité</label>
                    <select name="gravite_allergie" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="">— Sélectionner —</option>
                        <option value="légère">Légère</option>
                        <option value="modérée">Modérée</option>
                        <option value="sévère">Sévère</option>
                        <option value="anaphylactique">Anaphylactique</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold mb-2 text-[#013220]">Maladies chroniques</label>
                    <textarea name="maladies" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Diabète, hypertension..."></textarea>
                </div>

                <div class="md:col-span-2 mt-8">
                    <button type="submit" class="w-full bg-[#BA5BED] hover:bg-[#9d4dd4] text-white py-5 rounded-2xl font-semibold text-lg transition">
                        💾 Enregistrer le dossier
                    </button>
                </div>
            </form>

            <a href="health.html" class="mt-6 inline-block text-[#013220] hover:underline">← Retour à Ma Santé</a>
        </div>
    </main>
</body>
</html>