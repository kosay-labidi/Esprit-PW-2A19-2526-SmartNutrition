<?php
/* ============================================================
   view/FrontOffice/alimentlist.php
   RÔLE : Vue Front Office — Consultation publique des aliments
   L'utilisateur peut CONSULTER uniquement (pas de CRUD).
   La liste sert de base pour composer un repas plus tard.
   ARCHITECTURE MVC :
     - Model      : model/aliment.php (requêtes BDD)
     - View       : ce fichier        (affichage HTML)
     - Controller : alimentcontroller.php (non utilisé ici,
                    car pas d'action CUD côté front)
   ============================================================ */

/* ----------------------------------------------------------
   SECTION 1 — CHARGEMENT DES DÉPENDANCES
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DES DONNÉES (appel au Model)
   Lecture seule : on récupère tous les aliments pour les
   afficher à l'utilisateur final.
   ---------------------------------------------------------- */
$alimentModel = new Aliment();
$aliments     = $alimentModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Nos Aliments</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ── Polices : Cormorant Garamond (titres) + Lato (textes) */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');

        /* ── Palette GaiaLumen ──────────────────────────────── */
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }

        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        body { background:var(--sable); color:var(--vert); display:flex; min-height:100vh; }

        /* ── Curseur personnalisé ──────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ══════════════════════════════════════════════════
           SIDEBAR — MENU VERTICAL GAUCHE (Front Office)
           ══════════════════════════════════════════════════ */
        .sidebar {
            width: 220px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--vert) 0%, #11241f 100%);
            position: fixed;
            left: 0; top: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,.12);
        }
        .sidebar-logo {
            padding: 22px 18px 18px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .sidebar-logo span { font-family:'Cormorant Garamond',serif;font-size:20px;color:white;letter-spacing:-.03em; }
        .sidebar-section { font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.35);padding:14px 18px 6px; }
        .nav-item { display:flex;align-items:center;gap:10px;padding:11px 18px;color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;font-weight:500;transition:all .18s;border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.07);color:white; }
        .nav-item.active { background:rgba(167,139,250,.18);color:var(--violet);border-left-color:var(--violet); }
        .nav-item i { width:16px;text-align:center;font-size:13px; }
        .sidebar-footer { margin-top:auto;padding:14px 18px;border-top:1px solid rgba(255,255,255,.1);font-size:11px;color:rgba(255,255,255,.3); }

        /* ── Zone de contenu (à droite de la sidebar) ──── */
        .main-content { margin-left:220px; flex:1; display:flex; flex-direction:column; min-width:0; }

        /* ── Hero avec photo de fond ─────────────────────── */
        .hero {
            background-image:
                linear-gradient(rgba(26,55,47,.68), rgba(26,55,47,.68)),
                url('../BackOffice/assets/images/1000051721.jpg');
            background-size: cover;
            background-position: center;
            padding: 52px 28px 40px;
        }

        /* ── Filtres ─────────────────────────────────────── */
        .fb { padding:7px 16px;border-radius:99px;border:1.5px solid #d0c8be;background:white;font-size:12px;color:var(--vert);cursor:pointer;transition:all .18s;font-family:'Lato',sans-serif;font-weight:500; }
        .fb.on,.fb:hover { background:var(--vert);color:white;border-color:var(--vert); }

        /* ── Cartes aliments ─────────────────────────────── */
        .card { background:white;border-radius:20px;border:1px solid #ede8e0;overflow:hidden;transition:transform .2s,box-shadow .2s;cursor:pointer;text-decoration:none;display:block;color:inherit; }
        .card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(26,55,47,.13); }

        /* ── Badges ──────────────────────────────────────── */
        .badge  { display:inline-block;font-size:10px;padding:2px 9px;border-radius:99px;font-weight:600;white-space:nowrap; }
        .cbadge { font-size:10px;padding:1px 7px;border-radius:4px;background:#f4ede4;color:#5a5850; }

        /* ── Barres de macronutriments ───────────────────── */
        .mbar { height:5px;border-radius:3px;background:#ede8e0;overflow:hidden;margin:3px 0 7px; }
        .mfil { height:100%;border-radius:3px; }

        /* ── Recherche ───────────────────────────────────── */
        .si { padding:10px 16px 10px 40px;border-radius:99px;border:1.5px solid #d0c8be;font-size:13px;width:220px;outline:none;font-family:'Lato',sans-serif;background:white; }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px; }

        /* ── Stat pills dans le hero ─────────────────────── */
        .sp { background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* ── Toggle vue grille/liste ─────────────────────── */
        .vbtn { padding:8px 14px;border-radius:10px;border:1.5px solid #d0c8be;background:white;cursor:pointer;font-size:12px;color:#6b7280;transition:all .15s; }
        .vbtn.on { background:var(--vert);color:white;border-color:var(--vert); }

        /* ── Grille de cartes ────────────────────────────── */
        .gview { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px; }
        .gview.hide { display:none; }

        /* ── Vue liste ───────────────────────────────────── */
        .lview { display:none; }
        .lview.show { display:block; }
        .lhead { display:grid;grid-template-columns:48px 1.8fr .8fr .8fr .8fr .8fr .8fr 1.4fr;gap:0;padding:11px 16px;background:var(--vert);color:white;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-radius:16px 16px 0 0; }
        .lrow  { display:grid;grid-template-columns:48px 1.8fr .8fr .8fr .8fr .8fr .8fr 1.4fr;align-items:center;gap:0;padding:12px 16px;border-bottom:1px solid #f4ede4;transition:background .12s;text-decoration:none;color:inherit; }
        .lrow:hover { background:#faf7f3; }
        .lrow:last-child { border-bottom:none; }
        .lbody { background:white;border-radius:0 0 16px 16px;overflow:hidden;border:1px solid #ede8e0;border-top:none; }
    </style>
</head>
<body>
<div id="cur"></div>
<div id="curt"></div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 3 — SIDEBAR (menu vertical gauche)
     Navigation Front Office pour l'utilisateur
     ══════════════════════════════════════════════════════════ -->
<aside class="sidebar">
    <!-- Logo -->
    <a href="../../index.html" class="sidebar-logo">
        <svg width="28" height="28" viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
            <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs>
            <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
        </svg>
        <span>GaiaLumen</span>
    </a>

    <div class="sidebar-section">Navigation</div>
    <a href="../../index.html" class="nav-item"><i class="fas fa-home"></i> Accueil</a>
    <a href="alimentlist.php"  class="nav-item active"><i class="fas fa-carrot"></i> Aliments</a>
    <a href="#"                class="nav-item"><i class="fas fa-utensils"></i> Mes repas</a>
    <a href="#"                class="nav-item"><i class="fas fa-trophy"></i> Défis</a>
    <a href="#"                class="nav-item"><i class="fas fa-calendar"></i> Planning</a>

    <div class="sidebar-section">Mon compte</div>
    <a href="#" class="nav-item"><i class="fas fa-user-circle"></i> Profil</a>
    <a href="#" class="nav-item"><i class="fas fa-cog"></i> Paramètres</a>

    <div class="sidebar-footer">GaiaLumen © <?= date('Y') ?></div>
</aside>

<!-- ══════════════════════════════════════════════════════════
     SECTION 4 — CONTENU PRINCIPAL
     ══════════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- ── Hero avec photo de fond ─────────────────────────
         Photo : assets/images/1000051721.jpg
         Overlay vert foncé pour lisibilité du texte         -->
    <section class="hero">
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:4px 14px;border-radius:99px;font-size:11px;color:rgba(255,255,255,.85);margin-bottom:14px;">
            <i class="fas fa-leaf" style="color:var(--violet);"></i> BIBLIOTHÈQUE NUTRITIONNELLE
        </span>
        <h1 class="hf" style="font-size:54px;color:white;line-height:1;margin-bottom:10px;">Nos Aliments</h1>
        <p style="font-size:15px;color:rgba(255,255,255,.75);margin-bottom:20px;max-width:520px;line-height:1.6;">
            Explorez notre sélection d'aliments sains, locaux et durables pour composer vos repas avec conscience.
        </p>

        <!-- Statistiques issues de la BDD -->
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

        <!-- ══════════════════════════════════════════════════
             SECTION 5 — FONCTIONNALITÉS HORS CRUD
             Filtre par type + Recherche + Toggle vue
             Ces outils ne modifient pas la BDD.
             Ils filtrent uniquement l'affichage (JavaScript).
             ══════════════════════════════════════════════════ -->
        <div style="background:rgba(255,255,255,.08);border-radius:16px;padding:16px 20px;border:1px solid rgba(255,255,255,.12);">
            <p style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em;">Filtrer et rechercher</p>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;" id="fbar">
                <!-- Boutons de filtre par type d'aliment -->
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
    </section>

    <!-- ══════════════════════════════════════════════════════
         SECTION 6 — READ (affichage des aliments)
         Vue grille + vue liste — lecture seule
         Chaque carte est un lien vers la page détail
         ══════════════════════════════════════════════════════ -->
    <div style="padding:20px 28px 40px;">

        <!-- ── VUE GRILLE ─────────────────────────────────── -->
        <div id="vg" class="gview">
        <?php foreach ($aliments as $a):
            $c  = typeConfig($a['type']);
            $sv = alimentSVG($a['nom'], $a['type'], $c, 48);
            $co = co2Config((float)$a['co2']);
            $ns = nutriScore($a);
        ?>
        <!-- Carte cliquable → alimentdetail.php?id=X -->
        <a href="alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="card"
           data-type="<?= htmlspecialchars($a['type']) ?>"
           data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

            <!-- En-tête carte : icône SVG + nom + badges + nutriscore -->
            <div style="padding:18px 18px 12px;display:flex;align-items:center;gap:12px;">
                <?= $sv ?>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:700;color:var(--vert);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($a['nom']) ?></p>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <!-- Nutri-Score calculé par la fonction nutriScore() dans helpers -->
                <div style="width:30px;height:30px;border-radius:7px;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;" title="Nutri-Score <?= $ns['grade'] ?>"><?= $ns['grade'] ?></div>
            </div>

            <!-- Corps carte : barres de macronutriments -->
            <div style="padding:0 18px 14px;">
                <?php
                /* Affichage des 4 macros principaux avec barres visuelles */
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
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:#6b7280;">
                        <span><?= $l ?></span>
                        <span style="font-weight:600;color:var(--vert);"><?= number_format($v,1) ?><?= $u ?></span>
                    </div>
                    <div class="mbar"><div class="mfil" style="width:<?= $p ?>%;background:<?= $col ?>;"></div></div>
                </div>
                <?php endforeach; ?>

                <!-- Sucre et sodium en compact -->
                <div style="display:flex;gap:12px;margin-top:3px;">
                    <span style="font-size:10px;color:#6b7280;">Sucre: <b style="color:var(--vert);"><?= number_format($a['sucre'],1) ?>g</b></span>
                    <span style="font-size:10px;color:#6b7280;">Sodium: <b style="color:var(--vert);"><?= number_format($a['sodium'],1) ?>mg</b></span>
                </div>
            </div>

            <!-- Pied carte : calories, prix, CO₂, origine -->
            <div style="padding:12px 18px;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <p style="font-size:18px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;margin:0;"><?= number_format($a['calories'],0) ?> <span style="font-size:10px;font-weight:400;color:#9ca3af;font-family:'Lato',sans-serif;">kcal/100g</span></p>
                    <?php if ($a['prix'] > 0): ?>
                    <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;"><?= number_format($a['prix'],2) ?> TND/kg</p>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:9.5px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:3px 8px;border-radius:99px;font-weight:600;">CO₂ <?= $co['label'] ?></span>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:10px;color:#9ca3af;margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <!-- État vide après filtrage -->
        <div id="emptyG" style="display:none;grid-column:1/-1;text-align:center;padding:60px;color:#9ca3af;">
            <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
            Aucun aliment trouvé pour ce filtre.
        </div>
        </div>

        <!-- ── VUE LISTE ───────────────────────────────────── -->
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
            <a href="alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="lrow"
               data-type="<?= htmlspecialchars($a['type']) ?>"
               data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">
                <div style="display:flex;align-items:center;justify-content:center;"><?= $sv ?></div>
                <div style="padding-left:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <span style="font-size:11px;font-weight:700;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;padding:1px 6px;border-radius:5px;"><?= $ns['grade'] ?></span>
                    </div>
                    <div style="display:flex;gap:4px;margin-top:3px;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9.5px;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['calories'],0) ?></p><p style="font-size:9px;color:#9ca3af;margin:0;">kcal</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= $a['prix']>0 ? number_format($a['prix'],2).' TND' : '—' ?></p></div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                        <span style="font-size:10px;color:#6b7280;"><?= number_format($a['co2'],2) ?> kg</span>
                        <span style="font-size:9px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co['label'] ?></span>
                    </div>
                    <div style="height:4px;border-radius:2px;background:#ede8e0;overflow:hidden;">
                        <div style="width:<?= $co['pct'] ?>%;height:100%;background:<?= $co['color'] ?>;border-radius:2px;"></div>
                    </div>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:9.5px;color:#9ca3af;margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <div id="emptyL" style="display:none;padding:40px;text-align:center;color:#9ca3af;">Aucun résultat.</div>
            </div>

            <!-- Pied tableau liste -->
            <div style="padding:10px 16px;background:#f9f6f2;border-radius:0 0 16px 16px;border:1px solid #ede8e0;border-top:none;display:flex;justify-content:space-between;">
                <span style="font-size:11px;color:#9ca3af;" id="rc"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen © <?= date('Y') ?></span>
            </div>
        </div>

    </div>
</div><!-- fin .main-content -->

<!-- ══════════════════════════════════════════════════════════
     SECTION 7 — JAVASCRIPT (hors CRUD)
     Curseur, toggle vue, filtre par type, recherche par nom
     Aucune de ces fonctions ne modifie la BDD.
     ══════════════════════════════════════════════════════════ -->
<script>

/* ── Curseur personnalisé ─────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'), t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button,input').forEach(el=>{
        el.addEventListener('mouseenter',()=>c.classList.add('h'));
        el.addEventListener('mouseleave',()=>c.classList.remove('h'));
    });
})();

/* ── FONCTIONNALITÉS HORS CRUD ─────────────────────────────

   setView() : bascule entre la vue grille et la vue liste
   setFilter() : active un bouton de filtre par type
   applyFilters() : masque/affiche les cartes/lignes selon
                    le type sélectionné ET le texte recherché */

let cv = 'grid'; /* vue courante */

/* Bascule grille ↔ liste */
function setView(v) {
    cv = v;
    document.getElementById('vg').classList.toggle('hide', v === 'list');
    document.getElementById('vl').classList.toggle('show', v === 'list');
    document.getElementById('bg').classList.toggle('on', v === 'grid');
    document.getElementById('bl').classList.toggle('on', v === 'list');
    applyFilters(); /* recalcule le compteur */
}

/* Active le bouton de filtre cliqué */
function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}

/* Applique simultanément le filtre type + la recherche texte */
function applyFilters() {
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    let vg = 0, vl = 0;

    /* Filtre les cartes (vue grille) */
    document.querySelectorAll('#vg .card').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vg++;
    });

    /* Filtre les lignes (vue liste) */
    document.querySelectorAll('#lbody .lrow').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vl++;
    });

    document.getElementById('emptyG').style.display = vg === 0 ? 'block' : 'none';
    document.getElementById('emptyL').style.display = vl === 0 ? 'block' : 'none';
    const rc = document.getElementById('rc');
    if (rc) rc.textContent = (cv==='grid' ? vg : vl) + ' aliment(s)';
}
</script>
</body>
</html>
