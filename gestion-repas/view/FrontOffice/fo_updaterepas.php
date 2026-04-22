<?php
/* ============================================================
   view/FrontOffice/updaterepas.php — UPDATE d'un repas (MVC)
   RÔLE : Formulaire de modification d'un repas existant.
          Pré-remplit les données actuelles du repas et les
          aliments déjà sélectionnés (via la jointure).
   ============================================================ */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/repas_model.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

$repasModel   = new Repas();
$alimentModel = new Aliment();

$id    = (int) ($_GET['id'] ?? 0);
$repas = $repasModel->getById($id);

if (!$repas) { header("Location: fo_repaslist.php"); exit; }

/* Aliments actuellement dans ce repas (jointure repas_aliments) */
$alimActuels   = $repasModel->getAlimentsOfRepas($id);
$alimActuelsId = array_column($alimActuels, 'id_aliment');
$alimActuelsQte= array_column($alimActuels, 'quantite', 'id_aliment');

/* Tous les aliments disponibles */
$tousAliments = $alimentModel->getAll();

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Modifier le repas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        body { background:var(--sable); color:var(--vert); }
        .navbar { background:linear-gradient(90deg,var(--vert) 0%,#11241f 100%); }
        .fi { width:100%;padding:10px 14px;border-radius:12px;border:1.5px solid #e8e0d8;font-size:13px;outline:none;font-family:'Lato',sans-serif; }
        .fi:focus { border-color:var(--violet); }
        .lbl { display:block;font-size:11px;font-weight:700;color:var(--vert);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em; }
        .alim-item { border:1.5px solid #ede8e0;border-radius:12px;padding:10px;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:8px; }
        .alim-item:hover { border-color:var(--violet);background:#f9f5ff; }
        .alim-item.selected { border-color:var(--vert);background:#e8f0e9; }
        .badge { display:inline-block;font-size:9px;padding:2px 7px;border-radius:99px;font-weight:600; }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar text-white shadow-xl" style="position:sticky;top:0;z-index:50;">
    <div class="max-w-screen-xl mx-auto px-8 py-4 flex items-center justify-between">
        <a href="../../index.html" class="flex items-center gap-3">
            <svg width="30" height="30" viewBox="0 0 60 60" fill="none"><circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/><defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs><path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/></svg>
            <span class="hf text-3xl tracking-tighter">GaiaLumen</span>
        </a>
        <ul class="flex items-center gap-7 text-sm font-medium">
            <li><a href="fo_repaslist.php" class="hover:text-[#a78bfa]">← Mes Repas</a></li>
        </ul>
    </div>
</nav>

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
        <form id="updateForm" action="../../controller/repascontroller.php" method="POST" novalidate>
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
                    <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* Données des aliments actuellement sélectionnés (depuis PHP) */
const uSelected = {
<?php foreach ($alimActuels as $al): ?>
    <?= $al['id_aliment'] ?>: '<?= addslashes($al['nom']) ?>',
<?php endforeach; ?>
};

function uToggle(id, nom) {
    const item = document.getElementById('uitem_'+id);
    const chk  = document.getElementById('uchk_'+id);
    if (uSelected[id]) {
        delete uSelected[id];
        item.classList.remove('selected');
        chk.checked = false;
        const row = document.getElementById('uqrow_'+id);
        if (row) row.remove();
    } else {
        uSelected[id] = nom;
        item.classList.add('selected');
        chk.checked = true;
        addQteRow(id, nom, 100);
    }
}
function uSyncToggle(id) {
    const chk  = document.getElementById('uchk_'+id);
    const nom  = document.getElementById('uitem_'+id).querySelector('p').textContent;
    if (chk.checked) { uSelected[id]=nom; document.getElementById('uitem_'+id).classList.add('selected'); addQteRow(id,nom,100); }
    else { delete uSelected[id]; document.getElementById('uitem_'+id).classList.remove('selected'); const r=document.getElementById('uqrow_'+id); if(r)r.remove(); }
}
function addQteRow(id, nom, qte) {
    if (document.getElementById('uqrow_'+id)) return;
    const div = document.createElement('div');
    div.id = 'uqrow_'+id;
    div.style.cssText = 'display:flex;align-items:center;gap:10px;';
    div.innerHTML = `<span style="font-size:12px;color:var(--vert);font-weight:600;flex:1;">${nom}</span>
        <input type="number" name="quantites[${id}]" value="${qte}" min="1" max="2000"
               style="width:80px;padding:5px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;">
        <span style="font-size:11px;color:#9ca3af;">g</span>`;
    document.getElementById('uQuantitesList').appendChild(div);
}
</script>
</body>
</html>
