<?php


/* ----------------------------------------------------------
   SECTION 1 — CHARGEMENT DES DÉPENDANCES
   Inclut la connexion BDD, le modèle Aliment et les fonctions
   utilitaires partagées (SVG, couleurs, nutriscore…)
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DES DONNÉES (appel au Model)
   On instancie le Model et on récupère tous les aliments triés
   par ordre alphabétique depuis la BDD.
   ---------------------------------------------------------- */
global $pdo;
$aliments = aliment_getAll($pdo);

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

    <!-- Tailwind CSS  -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome (icônes) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');


        :root {
            --vert   : #1a372f;
            --sable  : #f4ede4;
            --violet : #a78bfa;
            --bleu   : #60a5fa;
        }

        
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; } /* classe pour les titres */
        body { background:var(--sable); color:var(--vert); display:flex; min-height:100vh; }

        /* Curseur personnalisé GaiaLumen    */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

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
        /* Badge "Back Office"  */
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
        
        .sidebar-section {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            padding: 14px 20px 6px;
        }
        
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
        
        .nav-item.active {
            background: rgba(167,139,250,.18);
            color: var(--violet);
            border-left-color: var(--violet);
        }
        .nav-item i { width: 16px; text-align: center; font-size: 13px; }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: 11px;
            color: rgba(255,255,255,.35);
        }

        /*  Zone de contenu principal  */
        .main-content {
            margin-left: 240px; /* décalage = largeur sidebar */
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Topbar  */
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

        /*  Hero   */
        .hero {
            background-image:
                linear-gradient(rgba(26,55,47,.72), rgba(26,55,47,.72)),
                url('assets/images/1000051721.jpg');
            background-size: cover;
            background-position: center;
            padding: 48px 28px 36px;
        }

        /*  Filtres  */
        .fb {
            padding:6px 16px;border-radius:99px;
            border:1.5px solid rgba(255,255,255,.3);
            background:transparent;font-size:12px;
            color:rgba(255,255,255,.75);cursor:pointer;
            transition:all .18s;font-family:'Lato',sans-serif;font-weight:500;
        }
        .fb.on,.fb:hover { background:var(--violet);color:white;border-color:var(--violet); }

        /* Tableau des aliments  */
        .tbl-wrap { background:var(--bg-card);border-radius:20px;overflow:hidden;border:1px solid rgba(26,55,47,.1); }
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

        /*  Badges de type et catégorie  */
        .badge  { display:inline-block;font-size:9.5px;padding:2px 8px;border-radius:99px;font-weight:600;white-space:nowrap; }
        .cbadge { font-size:9.5px;padding:1px 6px;border-radius:4px;background:#f4ede4;color:#5a5850; }

        /*  Barre CO₂  */
        .cobar { height:5px;border-radius:3px;background:#ede9e3;overflow:hidden;margin-top:3px; }
        .cofil { height:100%;border-radius:3px; }

        /* Edit / Delete)  */
        .bedit { display:inline-flex;align-items:center;padding:5px 10px;border-radius:8px;font-size:11px;border:1px solid var(--bleu);color:#1a5fa8;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bedit:hover { background:#e8f2fc; }
        .bdel  { display:inline-flex;align-items:center;padding:5px 10px;border-radius:8px;font-size:11px;border:1px solid #c09090;color:#8a2020;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bdel:hover  { background:#faeaea; }

        /* ─ Recherche  */
        .si { padding:8px 14px 8px 36px;border-radius:99px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);font-size:12px;color:white;outline:none;font-family:'Lato',sans-serif;width:210px; }
        .si::placeholder { color:rgba(255,255,255,.5); }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:11px; }

        /* Champs du formulaire modal  */
        .mi { width:100%;padding:9px 14px;border-radius:12px;border:1.5px solid #e8e0d8;font-family:'Lato',sans-serif;font-size:13px;outline:none;background:white;color:var(--vert); }
        .mi:focus { border-color:var(--violet); }
        .mi.error { border-color:#c09090;background:#fdf5f5; } /* erreur de validation */
        label.lbl { display:block;font-size:11px;font-weight:600;color:var(--vert);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em; }

        /*  Message d'erreur de validation */
        .err-msg { font-size:11px;color:#8a2020;margin-top:4px;display:none; }
        .err-msg.show { display:block; }

        
        .sp { background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white;border-radius:24px;width:100%;max-width:680px;margin:0 16px;max-height:90vh;overflow-y:auto; }
    
:root {
            --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa;
            /* Variables light mode */
            --bg-page: #f4ede4;
            --bg-card: white;
            --bg-card2: white;
            --bg-input: white;
            --text-main: #1a372f;
            --text-muted: #6b7280;
            --text-label: #1a372f;
            --border-card: #ede8e0;
            --border-input: #d0c8be;
            --navbar-bg: linear-gradient(90deg,#1a372f 0%,#11241f 100%);
            --hero-overlay: rgba(26,55,47,.72);
            --modal-overlay: rgba(0,0,0,.5);
            --badge-bg: #e8f0e9;
            --badge-text: #1a372f;
            --sable-card: #f9f6f2;
        }
        body.dark {
            --bg-page: #0f1623;
            --bg-card: #1a2433;
            --bg-card2: #1e2a3a;
            --bg-input: #1e2a3a;
            --text-main: #e2e8f0;
            --text-muted: #64748b;
            --text-label: #94a3b8;
            --border-card: #243040;
            --border-input: #2d3f54;
            --navbar-bg: linear-gradient(90deg,#0d1520 0%,#0a1018 100%);
            --hero-overlay: rgba(8,15,24,.55);
            --modal-overlay: rgba(0,0,0,.75);
            --badge-bg: #1e2a3a;
            --badge-text: #94a3b8;
            --sable-card: #1a2433;
        }

        body.dark .topbar { background:linear-gradient(135deg,#0d1520 0%,#0a1018 100%) !important; }
        body.dark .hero { background:linear-gradient(rgba(8,15,24,.60),rgba(8,15,24,.60)) center/cover no-repeat, url('assets/images/1000051721.jpg') center/cover no-repeat !important; }
        body.dark .sidebar { background:var(--bg-card) !important; border-color:var(--border-card) !important; }
        body.dark .nav-section { color:var(--text-muted) !important; }
        body.dark .nav-item { color:var(--text-main) !important; }
        body.dark .nav-item:hover, body.dark .nav-item.active { background:rgba(96,165,250,.12) !important; color:#60a5fa !important; }
        body.dark .tbl-wrap, body.dark .thead, body.dark .trow { background:var(--bg-card) !important; border-color:var(--border-card) !important; }
        body.dark .thead { background:var(--bg-card2) !important; }
        body.dark [style*="background:white"] { background:var(--bg-card) !important; }
        body.dark [style*="color:#1a372f"] { color:var(--text-main) !important; }
        body.dark [style*="color:#6b7280"] { color:var(--text-muted) !important; }
        body.dark [style*="border:1px solid #ede8e0"] { border-color:var(--border-card) !important; }
        body.dark .modal-box { background:var(--bg-card) !important; }
        body.dark .mi { background:var(--bg-input) !important; color:var(--text-main) !important; border-color:var(--border-input) !important; }
        body.dark select.mi { background:var(--bg-input) !important; color:var(--text-main) !important; }

        body.dark #breadcrumb-liste { color:var(--text-main) !important; }
        body.dark .trow { color:var(--text-main) !important; border-color:var(--border-card) !important; }
        body.dark .trow:hover { background:var(--bg-card2) !important; }
        body.dark .trow * { color:var(--text-main) !important; }
        body.dark .trow .badge { color:#94a3b8 !important; background:var(--bg-card2) !important; }
        body.dark .tbl-wrap { background:var(--bg-card) !important; border-color:var(--border-card) !important; }
        body.dark .tbl-wrap > * { background:var(--bg-card) !important; }
        body.dark #btnCreerAliment { background:transparent !important; border:2px solid rgba(255,255,255,.6) !important; color:white !important; }
        
        /* ── Zone sous hero fond sombre ─────────── */
        body.dark [style*="padding:0 28px"] { background:var(--bg-page) !important; }
        body.dark [style*="padding:16px 28px"] { background:var(--bg-page) !important; }

        /* ── Messages succès → bleu ──────────────── */
        body.dark #msgSucces { background:rgba(96,165,250,.08) !important; border-color:#60a5fa !important; color:#60a5fa !important; }
        body.dark #msgSucces i { color:#60a5fa !important; }

        /* ── Titres formulaires blancs ───────────── */
        body.dark h3.hf { color:var(--text-main) !important; }
        body.dark label.lbl { color:var(--text-label) !important; }

        /* ── Boutons Enregistrer → mauve ─────────── */
        body.dark #btnSaveAdd, body.dark #btnSaveUpdate { background:#a78bfa !important; }

        /* ── Badges CO2 → texte noir lisible ─────── */
        body.dark span[style*="font-size:9px"][style*="background:"] { color:#111111 !important; font-weight:700 !important; }
        body.dark .trow span[style*="padding:1px"] { color:#111111 !important; font-weight:700 !important; }
        
        body.dark .cbadge { color:#111111 !important; }
        body.dark #btnAnnulerAdd, body.dark #btnAnnulerUpdate { color:#a78bfa !important; border-color:#a78bfa !important; }
        </style>
<script>
/* Dark mode + langue — synchronisés depuis bo_repaslist via localStorage */
function updateDarkUI(d) {
    var i = document.getElementById('darkIcon');
    if (i) i.className = d ? 'fas fa-sun' : 'fas fa-moon';
}
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('gl-dark') === '1') {
        document.body.classList.add('dark');
    }
    window.addEventListener('storage', function(e) {
        if (e.key === 'gl-dark') {
            document.body.classList.toggle('dark', e.newValue === '1');
        }
    });
});
</script>
</head>
<body>
<div id="cur"></div>
<div id="curt"></div>


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

    <!-- Groupe : Module Repas -->
    <div class="sidebar-section">Module Repas</div>

    <!-- Lien vers la liste des repas-->
    <a href="#" class="nav-item">
        <i class="fas fa-utensils"></i> Repas
    </a>

    <div class="sidebar-section">Site</div>

    <a href="../../index.html" class="nav-item">
        <i class="fas fa-home"></i> Accueil
    </a>
    <a href="../FrontOffice/alimentlist.php" class="nav-item">
        <i class="fas fa-eye"></i> Vue utilisateur
    </a>

    <div class="sidebar-footer">GaiaLumen © <?= date('Y') ?></div>
</aside>


<div class="main-content">


    <div class="topbar">
        <div class="text-white text-sm font-medium flex items-center gap-2">
            <i class="fas fa-shield-alt text-[#a78bfa]"></i>
            Administration › Gestion des Aliments
        </div>
    </div>


    <section class="hero">
        <h1 class="hf" style="font-size:48px;color:white;line-height:1;margin-bottom:10px;">
            Gestion des Aliments
        </h1>
        <p style="font-size:14px;color:rgba(255,255,255,.7);margin-bottom:18px;">
            Entre nature et lumière, votre nutrition prend sens.
        </p>

        <?php
        $nb  = count($aliments);
        $nbt = count(array_unique(array_column($aliments,'type')));
        ?>
        <div class="flex gap-3 flex-wrap mb-6">
            <span class="sp"><b><?= $nb ?></b> aliments</span>
            <span class="sp"><b><?= $nbt ?></b> types</span>
        </div>


        <button onclick="openModal()"
            id="btnCreerAliment" style="background:white;color:var(--vert);padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:2px solid transparent;cursor:pointer;display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;font-family:'Lato',sans-serif;">
            <i class="fas fa-plus"></i> Créer un aliment
        </button>

        <!-- FONCTIONNALITÉS HORS CRUD -->
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

                <!-- Recherche par nom-->
                <div class="sw" style="margin-left:8px;">
                    <i class="fas fa-search"></i>
                    <input id="sq" type="text" class="si" placeholder="Rechercher un aliment…" oninput="applyFilters()">
                </div>
            </div>
        </div>
    </section>


    <div style="padding:0 28px;background:var(--bg-page);">
    <?php if ($success): ?>
        <div id="msgSucces" style="background:#e8f0e9;border:1px solid #1a372f30;color:var(--vert);padding:12px 20px;border-radius:12px;margin:16px 0;display:flex;align-items:center;gap:8px;font-size:13px;">
            <i class="fas fa-check-circle"></i>
            <?php
                if ($success==='created') echo 'Aliment ajouté avec succès.';
                if ($success==='updated') echo 'Aliment modifié avec succès.';
                if ($success==='deleted') echo 'Aliment supprimé.';
            ?>
        </div>
    <?php endif; ?>
    </div>

  
    <div style="padding:16px 28px 40px;background:var(--bg-page);">
        <!-- Breadcrumb concept 3 -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <p style="font-size:13px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-carrot" style="font-size:12px;"></i>
                Aliments
                <span style="opacity:.4;">›</span>
                <span id="breadcrumb-liste" style="color:var(--vert);font-weight:600;">Liste</span>
            </p>
            <a href="bo_repaslist.php"
               style="font-size:12px;color:#9ca3af;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;"
               onmouseover="this.style.color='var(--violet)'"
               onmouseout="this.style.color='#9ca3af'">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i> Retour aux repas
            </a>
        </div>
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

            <div id="tbody" style="min-width:1100px;">
            <?php if (empty($aliments)): ?>
                <!-- État vide : aucun aliment dans la BDD -->
                <div style="padding:48px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-seedling" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
                    Aucun aliment. Commencez par en créer un !
                </div>
            <?php else: ?>
                <?php foreach ($aliments as $a):

                    $c   = typeConfig($a['type']);
                    $svg = alimentSVG($a['nom'], $a['type'], $c, 40);
                    $co2 = co2Config((float)$a['co2']);
                ?>
                <div class="trow"
                     data-type="<?= htmlspecialchars($a['type']) ?>"
                     data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

                    <!-- Icône SVG générée dynamiquement selon le nom de l'aliment -->
                    <div style="display:flex;align-items:center;justify-content:center;"><?= $svg ?></div>


                    <div style="padding-left:10px;">
                        <p style="font-size:13px;font-weight:600;color:var(--text-main);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <div style="display:flex;gap:4px;margin-top:3px;flex-wrap:wrap;">
                            <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                            <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                        </div>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--text-main);margin:0;"><?= number_format($a['calories'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">kcal/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Fibres -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['fibres'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sucre'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sodium'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">mg/100g</p>
                    </div>

                    
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['prix'],2) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">TND/kg</p>
                    </div>

                    
                    <div style="padding:0 6px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                            <span style="font-size:10px;color:#6b7280;"><?= number_format($a['co2'],2) ?> kg</span>
                            <span style="font-size:9px;background:<?= $co2['bg'] ?>;color:<?= $co2['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co2['label'] ?></span>
                        </div>
                        <div class="cobar"><div class="cofil" style="width:<?= $co2['pct'] ?>%;background:<?= $co2['color'] ?>;"></div></div>
                    </div>

                    
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


                    <div style="display:flex;gap:5px;justify-content:center;">
                        <!-- Bouton UPDATE (modifier) -->
                        <button class="bedit" title="Modifier cet aliment" onclick="ouvrirModifAliment('<?= $a['id_aliment'] ?>','<?= addslashes(htmlspecialchars($a['nom'])) ?>','<?= $a['type'] ?>','<?= $a['categorie'] ?>','<?= $a['calories'] ?>','<?= $a['proteines'] ?>','<?= $a['glucides'] ?>','<?= $a['lipides'] ?>','<?= $a['fibres'] ?>','<?= $a['sucre'] ?>','<?= $a['sodium'] ?>','<?= $a['co2'] ?>','<?= $a['prix'] ?>','<?= addslashes(htmlspecialchars($a['vitamines'])) ?>','<?= addslashes(htmlspecialchars($a['label_ecologique'])) ?>','<?= addslashes(htmlspecialchars($a['origine'])) ?>','<?= addslashes(htmlspecialchars($a['allergenes'])) ?>')">
                            <i class="fas fa-pen" style="font-size:10px;"></i>
                        </button>
                        <!-- Bouton DELETE (supprimer) avec confirmation JavaScript -->
                        <a href="../../controller/alimentcontroller.php?action=delete&id=<?= $a['id_aliment'] ?>"
                           onclick="return confirm('Supprimer «<?= htmlspecialchars($a['nom']) ?>» ? Cette action est irréversible.')"
                           class="bdel" title="Supprimer cet aliment">
                            <i class="fas fa-trash" style="font-size:10px;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                
                <div id="noResult" style="display:none;padding:40px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-filter" style="font-size:1.8rem;display:block;margin-bottom:10px;color:#d0c8be;"></i>Aucun résultat pour ce filtre.
                </div>
            <?php endif; ?>
            </div>

            
            <div style="padding:10px 16px;background:#f9f6f2;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;" id="rowCount"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen Back Office © <?= date('Y') ?></span>
            </div>
        </div>
    </div>

</div>





<div id="modalOverlay" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div style="padding:28px 32px;">

            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Nouvel Aliment</h3>
                <button onclick="closeModal()" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>


            <form id="createForm" action="../../controller/alimentcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">


                <div style="grid-column:1/-1;">
                        <label class="lbl">Nom de l'aliment *</label>
                        <input type="text" name="nom" id="f_nom" class="mi" placeholder="ex: Carotte bio"
                               minlength="2" maxlength="200">
                        <p class="err-msg" id="e_nom">Le nom est obligatoire (min. 2 caractères).</p>
                    </div>


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

                    
                    <?php

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


                    <div style="grid-column:1/-1;">
                        <label class="lbl">Vitamines</label>
                        <input type="text" name="vitamines" class="mi"
                               placeholder="ex: A, B12, C" maxlength="200">
                    </div>


                    <div style="grid-column:1/-1;">
                        <label class="lbl">Label écologique</label>
                        <input type="text" name="label_ecologique" class="mi"
                               placeholder="bio, AOP, conventionnel…" maxlength="100">
                    </div>


                    <div>
                        <label class="lbl">Origine</label>
                        <input type="text" name="origine" class="mi"
                               placeholder="Tunisie, France…" maxlength="100">
                    </div>


                    <div>
                        <label class="lbl">Allergènes</label>
                        <input type="text" name="allergenes" class="mi"
                               placeholder="gluten, lait, arachide…" maxlength="255">
                    </div>
                </div>


                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button id="btnAnnulerAdd" type="button" onclick="closeModal()"
                        style="flex:1;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <!-- Bouton soumet après validation JS -->
                    <button id="btnSaveAdd" type="button" onclick="validateAndSubmit()"
                        style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>

/*  Curseur personnalisé */
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


function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    /* Réinitialise les marqueurs d'erreur */
    document.querySelectorAll('.mi.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.err-msg.show').forEach(el => el.classList.remove('show'));
}


function validateAndSubmit() {
    let valid = true;


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

    const nom = document.getElementById('f_nom').value.trim();
    setError('f_nom', 'e_nom', nom.length < 2);

    const type = document.getElementById('f_type').value;
    setError('f_type', 'e_type', type === '');

    const cat = document.getElementById('f_cat').value;
    setError('f_cat', 'e_cat', cat === '');

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

        const invalid = isNaN(val) || val < min || val > max;
        setError(id, errId, req ? invalid : (!isNaN(val) && invalid));
    });

    if (valid) {
        document.getElementById('createForm').submit();
    } else {
        /* Scroll vers le premier champ en erreur */
        const firstError = document.querySelector('.mi.error');
        if (firstError) firstError.scrollIntoView({ behavior:'smooth', block:'center' });
    }
}


/* FONCTIONNALITÉS HORS CRUD (filtre + recherche) */


function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}


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


    document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
    const rc = document.getElementById('rowCount');
    if (rc) rc.textContent = visible + ' aliment(s)';
}

</script>

<!-- ═══════════════════════════════════════════════════════════
     MODALE MODIFICATION ALIMENT (identique à la modale ajout)
     ═══════════════════════════════════════════════════════════ -->
<div id="updateModalOverlay" class="modal-overlay" onclick="if(event.target===this)fermerModifAliment()">
    <div class="modal-box">
        <div style="padding:28px 32px;">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Modifier l'aliment</h3>
                <button onclick="fermerModifAliment()" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>

            <form id="updateForm" action="../../controller/alimentcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_aliment" id="u_id">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <div style="grid-column:1/-1;">
                        <label class="lbl">Nom de l'aliment *</label>
                        <input type="text" name="nom" id="u_nom" class="mi" placeholder="ex: Carotte bio" minlength="2" maxlength="200">
                        <p class="err-msg" id="ue_nom">Le nom est obligatoire (min. 2 caractères).</p>
                    </div>

                    <div>
                        <label class="lbl">Type *</label>
                        <select name="type" id="u_type" class="mi">
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
                        <p class="err-msg" id="ue_type">Veuillez choisir un type.</p>
                    </div>

                    <div>
                        <label class="lbl">Catégorie *</label>
                        <select name="categorie" id="u_cat" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="frais">Frais</option>
                            <option value="sec">Sec</option>
                            <option value="transformé">Transformé</option>
                            <option value="ultra-transformé">Ultra-transformé</option>
                        </select>
                        <p class="err-msg" id="ue_cat">Veuillez choisir une catégorie.</p>
                    </div>

                    <div><label class="lbl">Calories (kcal/100g) *</label>
                        <input type="number" step="0.01" name="calories"  id="u_calories"  class="mi" min="0" max="9999" required>
                        <p class="err-msg" id="ue_calories">Valeur entre 0 et 9999.</p></div>
                    <div><label class="lbl">Protéines (g/100g) *</label>
                        <input type="number" step="0.01" name="proteines" id="u_proteines" class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_proteines">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Glucides (g/100g) *</label>
                        <input type="number" step="0.01" name="glucides"  id="u_glucides"  class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_glucides">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Lipides (g/100g) *</label>
                        <input type="number" step="0.01" name="lipides"   id="u_lipides"   class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_lipides">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Fibres (g/100g)</label>
                        <input type="number" step="0.01" name="fibres"    id="u_fibres"    class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_fibres">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Sucre (g/100g)</label>
                        <input type="number" step="0.01" name="sucre"     id="u_sucre"     class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_sucre">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Sodium (mg/100g)</label>
                        <input type="number" step="0.01" name="sodium"    id="u_sodium"    class="mi" min="0" max="5000">
                        <p class="err-msg" id="ue_sodium">Valeur entre 0 et 5000.</p></div>
                    <div><label class="lbl">CO₂ (kg CO₂eq/kg)</label>
                        <input type="number" step="0.01" name="co2"       id="u_co2"       class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_co2">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Prix (TND/kg)</label>
                        <input type="number" step="0.01" name="prix"      id="u_prix"      class="mi" min="0" max="9999">
                        <p class="err-msg" id="ue_prix">Valeur entre 0 et 9999.</p></div>

                    <div style="grid-column:1/-1;">
                        <label class="lbl">Vitamines</label>
                        <input type="text" name="vitamines" id="u_vitamines" class="mi" placeholder="ex: A, B12, C" maxlength="200">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="lbl">Label écologique</label>
                        <input type="text" name="label_ecologique" id="u_label" class="mi" placeholder="bio, AOP, conventionnel…" maxlength="100">
                    </div>
                    <div>
                        <label class="lbl">Origine</label>
                        <input type="text" name="origine" id="u_origine" class="mi" placeholder="Tunisie, France…" maxlength="100">
                    </div>
                    <div>
                        <label class="lbl">Allergènes</label>
                        <input type="text" name="allergenes" id="u_allergenes" class="mi" placeholder="gluten, lait, arachide…" maxlength="255">
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button id="btnAnnulerUpdate" type="button" onclick="fermerModifAliment()"
                        style="flex:1;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <button id="btnSaveUpdate" type="button" onclick="validateAndSubmitUpdate()"
                        style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ouvrirModifAliment(id,nom,type,cat,cal,prot,gluc,lip,fib,suc,sod,co2,prix,vit,label,orig,allerg) {
    document.getElementById('u_id').value        = id;
    document.getElementById('u_nom').value       = nom;
    document.getElementById('u_vitamines').value = vit;
    document.getElementById('u_label').value     = label;
    document.getElementById('u_origine').value   = orig;
    document.getElementById('u_allergenes').value= allerg;
    document.getElementById('u_calories').value  = cal;
    document.getElementById('u_proteines').value = prot;
    document.getElementById('u_glucides').value  = gluc;
    document.getElementById('u_lipides').value   = lip;
    document.getElementById('u_fibres').value    = fib;
    document.getElementById('u_sucre').value     = suc;
    document.getElementById('u_sodium').value    = sod;
    document.getElementById('u_co2').value       = co2;
    document.getElementById('u_prix').value      = prix;
    /* Type et catégorie : sélectionner la bonne option */
    var selType = document.getElementById('u_type');
    for (var i=0;i<selType.options.length;i++) {
        if (selType.options[i].value === type) { selType.selectedIndex = i; break; }
    }
    var selCat = document.getElementById('u_cat');
    for (var i=0;i<selCat.options.length;i++) {
        if (selCat.options[i].value === cat) { selCat.selectedIndex = i; break; }
    }
    /* Ouvrir la modale */
    document.getElementById('updateModalOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fermerModifAliment() {
    document.getElementById('updateModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function validateAndSubmitUpdate() {
    var valid = true;
    function chk(id, errId, test) {
        var el = document.getElementById(id);
        var er = document.getElementById(errId);
        if (!test(el)) { el.classList.add('error'); if(er) er.classList.add('show'); valid = false; }
        else { el.classList.remove('error'); if(er) er.classList.remove('show'); }
    }
    chk('u_nom',      'ue_nom',      function(e){ return e.value.trim().length >= 2; });
    chk('u_type',     'ue_type',     function(e){ return e.value !== ''; });
    chk('u_cat',      'ue_cat',      function(e){ return e.value !== ''; });
    chk('u_calories', 'ue_calories', function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=9999; });
    chk('u_proteines','ue_proteines',function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    chk('u_glucides', 'ue_glucides', function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    chk('u_lipides',  'ue_lipides',  function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    if (valid) document.getElementById('updateForm').submit();
    else document.querySelector('#updateModalOverlay .error')?.scrollIntoView({behavior:'smooth',block:'center'});
}
</script>

</body>
</html>


