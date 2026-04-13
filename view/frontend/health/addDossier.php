<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = 1; // Change later when you have login

    try {
        $dossier = new DossierMedical(
            null,
            $id_utilisateur,
            null,
            null,
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

        $result = $ctrl->add($dossier);
        $success = "✅ Dossier médical enregistré avec succès!";
    } catch (Exception $e) {
        $error = "❌ Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Dossier Médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        :root { --vert:#013220; --sable:#CBBD93; --violet:#BA5BED; --bleu:#77B5FE; }
        body { background: var(--sable); }
        .glass { background: rgba(255,255,255,0.95); border-radius: 20px; padding: 40px; max-width: 900px; margin: 40px auto; box-shadow: 0 15px 40px rgba(1,50,32,0.15); }
        input, select, textarea { width: 100%; padding: 12px 16px; border: 2px solid var(--bleu); border-radius: 12px; font-size: 14px; margin-bottom: 12px; }
        input:focus, select:focus, textarea:focus { border-color: var(--violet); outline: none; }
        button { background: var(--violet); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; }
        button:hover { background: #9d4dd4; }
    </style>
</head>
<body>
    <div class="glass">
        <h1 style="color: var(--vert); margin-bottom: 20px;">➕ Ajouter un Dossier Médical</h1>
        
        <?php if ($error): ?><div style="background: #fee; color: #c33; padding: 15px; border-radius: 10px; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div style="background: #efe; color: #3c3; padding: 15px; border-radius: 10px; margin-bottom: 20px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Groupe sanguin</label>
                <select name="groupe_sanguin">
                    <option value="">— Sélectionner —</option>
                    <option value="O+">O+</option><option value="O-">O-</option>
                    <option value="A+">A+</option><option value="A-">A-</option>
                    <option value="B+">B+</option><option value="B-">B-</option>
                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                </select>
            </div>
            <div><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Poids (kg)</label><input type="number" step="0.1" name="poids" required></div>
            <div><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Taille (cm)</label><input type="number" step="0.1" name="taille" required></div>
            <div><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Régime spécial</label><input type="text" name="regime_special" placeholder="ex. Végétarien"></div>

            <div style="grid-column: 1 / -1;"><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Allergies</label><textarea name="allergie" rows="3"></textarea></div>
            <div><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Gravité allergie</label>
                <select name="gravite_allergie">
                    <option value="">— Sélectionner —</option>
                    <option value="légère">Légère</option><option value="modérée">Modérée</option>
                    <option value="sévère">Sévère</option><option value="anaphylactique">Anaphylactique</option>
                </select>
            </div>
            <div style="grid-column: 1 / -1;"><label style="font-weight: 600; color: var(--vert); display: block; margin-bottom: 6px;">Maladies chroniques</label><textarea name="maladies" rows="3"></textarea></div>

            <div style="grid-column: 1 / -1;">
                <button type="submit">💾 Enregistrer le dossier médical</button>
            </div>
        </form>
        <a href="../health.html" style="margin-top: 20px; display: inline-block; color: var(--vert); text-decoration: none;">← Retour</a>
    </div>
</body>
</html>