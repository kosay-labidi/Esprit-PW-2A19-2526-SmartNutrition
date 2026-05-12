<?php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../../helpers/aliment_helpers.php';

global $pdo;
$aliments = aliment_getAll($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Nos Aliments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="../js/fo_alimentlist.js"></script>
    <link rel="stylesheet" href="../css/fo_alimentlist.css">
    <script src="../js/fo_repaslist_init.js"></script>
    <link rel="stylesheet" href="../css/cursor.css">
</head>
<body>
<div id="cursor"></div><div id="cursor-trail"></div>
<div id="cur"></div>
<div id="curt"></div>

<!-- NAVBAR HORIZONTALE -->
<div class="main-content">

    <section class="hero">
      <div class="max-w-screen-xl mx-auto px-8">
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:4px 14px;border-radius:99px;font-size:11px;color:rgba(255,255,255,.85);margin-bottom:14px;">
            <i class="fas fa-leaf" style="color:var(--violet);"></i> BIBLIOTHÈQUE NUTRITIONNELLE
        </span>
        <h1 class="hf" style="font-size:54px;color:white;line-height:1;margin-bottom:10px;">Nos Aliments</h1>
        <p style="font-size:15px;color:rgba(255,255,255,.75);margin-bottom:20px;max-width:520px;line-height:1.6;">
            Explorez notre sélection d'aliments sains, locaux et durables pour composer vos repas avec conscience.
        </p>

        <!-- Statistiques -->
        <?php
        $nb     = count($aliments);
        $nbt    = count(array_unique(array_column($aliments,'type')));
        $nb_bio = count(array_filter($aliments, fn($a) => !empty($a['label_ecologique'])));
        ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;">
            <span class="sp"><b><?= $nb ?></b> aliments</span>
            <span class="sp"><b><?= $nbt ?></b> catégories</span>
            <span class="sp"><b><?= $nb_bio ?></b> labellisés</span>
        </div>

        <!-- FONCTIONNALITÉS HORS CRUD -->
        <div style="background:rgba(255,255,255,.08);border-radius:16px;padding:16px 20px;border:1px solid rgba(255,255,255,.12);">
            <p style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em;">Filtrer et rechercher</p>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;" id="fbar">
               
                <button class="fb on" data-type="tous"               onclick="setFilter(this)">Tous</button>
                <button class="fb"    data-type="légume"             onclick="setFilter(this)">🌿 Légumes</button>
                <button class="fb"    data-type="fruit"              onclick="setFilter(this)">🍊 Fruits</button>
                <button class="fb"    data-type="protéines animales" onclick="setFilter(this)">🥩 Protéines</button>
                <button class="fb"    data-type="céréale"            onclick="setFilter(this)">🌾 Céréales</button>
                <button class="fb"    data-type="légumineuse"        onclick="setFilter(this)">🫘 Légumineuses</button>
                <button class="fb"    data-type="produit laitier"    onclick="setFilter(this)">🥛 Laitiers</button>
                <button class="fb"    data-type="épice"              onclick="setFilter(this)">🌶 Épices</button>
                <button class="fb"    data-type="huile"              onclick="setFilter(this)">🫙 Huiles</button>

                <!-- Champ de recherche par nom -->
                <div class="sw" style="margin-left:auto;">
                    <i class="fas fa-search" style="color:rgba(255,255,255,.5);"></i>
                    <input id="sq" type="text" class="si" placeholder="Rechercher…" oninput="applyFilters()"
                           style="border-color:rgba(255,255,255,.25);background:rgba(255,255,255,.1);color:white;">
                </div>
            </div>

            <!-- Boutons toggle vue grille/liste -->
            <div style="margin-top:10px;display:flex;gap:6px;justify-content:flex-end;">
                <button id="bg" class="vbtn on" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
                <button id="bl" class="vbtn"    onclick="setView('list')"><i class="fas fa-list"></i></button>
            </div>
        </div>
      </div><!-- fin max-w-screen-xl hero -->
    </section>

    <div class="max-w-screen-xl mx-auto px-8" style="padding-top:20px;padding-bottom:40px;">

        <!-- VUE GRILLE  -->
        <div id="vg" class="gview">
        <?php foreach ($aliments as $a):
            $c  = typeConfig($a['type']);
            $sv = alimentSVG($a['nom'], $a['type'], $c, 48);
            $co = co2Config((float)$a['co2']);
            $ns = nutriScore($a);
        ?>
        
        <a href="fo_alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="card"
           data-type="<?= htmlspecialchars($a['type']) ?>"
           data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

            <!-- En-tête carte : icône SVG + nom + badges + nutriscore -->
            <div style="padding:18px 18px 12px;display:flex;align-items:center;gap:12px;">
                <?= $sv ?>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:700;color:var(--text-main);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($a['nom']) ?></p>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <!-- Nutri-Score calculé par la fonction nutriScore() dans helpers -->
                <div style="width:30px;height:30px;border-radius:7px;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;" title="Nutri-Score <?= $ns['grade'] ?>"><?= $ns['grade'] ?></div>
            </div>

            <!-- Corps carte -->
            <div style="padding:0 18px 14px;">
                <?php
                
                $mc = [
                    ['Protéines', $a['proteines'], 50,  '#60a5fa', 'g'],
                    ['Glucides',  $a['glucides'],  100, '#c9a44a', 'g'],
                    ['Lipides',   $a['lipides'],   50,  '#a78bfa', 'g'],
                    ['Fibres',    $a['fibres'],    30,  '#1a372f', 'g'],
                ];
                foreach ($mc as [$l,$v,$m,$col,$u]):
                    $p = $m > 0 ? min(100, $v/$m*100) : 0;
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);">
                        <span><?= $l ?></span>
                        <span style="font-weight:600;color:var(--text-main);"><?= number_format($v,1) ?><?= $u ?></span>
                    </div>
                    <div class="mbar"><div class="mfil" style="width:<?= $p ?>%;background:<?= $col ?>;"></div></div>
                </div>
                <?php endforeach; ?>

                <div style="display:flex;gap:12px;margin-top:3px;">
                    <span style="font-size:10px;color:var(--text-muted);">Sucre: <b style="color:var(--text-main);"><?= number_format($a['sucre'],1) ?>g</b></span>
                    <span style="font-size:10px;color:var(--text-muted);">Sodium: <b style="color:var(--text-main);"><?= number_format($a['sodium'],1) ?>mg</b></span>
                </div>
            </div>

            <div style="padding:12px 18px;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <p style="font-size:18px;font-weight:700;color:var(--text-main);font-family:'Cormorant Garamond',serif;margin:0;"><?= number_format($a['calories'],0) ?> <span style="font-size:10px;font-weight:400;color:var(--text-muted);font-family:'Lato',sans-serif;">kcal/100g</span></p>
                    <?php if ($a['prix'] > 0): ?>
                    <p style="font-size:11px;color:var(--text-muted);margin:2px 0 0;"><?= number_format($a['prix'],2) ?> TND/kg</p>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:9.5px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:3px 8px;border-radius:99px;font-weight:600;">CO₂ <?= $co['label'] ?></span>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:10px;color:var(--text-muted);margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <!-- État vide après filtrage -->
        <div id="emptyG" style="display:none;grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted);">
            <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
            Aucun aliment trouvé pour ce filtre.
        </div>
        </div>

        <!--  VUE LISTE  -->
        <div id="vl" class="lview">
            <!-- En-tête de la vue liste -->
            <div class="lhead">
                <div></div>
                <div>Aliment</div>
                <div style="text-align:center">Cal.</div>
                <div style="text-align:center">Prot.</div>
                <div style="text-align:center">Gluc.</div>
                <div style="text-align:center">Lip.</div>
                <div style="text-align:center">Prix</div>
                <div>CO₂ / Infos</div>
            </div>

            <div class="lbody" id="lbody">
            <?php foreach ($aliments as $a):
                $c  = typeConfig($a['type']);
                $sv = alimentSVG($a['nom'], $a['type'], $c, 40);
                $co = co2Config((float)$a['co2']);
                $ns = nutriScore($a);
            ?>
            <!-- Ligne cliquable vers le détail -->
            <a href="fo_alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="lrow"
               data-type="<?= htmlspecialchars($a['type']) ?>"
               data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">
                <div style="display:flex;align-items:center;justify-content:center;"><?= $sv ?></div>
                <div style="padding-left:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <p style="font-size:13px;font-weight:700;color:var(--text-main);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <span style="font-size:11px;font-weight:700;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;padding:1px 6px;border-radius:5px;"><?= $ns['grade'] ?></span>
                    </div>
                    <div style="display:flex;gap:4px;margin-top:3px;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9.5px;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['calories'],0) ?></p><p style="font-size:9px;color:var(--text-muted);margin:0;">kcal</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= $a['prix']>0 ? number_format($a['prix'],2).' TND' : '—' ?></p></div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                        <span style="font-size:10px;color:var(--text-muted);"><?= number_format($a['co2'],2) ?> kg</span>
                        <span style="font-size:9px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co['label'] ?></span>
                    </div>
                    <div style="height:4px;border-radius:2px;background:#ede8e0;overflow:hidden;">
                        <div style="width:<?= $co['pct'] ?>%;height:100%;background:<?= $co['color'] ?>;border-radius:2px;"></div>
                    </div>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:9.5px;color:var(--text-muted);margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <div id="emptyL" style="display:none;padding:40px;text-align:center;color:var(--text-muted);">Aucun résultat.</div>
            </div>

            <!-- Pied tableau liste -->
            <div style="padding:10px 16px;background:#f9f6f2;border-radius:0 0 16px 16px;border:1px solid var(--border-card);border-top:none;display:flex;justify-content:space-between;">
                <span style="font-size:11px;color:var(--text-muted);" id="rc"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:var(--text-muted);">GaiaLumen © <?= date('Y') ?></span>
            </div>
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
<div id="google_translate_element" style="display:none;"></div>
    <script src="../js/cursor.js"></script>
</body>
</html>
