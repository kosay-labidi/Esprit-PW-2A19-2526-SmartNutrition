<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
$error = '';

if (!$id) {
    header('Location: health.html');
    exit;
}

$dossier = $ctrl->show($id);
if (!$dossier || !$ctrl->canAccessDossier($dossier)) {
    header('Location: ../dashboard.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    try {
        $updated = new DossierMedical(
            (int)$id,
            (int)($dossier['id_utilisateur'] ?? 0),
            isset($dossier['id_regime']) ? (int)$dossier['id_regime'] : null,
            $dossier['date_creation'] ?? null,
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

        $ctrl->update($updated, $id);
        // Respect return URL when present (only safe internal relative paths, block form pages)
        if (!empty($_GET['return'])) {
            $return = rawurldecode($_GET['return']);
            $blocked = preg_match('/updateRegime\.php|addRegime\.php|updateDossier\.php|addDossier\.php/i', $return);
            if (strpos($return, '/') === 0 && strpos($return, '//') !== 0 && !$blocked) {
                header('Location: ' . $return);
                exit;
            }
        }
        header('Location: ../dashboard.html?msg=updated');
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
    <style>
        :root { --vert:#013220; --sable:#CBBD93; --violet:#BA5BED; --bleu:#77B5FE; }
        body { background: #CBBD93; font-family: 'Segoe UI', sans-serif; padding: 40px 20px; }
        .glass { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 900px; margin: 0 auto; box-shadow: 0 15px 40px rgba(1,50,32,0.15); }
        h1 { color: var(--vert); margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-weight: 600; color: var(--vert); margin-bottom: 6px; font-size: 0.95rem; }
        input, select, textarea { padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; font-family: inherit; transition: border 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: var(--bleu); outline: none; }
        textarea { resize: vertical; min-height: 80px; }
        .btn { padding: 14px 28px; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-violet { background: linear-gradient(135deg, var(--violet), #9d4dd4); color: white; }
        .btn-violet:hover { transform: translateY(-2px); }
        .btn-back { background: var(--bleu); color: white; }
        .btn-back:hover { background: #4a9ee8; }
        .button-group { display: flex; gap: 15px; margin-top: 30px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="glass">
        <h1>✏️ Modifier le Dossier</h1>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="form-grid">
                <div class="form-group">
                    <label>Groupe sanguin</label>
                    <select name="groupe_sanguin">
                        <option value="">— Sélectionner —</option>
                        <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $gs): ?>
                        <option value="<?= $gs ?>" <?= ($dossier['groupe_sanguin'] ?? '') === $gs ? 'selected' : '' ?>><?= $gs ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Poids (kg)</label>
                    <input type="number" step="0.1" name="poids" value="<?= $dossier['poids'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Taille (cm)</label>
                    <input type="number" step="0.1" name="taille" value="<?= $dossier['taille'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>Régime spécial</label>
                    <input type="text" name="regime_special" value="<?= htmlspecialchars($dossier['regime_special'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Notes médecin</label>
                    <textarea name="notes_medecin"><?= htmlspecialchars($dossier['notes_medecin'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label>Allergies</label>
                    <textarea name="allergie"><?= htmlspecialchars($dossier['allergie'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Gravité allergie</label>
                    <select name="gravite_allergie">
                        <option value="">— Sélectionner —</option>
                        <?php foreach (['légère','modérée','sévère','anaphylactique'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($dossier['gravite_allergie'] ?? '') === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Médecin référent</label>
                    <input type="text" name="medecin" value="<?= htmlspecialchars($dossier['medecin'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Maladies chroniques</label>
                    <textarea name="maladies"><?= htmlspecialchars($dossier['maladies'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label>Traitement actuel</label>
                    <textarea name="traitement"><?= htmlspecialchars($dossier['traitement'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label>Contact urgence</label>
                    <input type="tel" name="contact_en_cas_durgence" value="<?= htmlspecialchars($dossier['contact_en_cas_durgence'] ?? '') ?>" placeholder="+33 6 12 34 56 78">
                </div>
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-violet">💾 Mettre à jour</button>
                <a href="../dashboard.html" class="btn btn-back">← Retour</a>
            </div>
        </form>
    </div>
    <script>
        function validateForm() {
            const poids = parseFloat(document.querySelector('[name="poids"]').value);
            const taille = parseFloat(document.querySelector('[name="taille"]').value);
            if (poids <= 0) { alert('⚠️ Veuillez entrer un poids valide (> 0)'); return false; }
            if (taille <= 0) { alert('⚠️ Veuillez entrer une taille valide (> 0)'); return false; }
            return true;
        }
    </script>
</body>
</html>
