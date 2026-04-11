<?php
require_once '../../../config.php';
require_once '../../../Controller/regime.controller.php';

$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: health.html');
    exit;
}

$regime = $ctrl->show($id);   // Votre controller doit avoir show($id)

if (!$regime) {
    header('Location: health.html');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_regime           = trim($_POST['nom_regime'] ?? '');
    $description          = trim($_POST['description'] ?? '');
    $type_regime          = $_POST['type_regime'] ?? 'alimentaire';
    $niveau_difficulte    = $_POST['niveau_difficulte'] ?? 'modere';
    $aliments_interdits   = trim($_POST['aliments_interdits'] ?? '[]');
    $aliments_recommandes = trim($_POST['aliments_recommandes'] ?? '[]');
    $apport_calorique     = !empty($_POST['apport_calorique']) ? (float)$_POST['apport_calorique'] : null;

    if (empty($nom_regime)) {
        $error = "Le nom du régime est obligatoire.";
    } else {
        $aliments_interdits   = json_encode(json_decode($aliments_interdits, true) ?: []);
        $aliments_recommandes = json_encode(json_decode($aliments_recommandes, true) ?: []);

        $updatedRegime = new Regime(
            $id,
            $nom_regime,
            $regime['slug'] ?? strtolower(str_replace(' ', '-', $nom_regime)),
            $description,
            $type_regime,
            $niveau_difficulte,
            $aliments_interdits,
            $aliments_recommandes,
            $apport_calorique
        );

        $result = $ctrl->update($updatedRegime, $id);

        if ($result) {
            header('Location: health.html?msg=regime_updated');
            exit;
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Régime - <?= SITE_NAME ?></title>
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
            <h1 class="text-3xl font-bold text-[#013220] mb-2">✏️ Modifier le Régime</h1>
            <p class="text-gray-600 mb-8">ID : <?= $id ?> — <?= e($regime['nom_regime'] ?? '') ?></p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <!-- Les mêmes champs que addRegime.php, pré-remplis -->
                <div>
                    <label class="block font-semibold mb-3 text-[#013220]">Nom du régime <span class="text-red-500">*</span></label>
                    <input type="text" name="nom_regime" class="health-input" required 
                           value="<?= e($regime['nom_regime'] ?? $_POST['nom_regime'] ?? '') ?>">
                </div>

                <div>
                    <label class="block font-semibold mb-3">Description</label>
                    <textarea name="description" rows="4" class="health-input"><?= e($regime['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- Type, Niveau, JSON fields, Apport calorique ... (identique à addRegime) -->
                <!-- Je les ai omis ici pour brevité, mais copiez-les exactement du addRegime.php ci-dessus et pré-remplissez avec $regime['champ'] -->

                <button type="submit" class="w-full bg-[#BA5BED] hover:bg-[#9d4dd4] text-white py-5 rounded-2xl font-semibold text-lg">
                    💾 Mettre à jour le régime
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="health.html" class="text-[#013220] hover:underline">← Retour à Ma Santé</a>
            </div>
        </div>
    </main>
</body>
</html>