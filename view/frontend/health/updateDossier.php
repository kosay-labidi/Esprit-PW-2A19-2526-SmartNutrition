<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$error = '';

if (!$id) {
    header('Location: health.html');
    exit;
}

$dossier = $ctrl->show($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    try {
        $updated = new DossierMedical(
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

        $ctrl->update($updated, $id);
        // Respect return URL when present (only safe internal relative paths, block form pages)
        if (!empty($_GET['return'])) {
            $return = rawurldecode($_GET['return']);
            $blocked = preg_match('/updateRegime\.php|addRegime\.php|updateDossier\.php|addDossier\.php/i', $return);
            if (strpos($return, '/project_v0/') === 0 && !$blocked) {
                header('Location: ' . $return);
                exit;
            }
        }
        header('Location: ../modules/health.html?msg=updated');
        exit;
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>body { background: #CBBD93; } .glass { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 900px; margin: 40px auto; }</style>
</head>
<body>
    <div class="glass">
        <h1>✏️ Modifier le Dossier</h1>
        <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

        <form method="POST" class="grid grid-cols-2 gap-6">
            <div><label>Poids (kg)</label><input type="number" step="0.1" name="poids" value="<?= $dossier['poids'] ?? '' ?>" class="health-input" required></div>
            <div><label>Taille (cm)</label><input type="number" step="0.1" name="taille" value="<?= $dossier['taille'] ?? '' ?>" class="health-input" required></div>
            <!-- Add other fields similarly -->
            <div class="col-span-2">
                <button type="submit" class="health-btn health-btn-violet w-full">💾 Mettre à jour</button>
            </div>
        </form>
        <a href="health.html">← Retour</a>
    </div>
</body>
</html>