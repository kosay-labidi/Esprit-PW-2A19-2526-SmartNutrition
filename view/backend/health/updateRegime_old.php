<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../Model/Regime.php';

$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;
$regime = $id ? $ctrl->show($id) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $regime) {
    try {
        $alimentsInterdits = !empty($_POST['aliments_interdits']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_interdits']))) : [];
        $alimentsRecommandes = !empty($_POST['aliments_recommandes']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_recommandes']))) : [];
        
        $r = new Regime(
            $id,
            $_POST['nom_regime'],
            Regime::generateSlug($_POST['nom_regime']),
            $_POST['description'],
            $_POST['type_regime'],
            $_POST['niveau_difficulte'],
            json_encode($alimentsInterdits),
            json_encode($alimentsRecommandes),
            (float)$_POST['apport_calorique_moyen']
        );
        $ctrl->update($r, $id);
        header('Location: ../modules/health-admin.html?success=regime_updated');
        exit;
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
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
            <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form id="regimeForm" method="POST" class="grid grid-cols-2 gap-6" onsubmit="return validateRegimeForm()">
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Nom du régime <span class="text-red-500">*</span></label>
                    <input type="text" name="nom_regime" id="nom" value="<?php echo htmlspecialchars($regime['nom_regime'] ?? ''); ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                </div>

                <div>
                    <label class="block text-sm mb-2 font-semibold">Type de régime</label>
                    <select name="type_regime" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="alimentaire" <?php echo ($regime['type_regime'] ?? '') === 'alimentaire' ? 'selected' : ''; ?>>Alimentaire</option>
                        <option value="medical" <?php echo ($regime['type_regime'] ?? '') === 'medical' ? 'selected' : ''; ?>>Médical</option>
                        <option value="perte_de_poids" <?php echo ($regime['type_regime'] ?? '') === 'perte_de_poids' ? 'selected' : ''; ?>>Perte de poids</option>
                        <option value="prise_de_masse" <?php echo ($regime['type_regime'] ?? '') === 'prise_de_masse' ? 'selected' : ''; ?>>Prise de masse</option>
                        <option value="sportif" <?php echo ($regime['type_regime'] ?? '') === 'sportif' ? 'selected' : ''; ?>>Sportif</option>
                        <option value="autre" <?php echo ($regime['type_regime'] ?? '') === 'autre' ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-2 font-semibold">Niveau de difficulté</label>
                    <select name="niveau_difficulte" class="w-full rounded-2xl border border-[#77B5FE] p-4">
                        <option value="facile" <?php echo ($regime['niveau_difficulte'] ?? '') === 'facile' ? 'selected' : ''; ?>>Facile</option>
                        <option value="modere" <?php echo ($regime['niveau_difficulte'] ?? '') === 'modere' ? 'selected' : ''; ?>>Modéré</option>
                        <option value="avance" <?php echo ($regime['niveau_difficulte'] ?? '') === 'avance' ? 'selected' : ''; ?>>Avancé</option>
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Description</label>
                    <textarea name="description" id="description" rows="4" class="w-full rounded-2xl border border-[#77B5FE] p-4"><?php echo htmlspecialchars($regime['description'] ?? ''); ?></textarea>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Aliments interdits (JSON)</label>
                    <textarea name="aliments_interdits" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder='["lait", "gluten", "arachides"]'><?php echo htmlspecialchars($regime['aliments_interdits'] ?? '[]'); ?></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Aliments recommandés (JSON)</label>
                    <textarea name="aliments_recommandes" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder='["légumes", "fruits", "protéines"]'><?php echo htmlspecialchars($regime['aliments_recommandes'] ?? '[]'); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm mb-2 font-semibold">Apport calorique moyen (kcal/jour)</label>
                    <input type="number" step="0.1" name="apport_calorique_moyen" value="<?php echo htmlspecialchars($regime['apport_calorique_moyen'] ?? ''); ?>" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. 1800">
                </div>

                <div class="col-span-2">
                    <button type="submit" class="w-full bg-[#77B5FE] text-white py-5 rounded-2xl text-xl font-semibold">💾 Mettre à jour le régime</button>
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