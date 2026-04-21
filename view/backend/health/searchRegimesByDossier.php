<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../controller/dossierMedical.controller.php';

$regimeC = new RegimeController();
$dossierC = new DossierMedicalController();

// Get all dossiers for the dropdown
$tousLesDossiers = $dossierC->list();

// Initialize variables
$list = null;
$idDossierSelected = null;
$tousLesRegimes = $regimeC->afficherTousRegimes();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['dossier']) && isset($_POST['search'])) {
        $idDossierSelected = $_POST['dossier'];
        $list = $regimeC->afficherRegimes($idDossierSelected);
    }
    
    // Handle regime association
    if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['id_regime']) && isset($_POST['id_dossier'])) {
        $regimeC->associerRegimeToDossier($_POST['id_dossier'], $_POST['id_regime']);
        $idDossierSelected = $_POST['id_dossier'];
        $list = $regimeC->afficherRegimes($idDossierSelected);
    }
    
    // Handle regime dissociation
    if (isset($_POST['action']) && $_POST['action'] === 'remove' && isset($_POST['id_regime']) && isset($_POST['id_dossier'])) {
        $regimeC->dissocierRegimeFromDossier($_POST['id_dossier'], $_POST['id_regime']);
        $idDossierSelected = $_POST['id_dossier'];
        $list = $regimeC->afficherRegimes($idDossierSelected);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de régimes par dossier médical</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        :root {
            --vert: #013220;
            --sable: #CBBD93;
            --violet: #BA5BED;
            --bleu: #77B5FE;
        }
        
        body {
            background: linear-gradient(135deg, var(--sable) 0%, #e8e0d5 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: var(--vert);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--vert);
            font-weight: 600;
            font-size: 1.05em;
        }
        
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        select:hover {
            border-color: var(--bleu);
        }
        
        select:focus {
            outline: none;
            border-color: var(--bleu);
            box-shadow: 0 0 5px rgba(119, 181, 254, 0.3);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        button {
            flex: 1;
            padding: 12px 24px;
            font-size: 1em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-search {
            background: var(--bleu);
            color: white;
        }
        
        .btn-search:hover {
            background: #5fa3f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(119, 181, 254, 0.3);
        }
        
        .btn-reset {
            background: #e0e0e0;
            color: var(--vert);
        }
        
        .btn-reset:hover {
            background: #d0d0d0;
        }
        
        .results-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .results-title {
            color: var(--vert);
            font-size: 1.8em;
            margin-bottom: 25px;
            font-weight: 700;
            border-bottom: 3px solid var(--bleu);
            padding-bottom: 12px;
        }
        
        .results-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .regime-item {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--bleu);
            transition: all 0.3s;
        }
        
        .regime-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .regime-name {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--vert);
            margin-bottom: 10px;
        }
        
        .regime-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            color: #555;
            font-size: 0.95em;
        }
        
        .info-box {
            background: white;
            padding: 12px;
            border-radius: 6px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--vert);
            margin-bottom: 4px;
        }
        
        .info-value {
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1em;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: var(--vert);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: #01522e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔍 Recherche de Régimes par Dossier Médical</h1>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <form method="POST">
                <div class="form-group">
                    <label for="dossier">Sélectionnez un dossier médical :</label>
                    <select name="dossier" id="dossier" required>
                        <option value="">-- Choisir un dossier --</option>
                        <?php foreach ($tousLesDossiers as $dossier) { ?>
                            <option value="<?php echo $dossier['id_dossier']; ?>" <?php echo ($idDossierSelected == $dossier['id_dossier']) ? 'selected' : ''; ?>>
                                Dossier #<?php echo $dossier['id_dossier']; ?> - 
                                Utilisateur #<?php echo $dossier['id_utilisateur']; ?> - 
                                (<?php echo $dossier['groupe_sanguin']; ?>)
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" name="search" class="btn-search">Rechercher</button>
                    <button type="reset" class="btn-reset">Réinitialiser</button>
                </div>
            </form>
        </div>

        <!-- Results Section -->
        <?php if (isset($list)) { ?>
            <div class="results-section">
                <h2 class="results-title">Régimes correspondants au dossier sélectionné :</h2>
                
                <?php if (is_array($list) && count($list) > 0) { ?>
                    <ul class="results-list">
                        <?php foreach ($list as $regime) { ?>
                            <li class="regime-item">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div class="regime-name">
                                            <?php echo htmlspecialchars($regime['nom_regime']); ?>
                                        </div>
                                        <div class="regime-info">
                                            <div class="info-box">
                                                <div class="info-label">Type de régime</div>
                                                <div class="info-value"><?php echo htmlspecialchars($regime['type_regime'] ?? '-'); ?></div>
                                            </div>
                                            <div class="info-box">
                                                <div class="info-label">Niveau de difficulté</div>
                                                <div class="info-value"><?php echo htmlspecialchars($regime['niveau_difficulte'] ?? '-'); ?></div>
                                            </div>
                                            <div class="info-box">
                                                <div class="info-label">Apport calorique moyen</div>
                                                <div class="info-value"><?php echo htmlspecialchars($regime['apport_calorique_moyen'] ?? '-'); ?> kcal</div>
                                            </div>
                                        </div>
                                        <?php if (!empty($regime['description'])) { ?>
                                            <div class="info-box" style="margin-top: 10px; grid-column: 1/-1;">
                                                <div class="info-label">Description</div>
                                                <div class="info-value"><?php echo htmlspecialchars($regime['description']); ?></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id_dossier" value="<?php echo $idDossierSelected; ?>">
                                        <input type="hidden" name="id_regime" value="<?php echo $regime['id_regime']; ?>">
                                        <button type="submit" class="btn-remove" style="background: #ff6b6b; color: white; padding: 8px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9em; margin-top: 10px;">❌ Retirer</button>
                                    </form>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <div class="no-results">
                        ⚠️ Aucun régime trouvé pour ce dossier médical.
                    </div>
                <?php } ?>

                <!-- Add new regime section -->
                <?php if ($idDossierSelected) { ?>
                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e0e0e0;">
                        <h3 class="results-title" style="margin-top: 0;">➕ Ajouter un régime à ce dossier</h3>
                        <form method="POST" style="display: flex; gap: 15px;">
                            <select name="id_regime" required style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="">-- Sélectionner un régime --</option>
                                <?php 
                                // Get IDs of already associated regimes
                                $associatedIds = array_column($list ?? [], 'id_regime');
                                foreach ($tousLesRegimes as $regime) { 
                                    if (!in_array($regime['id_regime'], $associatedIds)) {
                                ?>
                                    <option value="<?php echo $regime['id_regime']; ?>">
                                        <?php echo htmlspecialchars($regime['nom_regime']); ?> (<?php echo $regime['type_regime']; ?>)
                                    </option>
                                <?php 
                                    }
                                } 
                                ?>
                            </select>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id_dossier" value="<?php echo $idDossierSelected; ?>">
                            <button type="submit" class="btn-search" style="padding: 12px 24px;">Ajouter</button>
                        </form>
                    </div>
                <?php } ?>
                
                <a href="../modules/health-admin.html" class="back-link">← Retour à l'administration</a>
            </div>
        <?php } ?>
    </div>
</body>
</html>
