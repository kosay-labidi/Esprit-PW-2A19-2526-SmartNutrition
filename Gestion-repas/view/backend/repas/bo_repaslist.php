<?php
/* ============================================================
   view/BackOffice/bo_repaslist.php — BACK OFFICE Repas

   RÔLE : Vue READ des repas pour l'administrateur.
          Affiche un tableau avec la jointure repas ↔ aliments.

   CORRECTION MVC :
     Les données ($tousRepas, $repaDetails) sont préparées
     par le Controller (repascontroller.php?action=list_back)
     et passées ici via include.
     Cette vue NE fait PAS de requêtes SQL directement.

   URL D'ACCÈS : controller/repascontroller.php?action=list_back
   ============================================================ */

/* Sécurité : si la vue est appelée sans le Controller */
if (!isset($tousRepas)) {
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../controller/repascontroller.php';
    require_once __DIR__ . '/../../../helpers/aliment_helpers.php';
    require_once __DIR__ . '/../../../helpers/repas_helpers.php';
    global $pdo;
    $tousRepas   = repas_getAll($pdo);
    $repaDetails = [];
    foreach ($tousRepas as $r) {
        $id = (int)$r['id_repas'];
        $repaDetails[$id] = [
            'aliments' => repas_getAlimentsOfRepas($pdo, $id),
            'totaux'   => repas_getTotauxNutritionnels($pdo, $id),
        ];
    }
} else {
    require_once __DIR__ . '/../../../helpers/aliment_helpers.php';
    require_once __DIR__ . '/../../../helpers/repas_helpers.php';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Back Office — Repas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="/GSRepasVF2_final/Gestion-repas/view/backend/css/bo_repaslist.css">
    <script src="/GSRepasVF2_final/Gestion-repas/view/backend/js/bo_repaslist.js"></script>
    <link rel="stylesheet" href="../css/bo_cursor.css">
</head>
<body>
<div id="cursor"></div><div id="cursor-trail"></div>

<section class="hero">
        <h1 class="hf" style="font-size:44px;color:white;line-height:1;margin-bottom:8px;">Consultation des Repas</h1>
        <p style="font-size:13px;color:rgba(255,255,255,.7);margin-bottom:16px;">
            Vue administrative — tous les repas créés par les utilisateurs.
        </p>
        <?php
        $totalRepas   = count($tousRepas);
        $totalAlimSel = array_sum(array_column($tousRepas, 'nb_aliments'));
        ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
            <span class="sp"><b><?= $totalRepas ?></b> repas au total</span>
            <span class="sp"><b><?= $totalAlimSel ?></b> sélections d'aliments</span>
        </div>

        <!-- Recherche (hors CRUD — filtre affichage uniquement) -->
        <div class="sw">
            <i class="fas fa-search"></i>
            <input id="sq" type="text" class="si" placeholder="Rechercher un repas…" oninput="applySearch()">
        </div>
    </section>

    <!-- TABLEAU DES REPAS avec jointure visible -->
    <div style="padding:20px 28px 40px;">
        <!-- Breadcrumb concept 3 -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <p style="font-size:13px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-utensils" style="font-size:12px;"></i>
                Repas
                <span style="opacity:.4;">›</span>
                <span id="breadcrumb-liste" style="color:var(--vert);font-weight:600;">Liste</span>
            </p>
            <a href="javascript:void(0)" onclick="if(window.parent&&window.parent.nav){window.parent.nav(null,'aliments','Aliments');}return false;"
               style="font-size:12px;color:#9ca3af;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;"
               onmouseover="this.style.color='var(--violet)'"
               onmouseout="this.style.color='#9ca3af'">
                Passer aux aliments <i class="fas fa-arrow-right" style="font-size:11px;"></i>
            </a>
        </div>
        <div class="tbl-wrap" style="overflow-x:auto;">

            <?php if (empty($tousRepas)): ?>
            <div style="padding:48px;text-align:center;color:#9ca3af;">
                <i class="fas fa-utensils" style="font-size:2.5rem;display:block;margin-bottom:14px;color:#d0c8be;"></i>
                Aucun repas n'a encore été créé par les utilisateurs.
            </div>
            <?php else: ?>

            <!-- En-tête du tableau -->
            <div class="thead" style="min-width:900px;">
                <div style="text-align:center;">#</div>
                <div>Repas</div>
                <div>Date & heure</div>
                <div style="text-align:center;">Calories</div>
                <div style="text-align:center;">Protéines</div>
                <div style="text-align:center;">Score éco.</div>
                <div>CO₂ total</div>
                <!-- Colonne JOINTURE : composition du repas -->
                <div>Composition (aliments)</div>
            </div>

            <!-- Corps du tableau -->
            <div id="tbody" style="min-width:900px;">
            <?php foreach ($tousRepas as $r):
                $id     = (int) $r['id_repas'];
                $detail = $repaDetails[$id] ?? ['aliments'=>[], 'totaux'=>[]];
                $alims  = $detail['aliments'];
                $tot    = $detail['totaux'];

                /* Score écologique via helpers */
                $score  = scoreEcologique($tot);
                $lbl    = labelEcologique($score);
            ?>
            <div class="trow" data-nom="<?= strtolower(htmlspecialchars($r['nom_repas'])) ?>">

                <!-- ID -->
                <div style="text-align:center;">
                    <span style="font-size:11px;background:#f4ede4;color:#6b7280;padding:3px 8px;border-radius:6px;font-weight:600;" class="badge-id">#<?= $id ?></span>
                </div>

                <!-- Nom du repas + utilisateur -->
                <div>
                    <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= htmlspecialchars($r['nom_repas']) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">
                        <i class="fas fa-user" style="margin-right:3px;"></i>Utilisateur #<?= $r['id_utilisateur'] ?>
                        · <?= $r['nb_aliments'] ?> aliment(s)
                    </p>
                </div>

                <!-- Date -->
                <div>
                    <p style="font-size:12px;font-weight:500;color:var(--vert);margin:0;">
                        <?= date('d/m/Y', strtotime($r['date_repas'])) ?>
                    </p>
                    <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">
                        <?= date('H:i', strtotime($r['date_repas'])) ?>
                    </p>
                </div>

                <!-- Calories totales -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;margin:0;">
                        <?= !empty($tot) ? round($tot['total_calories'],0) : '—' ?>
                    </p>
                    <p style="font-size:9px;color:#9ca3af;margin:0;">kcal</p>
                </div>

                <!-- Protéines totales -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:700;color:#1a5fa8;font-family:'Cormorant Garamond',serif;margin:0;">
                        <?= !empty($tot) ? round($tot['total_proteines'],1) : '—' ?>
                    </p>
                    <p style="font-size:9px;color:#9ca3af;margin:0;">g</p>
                </div>

                <!-- Score écologique (Fonctionnalité 1) -->
                <div style="display:flex;align-items:center;justify-content:center;">
                    <div class="eco-circle" style="color:<?= $lbl['color'] ?>;border-color:<?= $lbl['color'] ?>;">
                        <span style="font-size:13px;font-weight:700;font-family:'Cormorant Garamond',serif;"><?= $score ?></span>
                        <span style="font-size:7px;opacity:.7;">/100</span>
                    </div>
                </div>

                <!-- CO₂ avec barre -->
                <div>
                    <?php if (!empty($tot)): ?>
                    <p style="font-size:12px;font-weight:600;color:<?= $lbl['color'] ?>;margin:0;">
                        <?= round($tot['total_co2'],2) ?> kg
                    </p>
                    <div class="cobar">
                        <div class="cofil" style="width:<?= min(100,$tot['total_co2']/5*100) ?>%;background:<?= $lbl['bar'] ?>;"></div>
                    </div>
                    <span style="font-size:9px;background:<?= $lbl['bg'] ?>;color:<?= $lbl['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $lbl['emoji'] ?> <?= $lbl['label'] ?></span>
                    <?php else: ?>
                    <p style="font-size:11px;color:#c4bdb5;">—</p>
                    <?php endif; ?>
                </div>

                <!-- JOINTURE VISIBLE : aliments qui composent le repas
                     Résultat de : SELECT a.*, ra.quantite
                                   FROM repas_aliments ra
                                   INNER JOIN aliments a ON ra.id_aliment = a.id_aliment
                                   WHERE ra.id_repas = ?                              -->
                <div>
                    <?php if (empty($alims)): ?>
                        <p style="font-size:11px;color:#c4bdb5;font-style:italic;">Aucun aliment</p>
                    <?php else: ?>
                        <?php foreach ($alims as $al):
                            $c = typeConfig($al['type']); ?>
                        <span class="alim-pill" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;">
                            <?= htmlspecialchars($al['nom']) ?>
                            <span style="opacity:.65;">(<?= $al['quantite'] ?>g)</span>
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>

            <div id="noResult" style="display:none;padding:40px;text-align:center;color:#9ca3af;">
                <i class="fas fa-filter" style="display:block;margin-bottom:10px;font-size:1.8rem;color:#d0c8be;"></i>
                Aucun résultat.
            </div>
            </div>

            <!-- Pied de tableau -->
            <div style="padding:10px 16px;background:#f9f6f2;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;" id="rowCount"><?= count($tousRepas) ?> repas</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen Back Office © <?= date('Y') ?></span>
            </div>
            <?php endif; ?>
        </div>


    </div>

</div>
<!-- MODALE LANGUE -->
<div id="langModal">
    <div id="langBox">
        <div id="langHead">
            <h2>Choisir une langue</h2>
            <button onclick="closeLang()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);line-height:1;padding:0;">&#x2715;</button>
        </div>
        <div id="langList">
            <input id="langSearch" type="text" placeholder="Rechercher une langue..." oninput="filterLangs(this.value)"/>
            <div id="langsContainer"></div>
        </div>
    </div>
</div>
<div id="google_translate_element" style="display:none;">

<script>
/* Recherche */
function applySearch() {
    var q    = (document.getElementById('sq').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('.trow');
    var vis  = 0;
    rows.forEach(function(r) {
        var nom  = (r.dataset.nom || '').toLowerCase();
        var show = !q || nom.includes(q);
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    var noRes  = document.getElementById('noResult');
    var rowCnt = document.getElementById('rowCount');
    if (noRes)  noRes.style.display = (vis === 0) ? 'block' : 'none';
    if (rowCnt) rowCnt.textContent  = vis + ' repas';
}
</script>

    <script src="../js/cursor.js"></script>
</body>
</html>
