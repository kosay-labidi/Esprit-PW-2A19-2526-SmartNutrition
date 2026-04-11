<?php
require_once '../../../config.php';
require_once '../../../Controller/regime.controller.php';
require_once '../../../Model/Regime.php';

$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;
$regime = $id ? $ctrl->show($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $regime) {
    $r = new Regime(null, $_POST['nom_regime'], $_POST['slug'], $_POST['description'],
                    $_POST['type_regime'], $_POST['niveau_difficulte'],
                    $_POST['aliments_interdits'], $_POST['aliments_recommandes'],
                    (float)$_POST['apport_calorique_moyen']);
    $ctrl->update($r, $id);
    header('Location: health-admin.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Régime</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">🏷️ Modifier Régime #<?php echo $id; ?></h1>
            <form id="regimeForm" method="POST" class="grid grid-cols-2 gap-6" onsubmit="return validateRegimeForm()">
                <div class="col-span-2">
                    <label class="block text-sm mb-1">Nom du régime</label>
                    <input type="text" name="nom_regime" id="nom" value="<?php echo htmlspecialchars($regime['nom_regime'] ?? ''); ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Slug</label>
                    <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($regime['slug'] ?? ''); ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm mb-1">Description</label>
                    <textarea name="description" id="description" rows="4" class="w-full rounded-2xl border border-[#77B5FE] p-4"><?php echo htmlspecialchars($regime['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-span-2">
                    <button type="submit" class="w-full bg-[#77B5FE] text-white py-5 rounded-2xl text-xl">Mettre à jour Régime</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function validateRegimeForm() {
            if (document.getElementById('nom').value.trim() === '') {
                alert('Le nom du régime est obligatoire');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>