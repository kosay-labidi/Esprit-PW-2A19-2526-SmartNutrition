<?php
require_once '../../../config.php';
require_once '../../../Controller/regime.controller.php';   // Changez si le nom de votre controller est différent

$ctrl = new RegimeController();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_regime           = trim($_POST['nom_regime'] ?? '');
    $description          = trim($_POST['description'] ?? '');
    $type_regime          = $_POST['type_regime'] ?? 'alimentaire';
    $niveau_difficulte    = $_POST['niveau_difficulte'] ?? 'modere';
    $aliments_interdits   = trim($_POST['aliments_interdits'] ?? '[]');
    $aliments_recommandes = trim($_POST['aliments_recommandes'] ?? '[]');
    $apport_calorique     = !empty($_POST['apport_calorique']) ? (float)$_POST['apport_calorique'] : null;

    // Validation simple
    if (empty($nom_regime)) {
        $error = "Le nom du régime est obligatoire.";
    } else {
        // Convertir les champs JSON en vrai JSON valide
        $aliments_interdits   = json_encode(json_decode($aliments_interdits, true) ?: []);
        $aliments_recommandes = json_encode(json_decode($aliments_recommandes, true) ?: []);

        $regime = new Regime(
            null,
            $nom_regime,
            strtolower(str_replace(' ', '-', $nom_regime)), // slug simple
            $description,
            $type_regime,
            $niveau_difficulte,
            $aliments_interdits,
            $aliments_recommandes,
            $apport_calorique
        );

        $result = $ctrl->add($regime);   // Votre controller doit avoir la méthode add()

        if ($result) {
            header('Location: health.html?msg=regime_added');
            exit;
        } else {
            $error = "Erreur lors de l'ajout du régime dans la base de données.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Régime - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        :root {--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}
        body { background: var(--sable); }
        .glass {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            box-shadow: 0 15px 40px rgba(1,50,32,0.18);
        }
        .health-input, select { 
            width: 100%; padding: 14px 18px; border: 2px solid #ddd; 
            border-radius: 16px; font-size: 1rem; 
        }
        .health-input:focus, select:focus { border-color: var(--bleu); outline: none; }
    </style>
</head>
<body>
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12">
            <h1 class="text-3xl font-bold text-[#013220] mb-2">🥗 Ajouter un Nouveau Régime</h1>
            <p class="text-gray-600 mb-8">Ce régime sera disponible dans la base et pourra être lié à un dossier médical.</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <div>
                    <label class="block font-semibold mb-3 text-[#013220]">Nom du régime <span class="text-red-500">*</span></label>
                    <input type="text" name="nom_regime" class="health-input" required 
                           placeholder="ex. Régime sans gluten" value="<?= e($_POST['nom_regime'] ?? '') ?>">
                </div>

                <div>
                    <label class="block font-semibold mb-3 text-[#013220]">Description</label>
                    <textarea name="description" rows="4" class="health-input"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-3">Type de régime</label>
                        <select name="type_regime" class="health-input">
                            <option value="alimentaire" <?= ($_POST['type_regime'] ?? '') === 'alimentaire' ? 'selected' : '' ?>>Alimentaire</option>
                            <option value="medical" <?= ($_POST['type_regime'] ?? '') === 'medical' ? 'selected' : '' ?>>Médical</option>
                            <option value="perte_de_poids">Perte de poids</option>
                            <option value="prise_de_masse">Prise de masse</option>
                            <option value="sportif">Sportif</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-3">Niveau de difficulté</label>
                        <select name="niveau_difficulte" class="health-input">
                            <option value="facile">Facile</option>
                            <option value="modere" selected>Modéré</option>
                            <option value="avance">Avancé</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-3">Aliments interdits (JSON)</label>
                        <textarea name="aliments_interdits" rows="3" class="health-input" placeholder='["lait", "gluten", "arachides"]'><?= e($_POST['aliments_interdits'] ?? '[]') ?></textarea>
                    </div>
                    <div>
                        <label class="block font-semibold mb-3">Aliments recommandés (JSON)</label>
                        <textarea name="aliments_recommandes" rows="3" class="health-input" placeholder='["légumes", "fruits", "protéines"]'><?= e($_POST['aliments_recommandes'] ?? '[]') ?></textarea>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold mb-3">Apport calorique moyen (kcal/jour)</label>
                    <input type="number" step="0.1" name="apport_calorique" class="health-input" 
                           value="<?= e($_POST['apport_calorique'] ?? '') ?>" placeholder="ex. 1800">
                </div>

                <button type="submit" class="w-full bg-[#77B5FE] hover:bg-[#4a9ee8] text-white py-5 rounded-2xl font-semibold text-lg">
                    💾 Enregistrer le régime
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="health.html" class="text-[#013220] hover:underline">← Retour à Ma Santé</a>
            </div>
        </div>
    </main>
</body>
</html>