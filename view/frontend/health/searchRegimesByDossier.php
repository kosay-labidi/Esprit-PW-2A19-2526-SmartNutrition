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
    <title>Mes Régimes Personnalisés</title>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        :root {
            --vert: #013220;
            --sable: #CBBD93;
            --violet: #BA5BED;
            --bleu: #77B5FE;
        }

        .search-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .search-title {
            color: var(--vert);
            font-size: 1.5em;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--vert);
            font-weight: 600;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--bleu);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1em;
        }

        .btn-search {
            background: var(--bleu);
            color: white;
        }

        .btn-search:hover {
            background: #5fa3f0;
        }

        .regime-card {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--bleu);
        }

        .regime-name {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--vert);
            margin-bottom: 10px;
        }

        .regime-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .info-box {
            background: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }

        .info-label {
            font-weight: 600;
            color: var(--vert);
            font-size: 0.9em;
            margin-bottom: 4px;
        }

        .info-value {
            color: #666;
            font-size: 1.05em;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
            background: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <main class="main-wrapper">
        <div class="search-container">
            <h2 class="search-title">🥗 Trouver Mes Régimes Personnalisés</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="dossier">Sélectionnez votre dossier médical :</label>
                    <select name="dossier" id="dossier" required>
                        <option value="">-- Choisir un dossier --</option>
                        <?php foreach ($tousLesDossiers as $dossier) { ?>
                            <option value="<?php echo $dossier['id_dossier']; ?>">
                                Dossier #<?php echo $dossier['id_dossier']; ?> 
                                (Groupe: <?php echo htmlspecialchars($dossier['groupe_sanguin']); ?>)
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" name="search" class="btn-search">Voir mes régimes</button>
                </div>
            </form>
        </div>

        <?php if (isset($list)) { ?>
            <div class="search-container">
                <h3 class="search-title">📋 Régimes Associés à Votre Dossier</h3>
                
                <?php if (is_array($list) && count($list) > 0) { ?>
                    <?php foreach ($list as $regime) { ?>
                        <div class="regime-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div class="regime-name">
                                        <?php echo htmlspecialchars($regime['nom_regime']); ?>
                                    </div>
                                    <div class="regime-info">
                                        <div class="info-box">
                                            <div class="info-label">Type</div>
                                            <div class="info-value"><?php echo htmlspecialchars($regime['type_regime'] ?? '-'); ?></div>
                                        </div>
                                        <div class="info-box">
                                            <div class="info-label">Difficulté</div>
                                            <div class="info-value"><?php echo htmlspecialchars($regime['niveau_difficulte'] ?? '-'); ?></div>
                                        </div>
                                        <div class="info-box">
                                            <div class="info-label">Calories/jour</div>
                                            <div class="info-value"><?php echo htmlspecialchars($regime['apport_calorique_moyen'] ?? '-'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id_dossier" value="<?php echo $idDossierSelected; ?>">
                                    <input type="hidden" name="id_regime" value="<?php echo $regime['id_regime']; ?>">
                                    <button type="submit" style="background: #ff6b6b; color: white; padding: 8px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9em;">❌ Retirer</button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="no-results">
                        ⚠️ Aucun régime n'est actuellement associé à votre dossier médical.
                    </div>
                <?php } ?>

                <!-- Add new regime section -->
                <?php if ($idDossierSelected) { ?>
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                        <h4 style="color: #013220; font-weight: 600; margin-bottom: 15px;">➕ Ajouter un régime</h4>
                        <form method="POST" style="display: flex; gap: 10px;">
                            <select name="id_regime" required style="flex: 1; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <option value="">-- Sélectionner un régime --</option>
                                <?php 
                                $associatedIds = array_column($list ?? [], 'id_regime');
                                foreach ($tousLesRegimes as $regime) { 
                                    if (!in_array($regime['id_regime'], $associatedIds)) {
                                ?>
                                    <option value="<?php echo $regime['id_regime']; ?>">
                                        <?php echo htmlspecialchars($regime['nom_regime']); ?>
                                    </option>
                                <?php 
                                    }
                                } 
                                ?>
                            </select>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id_dossier" value="<?php echo $idDossierSelected; ?>">
                            <button type="submit" class="btn-search" style="padding: 10px 20px;">Ajouter</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </main>
</body>
</html>
