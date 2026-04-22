<?php
/* ============================================================
   view/FrontOffice/fo_alimentdetail.php — FRONT OFFICE
   RÔLE : Fiche complète d'UN seul aliment.

   VALEUR AJOUTÉE par rapport à fo_alimentlist.php :
     - fo_alimentlist affiche un RÉSUMÉ de TOUS les aliments
       (nom, type, 4 macros, CO₂, prix) → vue de parcours
     - fo_alimentdetail affiche le DÉTAIL COMPLET d'UN aliment
       (toutes les valeurs nutritionnelles avec graphique donut,
        sucre, sodium, vitamines, label écologique, origine,
        allergènes, impact CO₂ détaillé) → vue de consultation
     - Contient le bouton "Ajouter à un repas" qui renvoie
       vers fo_repaslist.php avec l'aliment pré-sélectionné

   COMMENT Y ACCÉDER :
     En cliquant sur une carte dans fo_alimentlist.php
     URL : fo_alimentdetail.php?id=X

   ARCHITECTURE MVC :
     Model      : model/aliment.php  (getById)
     View       : ce fichier
     Controller : aucun (lecture seule, pas d'action CRUD)
   ============================================================ */

/* ----------------------------------------------------------
   SECTION 1 — DÉPENDANCES (chemins relatifs depuis FrontOffice/)
   ../../ remonte de FrontOffice/ → view/ → gestion-repas/
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DE L'ALIMENT (Model)
   On récupère UN seul aliment via son id passé en GET.
   Si l'id est invalide → redirection vers la liste.
   ---------------------------------------------------------- */
$alimentModel = new Aliment();
$a = $alimentModel->getById((int)($_GET['id'] ?? 0));

if (!$a) {
    header("Location: fo_alimentlist.php");
    exit;
}

/* ----------------------------------------------------------
   SECTION 3 — CALCULS POUR L'AFFICHAGE (helpers)
   Ces fonctions sont dans helpers/aliment_helpers.php
   ---------------------------------------------------------- */
$c   = typeConfig($a['type']);          /* couleurs selon le type */
$co2 = co2Config((float)$a['co2']);    /* couleur/label CO₂      */
$ns  = nutriScore($a);                  /* grade Nutri-Score A→E  */
$mp  = macroPercents($a);               /* % calorique macros     */
$svg = alimentSVG($a['nom'], $a['type'], $c, 90); /* icône SVG   */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · <?= htmlspecialchars($a['nom']) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js pour le graphique donut des macros -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    <style>
        /* ── Polices GaiaLumen ──────────────────────────────── */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');

        /* ── Palette officielle ─────────────────────────────── */
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }

        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; } /* titres */
        body { background:var(--sable); color:var(--vert); }

        /* ── Curseur personnalisé ─────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ── Navbar horizontale ─────────────────────────────── */
        .navbar { background:linear-gradient(90deg,var(--vert) 0%,#11241f 100%); position:sticky; top:0; z-index:50; }

        /* ── Hero de l'aliment ──────────────────────────────── */
        .aliment-hero {
            background: linear-gradient(135deg, <?= $c['bg'] ?> 0%, white 70%);
            border-bottom: 1px solid <?= $c['stroke'] ?>33;
        }

        /* ── Cartes de section ──────────────────────────────── */
        .card { background:white; border-radius:20px; border:1px solid #ede8e0; padding:24px; }

        /* ── Badges ─────────────────────────────────────────── */
        .badge { display:inline-block; font-size:11px; padding:3px 10px; border-radius:99px; font-weight:600; white-space:nowrap; }
        .cbadge { font-size:11px; padding:2px 8px; border-radius:5px; background:#f4ede4; color:#5a5850; }

        /* ── Barres de macronutriments ──────────────────────── */
        .mrow   { display:flex; align-items:center; gap:10px; margin-bottom:11px; }
        .mtrack { flex:1; height:8px; border-radius:4px; background:#ede8e0; overflow:hidden; }
        .mfill  { height:100%; border-radius:4px; transition:width .9s ease; }

        /* ── Boîtes statistiques (hero) ─────────────────────── */
        .stat-box { background:var(--sable); border-radius:14px; padding:14px 18px; text-align:center; }
        .stat-val { font-size:24px; font-weight:700; font-family:'Cormorant Garamond',serif; }
        .stat-lbl { font-size:11px; color:#9ca3af; margin-top:2px; }

        /* ── Graphique donut (macros) ───────────────────────── */
        .donut-wrap   { position:relative; width:150px; height:150px; flex-shrink:0; }
        .donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; }

        /* ── Nutri-Score lettre ─────────────────────────────── */
        .ns-box { width:60px; height:60px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:700; font-family:'Cormorant Garamond',serif; }
    </style>
</head>
<body>
<div id="cur"></div>
<div id="curt"></div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 4 — NAVBAR HORIZONTALE
     ══════════════════════════════════════════════════════════ -->
<nav class="navbar text-white shadow-xl">
    <div class="max-w-screen-xl mx-auto px-8 py-4 flex items-center justify-between">
        <a href="../../index.html" class="flex items-center gap-3">
            <svg width="32" height="32" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
                <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#60a5fa"/>
                    <stop offset="100%" stop-color="#a78bfa"/>
                </radialGradient></defs>
                <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
            </svg>
            <span class="hf text-3xl tracking-tighter">GaiaLumen</span>
        </a>
        <ul class="flex items-center gap-7 text-sm font-medium">
            <li><a href="../../index.html"  class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
            <!-- Lien retour vers la liste des aliments -->
            <li><a href="fo_alimentlist.php" class="text-[#a78bfa] font-semibold">Aliments</a></li>
            <li><a href="fo_repaslist.php"  class="hover:text-[#a78bfa] transition-colors">Mes Repas</a></li>
            <li><a href="#"                 class="hover:text-[#a78bfa] transition-colors">Défis</a></li>
        </ul>
        <a href="#" class="flex items-center gap-2 text-sm bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full">
            <i class="fas fa-user-circle"></i> Mon compte
        </a>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     SECTION 5 — FIL D'ARIANE
     Accueil > Aliments > [Nom de l'aliment]
     ══════════════════════════════════════════════════════════ -->
<div class="max-w-screen-xl mx-auto px-8 pt-5">
    <nav style="font-size:13px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
        <a href="fo_alimentlist.php" style="color:var(--vert);text-decoration:none;font-weight:600;">Aliments</a>
        <span>/</span>
        <span><?= htmlspecialchars($a['nom']) ?></span>
    </nav>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 6 — HERO DE L'ALIMENT
     Affiche le nom, les 8 stats clés et le Nutri-Score.
     Couleur de fond adaptée au type d'aliment (helpers).
     ══════════════════════════════════════════════════════════ -->
<div class="aliment-hero" style="margin-top:10px;">
    <div class="max-w-screen-xl mx-auto px-8 py-10">
        <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;">

            <!-- Icône SVG de l'aliment (générée par alimentSVG()) -->
            <div style="background:<?= $c['bg'] ?>;border-radius:22px;padding:14px;border:2px solid <?= $c['stroke'] ?>44;flex-shrink:0;">
                <?= $svg ?>
            </div>

            <!-- Informations principales -->
            <div style="flex:1;min-width:260px;">

                <!-- Badges type + catégorie + label + origine -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                    <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    <?php if (!empty($a['label_ecologique'])): ?>
                    <span class="badge" style="background:#e8f0e9;color:var(--vert);">🌱 <?= htmlspecialchars($a['label_ecologique']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($a['origine'])): ?>
                    <span class="badge" style="background:#f4ede4;color:var(--vert);">📍 <?= htmlspecialchars($a['origine']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Nom de l'aliment (titre Cormorant Garamond) -->
                <h1 class="hf" style="font-size:54px;color:var(--vert);line-height:1;margin:0 0 16px;">
                    <?= htmlspecialchars($a['nom']) ?>
                </h1>

                <!-- ── 8 stats clés en grille ───────────────────
                     VALEUR AJOUTÉE vs fo_alimentlist :
                     La liste ne montre que calories + 4 macros.
                     Ici on affiche TOUTES les valeurs y compris
                     sucre, sodium et prix.
                     ─────────────────────────────────────────── -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:10px;max-width:620px;">
                    <?php
                    $stats = [
                        ['Calories',   number_format($a['calories'],1),  'kcal/100g', 'var(--vert)'],
                        ['Protéines',  number_format($a['proteines'],1), 'g/100g',    '#1a5fa8'],
                        ['Glucides',   number_format($a['glucides'],1),  'g/100g',    '#8a6510'],
                        ['Lipides',    number_format($a['lipides'],1),   'g/100g',    '#7c5cbf'],
                        ['Fibres',     number_format($a['fibres'],1),    'g/100g',    'var(--vert)'],
                        ['Sucre',      number_format($a['sucre'],1),     'g/100g',    'var(--vert)'],
                        ['Sodium',     number_format($a['sodium'],1),    'mg/100g',   'var(--vert)'],
                        ['Prix',       $a['prix']>0 ? number_format($a['prix'],2) : '—', 'TND/kg', 'var(--vert)'],
                    ];
                    foreach ($stats as [$lbl,$val,$unit,$color]):
                    ?>
                    <div class="stat-box">
                        <div class="stat-val" style="color:<?= $color ?>;"><?= $val ?></div>
                        <div class="stat-lbl"><?= $lbl ?></div>
                        <div style="font-size:10px;color:#b0a898;"><?= $unit ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nutri-Score (calculé par nutriScore() dans helpers) -->
            <div style="text-align:center;flex-shrink:0;">
                <div class="ns-box" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;margin:0 auto 6px;">
                    <?= $ns['grade'] ?>
                </div>
                <p style="font-size:11px;color:#9ca3af;">Nutri-Score</p>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 7 — DÉTAIL COMPLET (2 colonnes)
     VALEUR AJOUTÉE principale vs fo_alimentlist :
       - Graphique donut interactif (Chart.js)
       - Toutes les barres nutritionnelles détaillées
       - Section allergènes visuellement mise en avant
       - Section impact écologique détaillée
       - Bouton "Ajouter à un repas" fonctionnel
     ══════════════════════════════════════════════════════════ -->
<div class="max-w-screen-xl mx-auto px-8 py-10">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- ── COLONNE GAUCHE ─────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Carte : Valeurs nutritionnelles complètes + donut -->
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:18px;">
                    Valeurs nutritionnelles
                    <span style="font-size:14px;font-weight:400;color:#9ca3af;font-family:'Lato',sans-serif;">pour 100g</span>
                </h2>

                <!-- Graphique donut : répartition calorique des macros
                     VALEUR AJOUTÉE : absent de fo_alimentlist       -->
                <div style="display:flex;align-items:center;gap:24px;margin-bottom:22px;">
                    <div class="donut-wrap">
                        <canvas id="donut" width="150" height="150"></canvas>
                        <div class="donut-center">
                            <p style="font-size:18px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;margin:0;">
                                <?= number_format($a['calories'],0) ?>
                            </p>
                            <p style="font-size:10px;color:#9ca3af;margin:0;">kcal</p>
                        </div>
                    </div>
                    <!-- Légende du donut -->
                    <div style="flex:1;">
                        <?php
                        $legend = [
                            ['#60a5fa', 'Protéines', $mp['prot']],
                            ['#c9a44a', 'Glucides',  $mp['gluc']],
                            ['#a78bfa', 'Lipides',   $mp['lip']],
                        ];
                        foreach ($legend as [$col,$lbl,$pct]):
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:10px;height:10px;border-radius:50%;background:<?= $col ?>;flex-shrink:0;"></div>
                            <span style="font-size:12px;color:#6b7280;flex:1;"><?= $lbl ?></span>
                            <span style="font-size:13px;font-weight:700;color:var(--vert);"><?= $pct ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Barres détaillées de TOUS les nutriments
                     VALEUR AJOUTÉE : fo_alimentlist ne montre
                     que 4 barres. Ici on a tout + sodium.       -->
                <?php
                $macros = [
                    ['Protéines',  $a['proteines'], 50,   '#60a5fa', 'g/100g'],
                    ['Glucides',   $a['glucides'],  100,  '#c9a44a', 'g/100g'],
                    ['dont sucre', $a['sucre'],     100,  '#e8c06a', 'g/100g'],
                    ['Lipides',    $a['lipides'],   50,   '#a78bfa', 'g/100g'],
                    ['Fibres',     $a['fibres'],    30,   '#1a372f', 'g/100g'],
                    ['Sodium',     $a['sodium'],    2300, '#888780', 'mg/100g'],
                ];
                foreach ($macros as [$lbl, $val, $max, $color, $unit]):
                    $pct    = $max > 0 ? min(100, $val / $max * 100) : 0;
                    $indent = str_starts_with($lbl, 'dont') ? 'padding-left:18px;' : '';
                ?>
                <div class="mrow" style="<?= $indent ?>">
                    <span style="font-size:12px;color:#6b7280;width:100px;flex-shrink:0;"><?= $lbl ?></span>
                    <div class="mtrack"><div class="mfill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
                    <span style="font-size:12px;font-weight:700;color:var(--vert);width:75px;text-align:right;flex-shrink:0;">
                        <?= number_format($val,1) ?> <?= $unit ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Vitamines (absent de fo_alimentlist) -->
                <?php if (!empty($a['vitamines'])): ?>
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f4ede4;">
                    <p style="font-size:11px;color:#9ca3af;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Vitamines</p>
                    <p style="font-size:14px;font-weight:500;color:var(--vert);"><?= htmlspecialchars($a['vitamines']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Carte : Allergènes (absent de fo_alimentlist)
                 VALEUR AJOUTÉE : information de sécurité critique -->
            <?php if (!empty($a['allergenes'])): ?>
            <div class="card" style="border-color:#c09090;background:#fdf5f5;">
                <h2 class="hf" style="font-size:22px;color:#8a2020;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:16px;"></i> Allergènes
                </h2>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach (explode(',', $a['allergenes']) as $al): ?>
                    <span style="background:#faeaea;color:#8a2020;padding:5px 14px;border-radius:99px;font-size:13px;font-weight:500;">
                        <?= htmlspecialchars(trim($al)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── COLONNE DROITE ─────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Carte : Impact écologique détaillé
                 VALEUR AJOUTÉE : fo_alimentlist montre juste un
                 badge CO₂ (Faible/Moyen/Élevé). Ici on a la
                 valeur exacte, la barre, la description.        -->
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:18px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-leaf" style="font-size:16px;color:var(--vert);"></i> Impact écologique
                </h2>

                <!-- Bloc CO₂ -->
                <div style="background:<?= $co2['bg'] ?>;border-radius:14px;padding:18px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                        <div>
                            <p style="font-size:28px;font-weight:700;color:<?= $co2['color'] ?>;font-family:'Cormorant Garamond',serif;margin:0;">
                                <?= number_format($a['co2'],2) ?> kg CO₂eq
                            </p>
                            <p style="font-size:12px;color:<?= $co2['color'] ?>;opacity:.75;margin:3px 0 0;">par kg d'aliment</p>
                        </div>
                        <span style="background:<?= $co2['color'] ?>;color:white;padding:6px 16px;border-radius:99px;font-size:13px;font-weight:600;">
                            <?= $co2['label'] ?>
                        </span>
                    </div>
                    <!-- Barre de progression CO₂ -->
                    <div style="height:8px;border-radius:4px;background:rgba(0,0,0,.1);overflow:hidden;">
                        <div style="width:<?= $co2['pct'] ?>%;height:100%;background:<?= $co2['color'] ?>;border-radius:4px;"></div>
                    </div>
                    <p style="font-size:12px;color:<?= $co2['color'] ?>;margin:8px 0 0;opacity:.8;"><?= $co2['desc'] ?></p>
                </div>

                <!-- Label écologique -->
                <?php if (!empty($a['label_ecologique'])): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#e8f0e9;border-radius:12px;margin-bottom:10px;">
                    <i class="fas fa-certificate" style="color:var(--vert);font-size:22px;"></i>
                    <div>
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;">Label écologique</p>
                        <p style="font-size:13px;color:#4a7a50;margin:2px 0 0;"><?= htmlspecialchars($a['label_ecologique']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Origine -->
                <?php if (!empty($a['origine'])): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--sable);border-radius:12px;">
                    <i class="fas fa-map-marker-alt" style="color:var(--vert);font-size:20px;"></i>
                    <div>
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;">Origine</p>
                        <p style="font-size:13px;color:#6b7280;margin:2px 0 0;"><?= htmlspecialchars($a['origine']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Carte : Prix -->
            <?php if ($a['prix'] > 0): ?>
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-tag" style="font-size:16px;"></i> Prix indicatif
                </h2>
                <div style="display:flex;align-items:baseline;gap:6px;">
                    <span style="font-size:40px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;">
                        <?= number_format($a['prix'],2) ?>
                    </span>
                    <span style="font-size:15px;color:#9ca3af;">TND / kg</span>
                </div>
                <p style="font-size:12px;color:#9ca3af;margin-top:6px;">Prix moyen indicatif sur le marché tunisien</p>
            </div>
            <?php endif; ?>

            <!-- ══ BOUTON AJOUTER À UN REPAS ════════════════
                 VALEUR AJOUTÉE principale :
                 Depuis fo_alimentlist on ne peut QUE consulter.
                 Depuis fo_alimentdetail on peut AJOUTER
                 directement cet aliment à un repas.
                 Ce bouton renvoie vers fo_repaslist.php avec
                 l'id de l'aliment pré-sélectionné.
                 ══════════════════════════════════════════════ -->
            <div class="card" style="background:linear-gradient(135deg,<?= $c['bg'] ?> 0%,white 100%);border-color:<?= $c['stroke'] ?>44;">
                <h2 class="hf" style="font-size:22px;color:var(--vert);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-utensils" style="font-size:15px;"></i> Utiliser cet aliment
                </h2>
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;line-height:1.6;">
                    Ajoutez <strong><?= htmlspecialchars($a['nom']) ?></strong> à l'un de vos repas
                    pour suivre votre apport nutritionnel et votre impact CO₂.
                </p>
                <!-- Lien vers fo_repaslist.php avec l'aliment pré-sélectionné -->
                <a href="fo_repaslist.php?add_aliment=<?= $a['id_aliment'] ?>"
                   style="display:inline-flex;align-items:center;gap:8px;background:var(--vert);color:white;padding:11px 22px;border-radius:99px;font-size:13px;font-weight:600;text-decoration:none;">
                    <i class="fas fa-plus"></i> Ajouter à un repas
                </a>
            </div>

            <!-- Lien retour vers la liste -->
            <a href="fo_alimentlist.php"
               style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:16px;border:1.5px solid #d0c8be;background:white;font-size:14px;font-weight:500;color:#4b5563;text-decoration:none;transition:all .15s;"
               onmouseover="this.style.borderColor='var(--vert)';this.style.color='var(--vert)'"
               onmouseout="this.style.borderColor='#d0c8be';this.style.color='#4b5563'">
                <i class="fas fa-arrow-left" style="font-size:12px;"></i> Retour à la liste des aliments
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 8 — JAVASCRIPT
     Curseur + graphique donut Chart.js
     ══════════════════════════════════════════════════════════ -->
<script>
/* ── Curseur personnalisé ─────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'), t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button').forEach(el=>{
        el.addEventListener('mouseenter',()=>c.classList.add('h'));
        el.addEventListener('mouseleave',()=>c.classList.remove('h'));
    });
})();

/* ── Graphique donut — répartition calorique des macros ─────
   Les données viennent de macroPercents() calculé en PHP.
   VALEUR AJOUTÉE : visualisation absente de fo_alimentlist   */
new Chart(document.getElementById('donut').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Protéines', 'Glucides', 'Lipides'],
        datasets: [{
            data: [<?= $mp['prot'] ?>, <?= $mp['gluc'] ?>, <?= $mp['lip'] ?>],
            backgroundColor: ['#60a5fa', '#c9a44a', '#a78bfa'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label} : ${ctx.raw}%` } }
        },
        animation: { duration: 900 }
    }
});
</script>
</body>
</html>
