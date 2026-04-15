<?php
/* ============================================================
   view/BackOffice/alimentlist.php
   RÔLE : Vue Back Office — Gestion CRUD des aliments (admin)
   ARCHITECTURE MVC :
     - Model  : model/aliment.php      (requêtes BDD)
     - View   : ce fichier             (affichage HTML)
     - Controller : controller/alimentcontroller.php (logique)
   ============================================================ */

/* ----------------------------------------------------------
   SECTION 1 — CHARGEMENT DES DÉPENDANCES
   Inclut la connexion BDD, le modèle Aliment et les fonctions
   utilitaires partagées (SVG, couleurs, nutriscore…)
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DES DONNÉES (appel au Model)
   On instancie le Model et on récupère tous les aliments triés
   par ordre alphabétique depuis la BDD.
   ---------------------------------------------------------- */
$alimentModel = new Aliment();
$aliments     = $alimentModel->getAll();

/* ----------------------------------------------------------
   SECTION 3 — LECTURE DU MESSAGE DE SUCCÈS (GET param)
   Après une création/modification/suppression, le Controller
   redirige ici avec ?success=created|updated|deleted
   ---------------------------------------------------------- */
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Back Office — Aliments</title>

    <!-- Tailwind CSS (framework utilitaire) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome (icônes) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ── Importation des polices GaiaLumen ──────────────
           Cormorant Garamond → titres élégants
           Lato               → textes lisibles sur écran    */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');

        /* ── Palette officielle GaiaLumen ───────────────────
           Vert Profond    : #1a372f
           Sable Sacré     : #f4ede4
           Violet Mystique : #a78bfa
           Bleu Céleste    : #60a5fa                         */
        :root {
            --vert   : #1a372f;
            --sable  : #f4ede4;
            --violet : #a78bfa;
            --bleu   : #60a5fa;
        }

        /* ── Reset de base ──────────────────────────────── */
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; } /* classe pour les titres */
        body { background:var(--sable); color:var(--vert); display:flex; min-height:100vh; }

        /* ── Curseur personnalisé GaiaLumen ─────────────────
           Boule violette qui suit la souris                 */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ══════════════════════════════════════════════════
           SIDEBAR — MENU VERTICAL GAUCHE
           Structure : sidebar fixe à gauche + contenu à droite
           ══════════════════════════════════════════════════ */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--vert) 0%, #11241f 100%);
            position: fixed;
            left: 0; top: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,.15);
        }
        /* Logo en haut de la sidebar */
        .sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .sidebar-logo span {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            color: white;
            letter-spacing: -0.03em;
        }
        /* Badge "Back Office" sous le logo */
        .sidebar-badge {
            margin: 0 20px 16px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--violet);
            padding: 5px 10px;
            background: rgba(167,139,250,.15);
            border-radius: 6px;
            text-align: center;
        }
        /* Titre de groupe dans le menu */
        .sidebar-section {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            padding: 14px 20px 6px;
        }
        /* Item de navigation */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 20px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all .18s;
            border-left: 3px solid transparent;
        }
        .nav-item:hover { background: rgba(255,255,255,.07); color: white; }
        /* Item actif → surlignage violet */
        .nav-item.active {
            background: rgba(167,139,250,.18);
            color: var(--violet);
            border-left-color: var(--violet);
        }
        .nav-item i { width: 16px; text-align: center; font-size: 13px; }
        /* Pied de la sidebar */
        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: 11px;
            color: rgba(255,255,255,.35);
        }

        /* ── Zone de contenu principal (à droite de la sidebar) */
        .main-content {
            margin-left: 240px; /* décalage = largeur sidebar */
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* ── Topbar (barre du haut dans le contenu) ──────── */
        .topbar {
            background: linear-gradient(135deg, var(--vert) 0%, #11241f 100%);
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ── Hero avec photo de fond ─────────────────────────
           La photo 1000051721.jpg est dans assets/images/
           Le chemin relatif est correct depuis BackOffice/    */
        .hero {
            background-image:
                linear-gradient(rgba(26,55,47,.72), rgba(26,55,47,.72)),
                url('assets/images/1000051721.jpg');
            background-size: cover;
            background-position: center;
            padding: 48px 28px 36px;
        }

        /* ── Filtres (boutons de filtre par type) ─────────── */
        .fb {
            padding:6px 16px;border-radius:99px;
            border:1.5px solid rgba(255,255,255,.3);
            background:transparent;font-size:12px;
            color:rgba(255,255,255,.75);cursor:pointer;
            transition:all .18s;font-family:'Lato',sans-serif;font-weight:500;
        }
        .fb.on,.fb:hover { background:var(--violet);color:white;border-color:var(--violet); }

        /* ── Tableau des aliments ────────────────────────────
           Grille CSS avec 13 colonnes pour toutes les données */
        .tbl-wrap { background:white;border-radius:20px;overflow:hidden;border:1px solid rgba(26,55,47,.1); }
        .thead {
            display:grid;
            grid-template-columns:48px 1.5fr .7fr .7fr .6fr .6fr .6fr .6fr .6fr .6fr .9fr 1.1fr 90px;
            align-items:center;padding:11px 16px;
            background:var(--vert);color:white;
            font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
        }
        .trow {
            display:grid;
            grid-template-columns:48px 1.5fr .7fr .7fr .6fr .6fr .6fr .6fr .6fr .6fr .9fr 1.1fr 90px;
            align-items:center;padding:12px 16px;
            border-bottom:1px solid #f4ede4;transition:background .12s;
            text-decoration:none;color:inherit;
        }
        .trow:hover { background:#faf7f3; }
        .trow:last-child { border-bottom:none; }

        /* ── Badges de type et catégorie ─────────────────── */
        .badge  { display:inline-block;font-size:9.5px;padding:2px 8px;border-radius:99px;font-weight:600;white-space:nowrap; }
        .cbadge { font-size:9.5px;padding:1px 6px;border-radius:4px;background:#f4ede4;color:#5a5850; }

        /* ── Barre CO₂ ───────────────────────────────────── */
        .cobar { height:5px;border-radius:3px;background:#ede9e3;overflow:hidden;margin-top:3px; }
        .cofil { height:100%;border-radius:3px; }

        /* ── Boutons action (Edit / Delete) ──────────────── */
        .bedit { display:inline-flex;align-items:center;padding:5px 10px;border-radius:8px;font-size:11px;border:1px solid var(--bleu);color:#1a5fa8;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bedit:hover { background:#e8f2fc; }
        .bdel  { display:inline-flex;align-items:center;padding:5px 10px;border-radius:8px;font-size:11px;border:1px solid #c09090;color:#8a2020;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bdel:hover  { background:#faeaea; }

        /* ── Recherche (input dans le hero) ──────────────── */
        .si { padding:8px 14px 8px 36px;border-radius:99px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);font-size:12px;color:white;outline:none;font-family:'Lato',sans-serif;width:210px; }
        .si::placeholder { color:rgba(255,255,255,.5); }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:11px; }

        /* ── Champs du formulaire modal ──────────────────── */
        .mi { width:100%;padding:9px 14px;border-radius:12px;border:1.5px solid #e8e0d8;font-family:'Lato',sans-serif;font-size:13px;outline:none;background:white;color:var(--vert); }
        .mi:focus { border-color:var(--violet); }
        .mi.error { border-color:#c09090;background:#fdf5f5; } /* erreur de validation */
        label.lbl { display:block;font-size:11px;font-weight:600;color:var(--vert);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em; }

        /* ── Message d'erreur de validation ─────────────── */
        .err-msg { font-size:11px;color:#8a2020;margin-top:4px;display:none; }
        .err-msg.show { display:block; }

        /* ── Stat pills dans le hero ─────────────────────── */
        .sp { background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* ── Modal (popup de création) ───────────────────── */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white;border-radius:24px;width:100%;max-width:680px;margin:0 16px;max-height:90vh;overflow-y:auto; }
    </style>
</head>
<body>
<div id="cur"></div>
<div id="curt"></div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 4 — SIDEBAR (menu vertical gauche)
     Navigation de l'administration du module Gestion Repas
     ══════════════════════════════════════════════════════════ -->
<aside class="sidebar">

    <!-- Logo GaiaLumen -->
    <a href="../../index.html" class="sidebar-logo">
        <svg width="30" height="30" viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
            <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#60a5fa"/>
                <stop offset="100%" stop-color="#a78bfa"/>
            </radialGradient></defs>
            <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
        </svg>
        <span>GaiaLumen</span>
    </a>

    <!-- Badge administration -->
    <div class="sidebar-badge">⚙ Back Office</div>

    <!-- Groupe : Module Repas (CRUD principaux) -->
    <div class="sidebar-section">Module Repas</div>

    <!-- Lien vers la liste des aliments (page actuelle) -->
    <a href="alimentlist.php" class="nav-item active">
        <i class="fas fa-carrot"></i> Aliments
    </a>
    <!-- Lien vers la liste des repas (à venir) -->
    <a href="#" class="nav-item">
        <i class="fas fa-utensils"></i> Repas
    </a>

    <!-- Groupe : Navigation générale du site -->
    <div class="sidebar-section">Site</div>

    <a href="../../index.html" class="nav-item">
        <i class="fas fa-home"></i> Accueil
    </a>
    <a href="../FrontOffice/alimentlist.php" class="nav-item">
        <i class="fas fa-eye"></i> Vue utilisateur
    </a>

    <!-- Pied de sidebar -->
    <div class="sidebar-footer">GaiaLumen © <?= date('Y') ?></div>
</aside>

<!-- ══════════════════════════════════════════════════════════
     SECTION 5 — ZONE DE CONTENU PRINCIPALE
     Tout ce qui s'affiche à droite de la sidebar
     ══════════════════════════════════════════════════════════ -->
<div class="main-content">

    <!-- ── Topbar (barre supérieure) ────────────────────── -->
    <div class="topbar">
        <div class="text-white text-sm font-medium flex items-center gap-2">
            <i class="fas fa-shield-alt text-[#a78bfa]"></i>
            Administration › Gestion des Aliments
        </div>
        <!-- Bouton "Nouvel aliment" dans la topbar -->
        <button onclick="openModal()"
            style="background:var(--violet);color:white;padding:7px 18px;border-radius:99px;font-size:12px;font-weight:600;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <i class="fas fa-plus" style="font-size:10px;"></i> Nouvel aliment
        </button>
    </div>

    <!-- ── Hero avec photo de fond ──────────────────────────
         La photo 1000051721.jpg sert de fond avec un overlay
         vert foncé pour rendre le texte lisible             -->
    <section class="hero">
        <h1 class="hf" style="font-size:48px;color:white;line-height:1;margin-bottom:10px;">
            Gestion des Aliments
        </h1>
        <p style="font-size:14px;color:rgba(255,255,255,.7);margin-bottom:18px;">
            Entre nature et lumière, votre nutrition prend sens.
        </p>

        <!-- Statistiques rapides (calculées depuis la BDD) -->
        <?php
        $nb  = count($aliments);
        $nbt = count(array_unique(array_column($aliments,'type')));
        ?>
        <div class="flex gap-3 flex-wrap mb-6">
            <span class="sp"><b><?= $nb ?></b> aliments</span>
            <span class="sp"><b><?= $nbt ?></b> types</span>
        </div>

        <!-- ══════════════════════════════════════════════════
             SECTION 6 — CRUD : BOUTON CRÉER
             Point d'entrée du Create (C du CRUD)
             Ouvre la modale de création d'un aliment
             ══════════════════════════════════════════════════ -->
        <button onclick="openModal()"
            style="background:white;color:var(--vert);padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;font-family:'Lato',sans-serif;">
            <i class="fas fa-plus"></i> Créer un aliment
        </button>

        <!-- ══════════════════════════════════════════════════
             SECTION 7 — FONCTIONNALITÉS HORS CRUD
             (Filtre par type + Recherche par nom)
             Ces boutons ne modifient pas la BDD — ils filtrent
             uniquement l'affichage côté client (JavaScript)
             ══════════════════════════════════════════════════ -->
        <div style="margin-bottom:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em;">Filtrer l'affichage</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;" id="fbar">
                <button class="fb on" data-type="tous"               onclick="setFilter(this)">Tous</button>
                <button class="fb"    data-type="légume"             onclick="setFilter(this)">Légumes</button>
                <button class="fb"    data-type="fruit"              onclick="setFilter(this)">Fruits</button>
                <button class="fb"    data-type="protéines animales" onclick="setFilter(this)">Protéines</button>
                <button class="fb"    data-type="céréale"            onclick="setFilter(this)">Céréales</button>
                <button class="fb"    data-type="légumineuse"        onclick="setFilter(this)">Légumineuses</button>
                <button class="fb"    data-type="produit laitier"    onclick="setFilter(this)">Laitiers</button>
                <button class="fb"    data-type="épice"              onclick="setFilter(this)">Épices</button>
                <button class="fb"    data-type="huile"              onclick="setFilter(this)">Huiles</button>

                <!-- Recherche par nom (filtre côté client, pas de requête BDD) -->
                <div class="sw" style="margin-left:8px;">
                    <i class="fas fa-search"></i>
                    <input id="sq" type="text" class="si" placeholder="Rechercher un aliment…" oninput="applyFilters()">
                </div>
            </div>
        </div>
    </section>

    <!-- ── Message de succès après CRUD ─────────────────── -->
    <div style="padding:0 28px;">
    <?php if ($success): ?>
        <div style="background:#e8f0e9;border:1px solid #1a372f30;color:var(--vert);padding:12px 20px;border-radius:12px;margin:16px 0;display:flex;align-items:center;gap:8px;font-size:13px;">
            <i class="fas fa-check-circle"></i>
            <?php
                /* Affiche le bon message selon l'action effectuée */
                if ($success==='created') echo 'Aliment ajouté avec succès.';
                if ($success==='updated') echo 'Aliment modifié avec succès.';
                if ($success==='deleted') echo 'Aliment supprimé.';
            ?>
        </div>
    <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECTION 8 — CRUD : READ (tableau des aliments)
         Affiche TOUS les attributs de chaque aliment :
         icône, nom, type, catégorie, calories, protéines,
         glucides, lipides, fibres, sucre, sodium, prix,
         CO₂, vitamines, origine, label_eco, allergènes
         + boutons Edit (Update) et Delete
         ══════════════════════════════════════════════════════ -->
    <div style="padding:16px 28px 40px;">
        <div class="tbl-wrap" style="overflow-x:auto;">

            <!-- En-tête du tableau -->
            <div class="thead" style="min-width:1100px;">
                <div></div>
                <div>Aliment</div>
                <div style="text-align:center" title="Calories pour 100g">Cal.</div>
                <div style="text-align:center" title="Protéines pour 100g">Prot.</div>
                <div style="text-align:center" title="Glucides pour 100g">Gluc.</div>
                <div style="text-align:center" title="Lipides pour 100g">Lip.</div>
                <div style="text-align:center" title="Fibres pour 100g">Fibres</div>
                <div style="text-align:center" title="Sucre pour 100g">Sucre</div>
                <div style="text-align:center" title="Sodium pour 100g">Sodium</div>
                <div style="text-align:center" title="Prix en TND par kg">Prix</div>
                <div title="Impact carbone">CO₂</div>
                <div title="Vitamines, origine, label, allergènes">Infos</div>
                <!-- Colonne Actions = Update (✏) + Delete (🗑) -->
                <div style="text-align:center">Actions</div>
            </div>

            <!-- Corps du tableau : une ligne par aliment -->
            <div id="tbody" style="min-width:1100px;">
            <?php if (empty($aliments)): ?>
                <!-- État vide : aucun aliment dans la BDD -->
                <div style="padding:48px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-seedling" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
                    Aucun aliment. Commencez par en créer un !
                </div>
            <?php else: ?>
                <?php foreach ($aliments as $a):
                    /* Calcul des couleurs et SVG pour chaque aliment */
                    $c   = typeConfig($a['type']);
                    $svg = alimentSVG($a['nom'], $a['type'], $c, 40);
                    $co2 = co2Config((float)$a['co2']);
                ?>
                <!-- Ligne aliment : data-type et data-nom servent au filtre JS -->
                <div class="trow"
                     data-type="<?= htmlspecialchars($a['type']) ?>"
                     data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

                    <!-- Icône SVG générée dynamiquement selon le nom de l'aliment -->
                    <div style="display:flex;align-items:center;justify-content:center;"><?= $svg ?></div>

                    <!-- Nom + badges type + catégorie -->
                    <div style="padding-left:10px;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <div style="display:flex;gap:4px;margin-top:3px;flex-wrap:wrap;">
                            <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                            <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                        </div>
                    </div>

                    <!-- Valeur : Calories -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['calories'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">kcal/100g</p>
                    </div>

                    <!-- Valeur : Protéines -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Glucides -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Lipides -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Fibres -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['fibres'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Sucre -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sucre'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Sodium -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sodium'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">mg/100g</p>
                    </div>

                    <!-- Valeur : Prix -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['prix'],2) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">TND/kg</p>
                    </div>

                    <!-- Barre CO₂ : couleur verte/orange/rouge selon l'impact -->
                    <div style="padding:0 6px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                            <span style="font-size:10px;color:#6b7280;"><?= number_format($a['co2'],2) ?> kg</span>
                            <span style="font-size:9px;background:<?= $co2['bg'] ?>;color:<?= $co2['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co2['label'] ?></span>
                        </div>
                        <div class="cobar"><div class="cofil" style="width:<?= $co2['pct'] ?>%;background:<?= $co2['color'] ?>;"></div></div>
                    </div>

                    <!-- Infos complémentaires : vitamines, origine, label, allergènes -->
                    <div>
                        <?php if (!empty($a['vitamines'])): ?>
                        <p style="font-size:10px;color:#6b7280;margin:0 0 2px;"><span style="font-weight:600;">Vit:</span> <?= htmlspecialchars($a['vitamines']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['origine'])): ?>
                        <p style="font-size:10px;color:#6b7280;margin:0 0 2px;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['label_ecologique'])): ?>
                        <p style="font-size:10px;color:#1a372f;margin:0 0 2px;">🌱 <?= htmlspecialchars($a['label_ecologique']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['allergenes'])): ?>
                        <p style="font-size:10px;color:#8a2020;margin:0;">⚠ <?= htmlspecialchars($a['allergenes']) ?></p>
                        <?php endif; ?>
                        <?php if (empty($a['vitamines']) && empty($a['origine']) && empty($a['label_ecologique']) && empty($a['allergenes'])): ?>
                        <p style="font-size:10px;color:#c4bdb5;">—</p>
                        <?php endif; ?>
                    </div>

                    <!-- ── Actions CRUD : Update + Delete ──
                         Update → redirige vers updatealiment.php
                         Delete → appelle le Controller via GET ?action=delete  -->
                    <div style="display:flex;gap:5px;justify-content:center;">
                        <!-- Bouton UPDATE (modifier) -->
                        <a href="updatealiment.php?id=<?= $a['id_aliment'] ?>" class="bedit" title="Modifier cet aliment">
                            <i class="fas fa-pen" style="font-size:10px;"></i>
                        </a>
                        <!-- Bouton DELETE (supprimer) avec confirmation JavaScript -->
                        <a href="../../controller/alimentcontroller.php?action=delete&id=<?= $a['id_aliment'] ?>"
                           onclick="return confirm('Supprimer «<?= htmlspecialchars($a['nom']) ?>» ? Cette action est irréversible.')"
                           class="bdel" title="Supprimer cet aliment">
                            <i class="fas fa-trash" style="font-size:10px;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- État vide après filtrage (caché par défaut) -->
                <div id="noResult" style="display:none;padding:40px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-filter" style="font-size:1.8rem;display:block;margin-bottom:10px;color:#d0c8be;"></i>Aucun résultat pour ce filtre.
                </div>
            <?php endif; ?>
            </div>

            <!-- Pied du tableau avec compteur -->
            <div style="padding:10px 16px;background:#f9f6f2;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;" id="rowCount"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen Back Office © <?= date('Y') ?></span>
            </div>
        </div>
    </div>

</div><!-- fin .main-content -->


<!-- ══════════════════════════════════════════════════════════
     SECTION 9 — CRUD : CREATE (modal de création)
     Formulaire envoyé en POST vers le Controller
     Le Controller appelle Model->create() puis redirige ici
     ══════════════════════════════════════════════════════════ -->
<div id="modalOverlay" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div style="padding:28px 32px;">

            <!-- Titre de la modal -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Nouvel Aliment</h3>
                <button onclick="closeModal()" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>

            <!-- Formulaire CREATE → envoi POST au Controller -->
            <form id="createForm" action="../../controller/alimentcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <!-- Nom de l'aliment (obligatoire, min 2 caractères) -->
                    <div style="grid-column:1/-1;">
                        <label class="lbl">Nom de l'aliment *</label>
                        <input type="text" name="nom" id="f_nom" class="mi" placeholder="ex: Carotte bio"
                               minlength="2" maxlength="200">
                        <p class="err-msg" id="e_nom">Le nom est obligatoire (min. 2 caractères).</p>
                    </div>

                    <!-- Type (obligatoire, sélection parmi les valeurs ENUM de la BDD) -->
                    <div>
                        <label class="lbl">Type *</label>
                        <select name="type" id="f_type" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="légume">Légume</option>
                            <option value="fruit">Fruit</option>
                            <option value="céréale">Céréale</option>
                            <option value="protéines animales">Protéines animales</option>
                            <option value="légumineuse">Légumineuse</option>
                            <option value="produit laitier">Produit laitier</option>
                            <option value="huile">Huile</option>
                            <option value="épice">Épice</option>
                            <option value="autre">Autre</option>
                        </select>
                        <p class="err-msg" id="e_type">Veuillez choisir un type.</p>
                    </div>

                    <!-- Catégorie (obligatoire) -->
                    <div>
                        <label class="lbl">Catégorie *</label>
                        <select name="categorie" id="f_cat" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="frais">Frais</option>
                            <option value="sec">Sec</option>
                            <option value="transformé">Transformé</option>
                            <option value="ultra-transformé">Ultra-transformé</option>
                        </select>
                        <p class="err-msg" id="e_cat">Veuillez choisir une catégorie.</p>
                    </div>

                    <!-- Valeurs nutritionnelles (obligatoires, nombres >= 0) -->
                    <?php
                    /* Tableau des champs numériques obligatoires */
                    $numFields = [
                        ['calories',  'Calories (kcal/100g)', true,  0, 9999],
                        ['proteines', 'Protéines (g/100g)',   true,  0, 100],
                        ['glucides',  'Glucides (g/100g)',    true,  0, 100],
                        ['lipides',   'Lipides (g/100g)',     true,  0, 100],
                        ['fibres',    'Fibres (g/100g)',      false, 0, 100],
                        ['sucre',     'Sucre (g/100g)',       false, 0, 100],
                        ['sodium',    'Sodium (mg/100g)',     false, 0, 5000],
                        ['co2',       'CO₂ (kg CO₂eq/kg)',   false, 0, 100],
                        ['prix',      'Prix (TND/kg)',        false, 0, 9999],
                    ];
                    foreach ($numFields as [$name, $label, $required, $min, $max]):
                    ?>
                    <div>
                        <label class="lbl"><?= $label ?> <?= $required ? '*' : '' ?></label>
                        <input type="number" step="0.01" name="<?= $name ?>" id="f_<?= $name ?>"
                               class="mi" value="0"
                               min="<?= $min ?>" max="<?= $max ?>"
                               <?= $required ? 'required' : '' ?>>
                        <p class="err-msg" id="e_<?= $name ?>">
                            Valeur entre <?= $min ?> et <?= $max ?>.
                        </p>
                    </div>
                    <?php endforeach; ?>

                    <!-- Vitamines (facultatif, texte libre) -->
                    <div style="grid-column:1/-1;">
                        <label class="lbl">Vitamines</label>
                        <input type="text" name="vitamines" class="mi"
                               placeholder="ex: A, B12, C" maxlength="200">
                    </div>

                    <!-- Label écologique (facultatif) -->
                    <div style="grid-column:1/-1;">
                        <label class="lbl">Label écologique</label>
                        <input type="text" name="label_ecologique" class="mi"
                               placeholder="bio, AOP, conventionnel…" maxlength="100">
                    </div>

                    <!-- Origine (facultatif) -->
                    <div>
                        <label class="lbl">Origine</label>
                        <input type="text" name="origine" class="mi"
                               placeholder="Tunisie, France…" maxlength="100">
                    </div>

                    <!-- Allergènes (facultatif) -->
                    <div>
                        <label class="lbl">Allergènes</label>
                        <input type="text" name="allergenes" class="mi"
                               placeholder="gluten, lait, arachide…" maxlength="255">
                    </div>
                </div>

                <!-- Boutons Annuler / Enregistrer -->
                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button type="button" onclick="closeModal()"
                        style="flex:1;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <!-- Bouton soumet après validation JS -->
                    <button type="button" onclick="validateAndSubmit()"
                        style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     SECTION 10 — JAVASCRIPT
     10a. Curseur personnalisé
     10b. Modal (ouvrir/fermer)
     10c. Validation du formulaire de création (CRUD : Create)
     10d. Filtre par type (hors CRUD — affichage client)
     10e. Recherche par nom (hors CRUD — affichage client)
     ══════════════════════════════════════════════════════════ -->
<script>

/* ── 10a. Curseur personnalisé ──────────────────────────────
   Boule violette qui suit la souris avec un léger délai      */
(function(){
    const c = document.getElementById('cur');
    const t = document.getElementById('curt');
    let mx=0, my=0, tx=0, ty=0;
    document.addEventListener('mousemove', e => {
        mx = e.clientX; my = e.clientY;
        c.style.left = mx+'px'; c.style.top = my+'px';
    });
    (function loop() {
        tx += (mx-tx) * .12; ty += (my-ty) * .12;
        t.style.left = tx+'px'; t.style.top = ty+'px';
        requestAnimationFrame(loop);
    })();
    /* Agrandit le curseur au survol des éléments interactifs */
    document.querySelectorAll('a,button,input,select').forEach(el => {
        el.addEventListener('mouseenter', () => c.classList.add('h'));
        el.addEventListener('mouseleave', () => c.classList.remove('h'));
    });
})();

/* ── 10b. Gestion de la modal de création ──────────────────
   openModal()  : affiche la modal
   closeModal() : cache la modal + réinitialise les erreurs   */
function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    /* Réinitialise les marqueurs d'erreur */
    document.querySelectorAll('.mi.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.err-msg.show').forEach(el => el.classList.remove('show'));
}

/* ── 10c. Validation du formulaire CREATE ───────────────────
   Vérifie chaque champ avant soumission au Controller.
   Affiche un message d'erreur sous le champ invalide.
   Si tout est valide → soumet le formulaire POST.            */
function validateAndSubmit() {
    let valid = true;

    /* Fonction utilitaire : marque un champ en erreur */
    function setError(inputId, errId, show) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(errId);
        if (!input || !err) return;
        if (show) {
            input.classList.add('error');
            err.classList.add('show');
            valid = false;
        } else {
            input.classList.remove('error');
            err.classList.remove('show');
        }
    }

    /* Validation : Nom (obligatoire, ≥ 2 caractères) */
    const nom = document.getElementById('f_nom').value.trim();
    setError('f_nom', 'e_nom', nom.length < 2);

    /* Validation : Type (obligatoire, doit être sélectionné) */
    const type = document.getElementById('f_type').value;
    setError('f_type', 'e_type', type === '');

    /* Validation : Catégorie (obligatoire) */
    const cat = document.getElementById('f_cat').value;
    setError('f_cat', 'e_cat', cat === '');

    /* Validation : Champs numériques obligatoires ≥ 0 */
    const numChecks = [
        { id:'f_calories',  errId:'e_calories',  min:0,    max:9999, req:true  },
        { id:'f_proteines', errId:'e_proteines', min:0,    max:100,  req:true  },
        { id:'f_glucides',  errId:'e_glucides',  min:0,    max:100,  req:true  },
        { id:'f_lipides',   errId:'e_lipides',   min:0,    max:100,  req:true  },
        { id:'f_fibres',    errId:'e_fibres',    min:0,    max:100,  req:false },
        { id:'f_sucre',     errId:'e_sucre',     min:0,    max:100,  req:false },
        { id:'f_sodium',    errId:'e_sodium',    min:0,    max:5000, req:false },
        { id:'f_co2',       errId:'e_co2',       min:0,    max:100,  req:false },
        { id:'f_prix',      errId:'e_prix',      min:0,    max:9999, req:false },
    ];

    numChecks.forEach(({ id, errId, min, max, req }) => {
        const el  = document.getElementById(id);
        const val = parseFloat(el.value);
        /* Invalide si : champ requis vide, valeur hors bornes, ou pas un nombre */
        const invalid = isNaN(val) || val < min || val > max;
        setError(id, errId, req ? invalid : (!isNaN(val) && invalid));
    });

    /* Si tout est valide → soumet le formulaire */
    if (valid) {
        document.getElementById('createForm').submit();
    } else {
        /* Scroll vers le premier champ en erreur */
        const firstError = document.querySelector('.mi.error');
        if (firstError) firstError.scrollIntoView({ behavior:'smooth', block:'center' });
    }
}


/* ══════════════════════════════════════════════════════════
   ── FONCTIONNALITÉS HORS CRUD (filtre + recherche) ──────
   Ces fonctions ne touchent pas la BDD.
   Elles filtrent uniquement l'affichage côté navigateur.
   ══════════════════════════════════════════════════════════ */

/* ── 10d. Filtre par type d'aliment ────────────────────────
   Clique sur un bouton de filtre → active ce bouton
   → appelle applyFilters() pour masquer les lignes         */
function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}

/* ── 10e. Application combinée filtre + recherche ──────────
   Parcourt toutes les lignes du tableau.
   Une ligne est visible si :
   - son data-type correspond au filtre actif (ou "tous")
   - son data-nom contient la chaîne de recherche           */
function applyFilters() {
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    const rows  = document.querySelectorAll('.trow');
    let visible = 0;

    rows.forEach(row => {
        const matchType   = (type === 'tous') || (row.dataset.type === type);
        const matchSearch = !query || row.dataset.nom.includes(query);
        const show        = matchType && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    /* Affiche/cache le message "aucun résultat" */
    document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
    /* Met à jour le compteur en pied de tableau */
    const rc = document.getElementById('rowCount');
    if (rc) rc.textContent = visible + ' aliment(s)';
}

</script>
</body>
</html>
