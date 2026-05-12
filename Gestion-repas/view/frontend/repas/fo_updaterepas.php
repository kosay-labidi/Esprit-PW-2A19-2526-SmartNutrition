<?php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/repascontroller.php';
require_once __DIR__ . '/../../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../../helpers/aliment_helpers.php';

global $pdo;

$id    = (int) ($_GET['id'] ?? 0);
$repas = repas_getById($pdo, $id);

if (!$repas) { header("Location: fo_repaslist.php"); exit; }

/* Aliments actuellement dans ce repas (jointure repas_aliments) */
$alimActuels   = repas_getAlimentsOfRepas($pdo, $id);
$alimActuelsId = array_column($alimActuels, 'id_aliment');
$alimActuelsQte= array_column($alimActuels, 'quantite', 'id_aliment');

/* Tous les aliments disponibles */
$tousAliments = aliment_getAll($pdo);

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Modifier le repas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="../css/fo_repaslist.css">

    <script src="../js/fo_updaterepas.js"></script>
</head>
<body>
<!-- Navbar -->
<div class="max-w-screen-md mx-auto px-8 py-10">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <a href="fo_repaslist.php" style="color:var(--vert);text-decoration:none;font-size:20px;"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h1 class="hf" style="font-size:36px;color:var(--vert);margin:0;">Modifier le repas</h1>
            <p style="font-size:12px;color:#9ca3af;margin:0;">ID #<?= $id ?> · <?= htmlspecialchars($repas['nom_repas']) ?></p>
        </div>
    </div>

    <?php if ($error): ?>
    <div style="background:#faeaea;color:#8a2020;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-size:13px;">
        <i class="fas fa-exclamation-triangle"></i> Erreur de validation. Vérifiez les champs.
    </div>
    <?php endif; ?>

    <div style="background:white;border-radius:24px;padding:32px;border:1px solid #ede8e0;">
        <form id="updateForm" action="../../../controller/repascontroller.php" method="POST" novalidate>
            <input type="hidden" name="action"   value="update">
            <input type="hidden" name="id_repas" value="<?= $id ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                    <label class="lbl">Nom du repas *</label>
                    <input type="text" name="nom_repas" class="fi" required
                           value="<?= htmlspecialchars($repas['nom_repas']) ?>" maxlength="150">
                </div>
                <div>
                    <label class="lbl">Date & heure *</label>
                    <input type="datetime-local" name="date_repas" class="fi" required
                           value="<?= date('Y-m-d\TH:i', strtotime($repas['date_repas'])) ?>">
                </div>
            </div>

            <!-- Sélection des aliments (pré-cochés via jointure) -->
            <div style="margin-bottom:20px;">
                <label class="lbl">Composition du repas *</label>
                <p style="font-size:12px;color:#9ca3af;margin-bottom:10px;">Les aliments déjà dans ce repas sont pré-sélectionnés.</p>

                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;max-height:280px;overflow-y:auto;margin-bottom:14px;">
                <?php foreach ($tousAliments as $al):
                    $c  = typeConfig($al['type']);
                    $sv = alimentSVG($al['nom'], $al['type'], $c, 26);
                    $isSel = in_array($al['id_aliment'], $alimActuelsId);
                ?>
                <div class="alim-item <?= $isSel ? 'selected' : '' ?>"
                     id="uitem_<?= $al['id_aliment'] ?>"
                     onclick="uToggle(<?= $al['id_aliment'] ?>, '<?= addslashes($al['nom']) ?>')"
                     data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>">
                    <input type="checkbox" name="aliments[]" value="<?= $al['id_aliment'] ?>"
                           id="uchk_<?= $al['id_aliment'] ?>"
                           <?= $isSel ? 'checked' : '' ?>
                           onclick="event.stopPropagation();" onchange="uSyncToggle(<?= $al['id_aliment'] ?>)">
                    <?= $sv ?>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:11px;font-weight:600;color:var(--vert);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($al['nom']) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;"><?= round($al['calories'],0) ?> kcal</p>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Quantités (pré-remplies avec les valeurs actuelles) -->
                <div id="uQuantiteZone">
                    <p style="font-size:11px;font-weight:700;color:var(--vert);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Quantités (grammes)</p>
                    <div id="uQuantitesList" style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($alimActuels as $al): ?>
                    <div id="uqrow_<?= $al['id_aliment'] ?>" style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:12px;color:var(--vert);font-weight:600;flex:1;"><?= htmlspecialchars($al['nom']) ?></span>
                        <input type="number" name="quantites[<?= $al['id_aliment'] ?>]"
                               value="<?= $al['quantite'] ?>" min="1" max="2000"
                               style="width:80px;padding:5px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;">
                        <span style="font-size:11px;color:#9ca3af;">g</span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <a href="fo_repaslist.php"
                   style="flex:1;text-align:center;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;color:var(--vert);text-decoration:none;font-weight:600;font-size:13px;">
                    Annuler
                </a>
                <button type="submit"
                    style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                    <i id="btnEnregistrerUpdate" class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
