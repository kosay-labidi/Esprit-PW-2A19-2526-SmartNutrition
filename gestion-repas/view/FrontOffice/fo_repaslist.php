<?php
/* ============================================================
   view/FrontOffice/repaslist.php — FRONT OFFICE Repas
   RÔLE : Page principale de l'utilisateur pour gérer ses repas.

   CRUD REPAS côté Front Office :
     C → Formulaire modal "Créer un repas" (choisit des aliments)
     R → Liste des repas + détail des aliments composant chaque repas
     U → Lien vers updaterepas.php
     D → Lien de suppression vers le Controller

   CONSULTATION ALIMENTS :
     L'utilisateur peut voir les aliments disponibles pour les
     sélectionner dans le formulaire de création du repas.

   ARCHITECTURE MVC :
     Model      : model/repas.php + model/aliment.php
     View       : ce fichier
     Controller : controller/repascontroller.php
   ============================================================ */

/* ----------------------------------------------------------
   SECTION 1 — DÉPENDANCES + DONNÉES
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/repas_model.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

$repasModel   = new Repas();
$alimentModel = new Aliment();

/* Récupération des repas de l'utilisateur (id=1 en attendant l'auth) */
$repas    = $repasModel->getAllByUser(1);
/* Récupération de tous les aliments disponibles (pour le formulaire) */
$aliments = $alimentModel->getAll();

$success = $_GET['success'] ?? '';
$error   = urldecode($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Mes Repas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ── Polices GaiaLumen ──────────────────────────────── */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        body { background:var(--sable); color:var(--vert); }

        /* ── Curseur personnalisé ─────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ── Navbar horizontale ─────────────────────────────── */
        .navbar { background:linear-gradient(90deg,var(--vert) 0%,#11241f 100%);position:sticky;top:0;z-index:50; }

        /* ── Hero avec photo ────────────────────────────────── */
        .hero {
            background-image: linear-gradient(rgba(26,55,47,.72),rgba(26,55,47,.72)),
                              url('../BackOffice/assets/images/1000051721.jpg');
            background-size:cover; background-position:center; padding:48px 0 36px;
        }

        /* ── Carte repas ───────────────────────────────────── */
        .repas-card { background:white;border-radius:20px;border:1px solid #ede8e0;overflow:hidden;transition:box-shadow .2s; }
        .repas-card:hover { box-shadow:0 8px 28px rgba(26,55,47,.12); }

        /* ── Badges ─────────────────────────────────────────── */
        .badge { display:inline-block;font-size:10px;padding:2px 9px;border-radius:99px;font-weight:600; }

        /* ── Barre macro ────────────────────────────────────── */
        .mbar { height:5px;border-radius:3px;background:#ede8e0;overflow:hidden;margin:3px 0 6px; }
        .mfil { height:100%;border-radius:3px; }

        /* ── Modal ──────────────────────────────────────────── */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;align-items:center;justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white;border-radius:24px;width:100%;max-width:780px;margin:0 16px;max-height:92vh;overflow-y:auto; }

        /* ── Champs formulaire ──────────────────────────────── */
        .fi { width:100%;padding:9px 14px;border-radius:12px;border:1.5px solid #e8e0d8;font-size:13px;outline:none;font-family:'Lato',sans-serif;color:var(--vert); }
        .fi:focus { border-color:var(--violet); }
        .fi.error { border-color:#c09090;background:#fdf5f5; }
        .lbl { display:block;font-size:11px;font-weight:700;color:var(--vert);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em; }
        .err-msg { font-size:11px;color:#8a2020;margin-top:3px;display:none; }
        .err-msg.show { display:block; }

        /* ── Grille aliments dans le formulaire ─────────────── */
        .alim-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;max-height:300px;overflow-y:auto;padding:4px; }
        .alim-item { border:1.5px solid #ede8e0;border-radius:12px;padding:10px;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:8px; }
        .alim-item:hover { border-color:var(--violet);background:#f9f5ff; }
        .alim-item.selected { border-color:var(--vert);background:#e8f0e9; }
        .alim-item input[type=checkbox] { accent-color:var(--vert); }

        /* ── Boutons action ─────────────────────────────────── */
        .bedit { display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid var(--bleu);color:#1a5fa8;background:transparent;cursor:pointer;text-decoration:none;transition:all .14s; }
        .bedit:hover { background:#e8f2fc; }
        .bdel  { display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid #c09090;color:#8a2020;background:transparent;cursor:pointer;text-decoration:none;transition:all .14s; }
        .bdel:hover  { background:#faeaea; }

        /* ── Pill stats ─────────────────────────────────────── */
        .sp { background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* ── Section aliments à consulter ───────────────────── */
        .consult-card { background:white;border-radius:16px;border:1px solid #ede8e0;padding:14px;transition:transform .15s; }
        .consult-card:hover { transform:translateY(-2px); }
    </style>
</head>
<body>
<div id="cur"></div><div id="curt"></div>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR HORIZONTALE
     ══════════════════════════════════════════════════════════ -->
<nav class="navbar text-white shadow-xl">
    <div class="max-w-screen-xl mx-auto px-8 py-4 flex items-center justify-between">
        <a href="../../index.html" class="flex items-center gap-3">
            <svg width="32" height="32" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
                <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs>
                <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
            </svg>
            <span class="hf text-3xl tracking-tighter">GaiaLumen</span>
        </a>
        <ul class="flex items-center gap-7 text-sm font-medium">
            <li><a href="../../index.html"     class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
            <li><a href="fo_alimentlist.php"      class="hover:text-[#a78bfa] transition-colors">Aliments</a></li>
            <li><a href="repaslist.php"        class="text-[#a78bfa] font-semibold">Mes Repas</a></li>
            <li><a href="#"                    class="hover:text-[#a78bfa] transition-colors">Défis</a></li>
        </ul>
        <a href="#" class="flex items-center gap-2 text-sm bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full">
            <i class="fas fa-user-circle"></i> Mon compte
        </a>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     HERO avec photo
     ══════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="max-w-screen-xl mx-auto px-8">
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:4px 14px;border-radius:99px;font-size:11px;color:rgba(255,255,255,.85);margin-bottom:14px;">
            <i class="fas fa-utensils" style="color:var(--violet);"></i> MON JOURNAL ALIMENTAIRE
        </span>
        <h1 class="hf" style="font-size:52px;color:white;line-height:1;margin-bottom:10px;">Mes Repas</h1>
        <p style="font-size:14px;color:rgba(255,255,255,.75);margin-bottom:20px;">
            Composez vos repas en sélectionnant vos aliments, suivez vos apports nutritionnels.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;">
            <span class="sp"><b><?= count($repas) ?></b> repas enregistrés</span>
            <span class="sp"><b><?= count($aliments) ?></b> aliments disponibles</span>
        </div>

        <!-- ══════════════════════════════════════════════════
             CRUD : BOUTON CREATE (unique — dans le hero)
             Ouvre le formulaire de création d'un repas
             ══════════════════════════════════════════════════ -->
        <button onclick="openModal('createModal')"
            style="background:white;color:var(--vert);padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;font-family:'Lato',sans-serif;">
            <i class="fas fa-plus"></i> Créer un repas
        </button>
    </div>
</section>

<!-- Contenu principal -->
<div class="max-w-screen-xl mx-auto px-8 py-8">

    <!-- Messages succès / erreur -->
    <?php if ($success): ?>
    <div style="background:#e8f0e9;border:1px solid #1a372f30;color:var(--vert);padding:12px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:8px;font-size:13px;">
        <i class="fas fa-check-circle"></i>
        <?php
        if ($success==='created') echo 'Repas créé avec succès !';
        if ($success==='updated') echo 'Repas modifié avec succès !';
        if ($success==='deleted') echo 'Repas supprimé.';
        ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:#faeaea;border:1px solid #c09090;color:#8a2020;padding:12px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:8px;font-size:13px;">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

        <!-- ══════════════════════════════════════════════════
             CRUD : READ — Liste des repas de l'utilisateur
             ══════════════════════════════════════════════════ -->
        <div>
            <h2 class="hf" style="font-size:28px;color:var(--vert);margin-bottom:16px;">Mes repas</h2>

            <?php if (empty($repas)): ?>
            <div style="background:white;border-radius:20px;padding:48px;text-align:center;border:1px solid #ede8e0;color:#9ca3af;">
                <i class="fas fa-utensils" style="font-size:2.5rem;display:block;margin-bottom:14px;color:#d0c8be;"></i>
                <p style="font-size:15px;">Vous n'avez pas encore de repas.</p>
                <button onclick="openModal('createModal')"
                    style="margin-top:16px;background:var(--vert);color:white;padding:10px 24px;border-radius:99px;border:none;cursor:pointer;font-size:13px;font-weight:600;">
                    Créer mon premier repas
                </button>
            </div>
            <?php else: ?>

            <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach ($repas as $r):
                /* Récupère les aliments du repas via la jointure */
                $alimRepas = $repasModel->getAlimentsOfRepas((int)$r['id_repas']);
                /* Calcule les totaux nutritionnels via la jointure */
                $totaux    = $repasModel->getTotauxNutritionnels((int)$r['id_repas']);
            ?>
            <div class="repas-card">
                <!-- En-tête de la carte repas -->
                <div style="padding:18px 20px 14px;display:flex;align-items:flex-start;justify-content:space-between;">
                    <div>
                        <h3 class="hf" style="font-size:22px;color:var(--vert);margin:0;"><?= htmlspecialchars($r['nom_repas']) ?></h3>
                        <p style="font-size:12px;color:#9ca3af;margin:3px 0 0;">
                            <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>
                            <?= date('d/m/Y à H:i', strtotime($r['date_repas'])) ?>
                            · <?= $r['nb_aliments'] ?> aliment(s)
                        </p>
                    </div>
                    <!-- CRUD : Actions Update + Delete -->
                    <div style="display:flex;gap:6px;">
                        <a href="fo_updaterepas.php?id=<?= $r['id_repas'] ?>" class="bedit">
                            <i class="fas fa-pen" style="font-size:10px;"></i> Modifier
                        </a>
                        <a href="../../controller/repascontroller.php?action=delete&id=<?= $r['id_repas'] ?>"
                           onclick="return confirm('Supprimer ce repas ?')" class="bdel">
                            <i class="fas fa-trash" style="font-size:10px;"></i> Supprimer
                        </a>
                    </div>
                </div>

                <!-- Totaux nutritionnels du repas (via jointure) -->
                <?php if (!empty($totaux) && $totaux['total_calories'] > 0): ?>
                <div style="padding:0 20px 14px;">
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px;">
                        <?php
                        $stats = [
                            ['Calories',  round($totaux['total_calories'],1),  'kcal', '#1a372f'],
                            ['Protéines', round($totaux['total_proteines'],1), 'g',    '#60a5fa'],
                            ['Glucides',  round($totaux['total_glucides'],1),  'g',    '#c9a44a'],
                            ['Lipides',   round($totaux['total_lipides'],1),   'g',    '#a78bfa'],
                        ];
                        foreach ($stats as [$l,$v,$u,$col]):
                        ?>
                        <div style="background:var(--sable);border-radius:10px;padding:8px 10px;text-align:center;">
                            <p style="font-size:15px;font-weight:700;color:<?= $col ?>;font-family:'Cormorant Garamond',serif;margin:0;"><?= $v ?></p>
                            <p style="font-size:9px;color:#9ca3af;margin:0;"><?= $l ?> (<?= $u ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Liste des aliments du repas (résultat de la jointure) -->
                    <?php if (!empty($alimRepas)): ?>
                    <div>
                        <p style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Composition</p>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        <?php foreach ($alimRepas as $al):
                            $c = typeConfig($al['type']);
                        ?>
                            <span style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:500;">
                                <?= htmlspecialchars($al['nom']) ?>
                                <span style="opacity:.7;">(<?= $al['quantite'] ?>g)</span>
                            </span>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Impact CO₂ total -->
                    <?php if ($totaux['total_co2'] > 0): ?>
                    <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-leaf" style="color:var(--vert);font-size:12px;"></i>
                        <span style="font-size:12px;color:#6b7280;">Impact CO₂ estimé : <b style="color:var(--vert);"><?= round($totaux['total_co2'],2) ?> kg CO₂eq</b></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="padding:0 20px 16px;color:#9ca3af;font-size:13px;font-style:italic;">Aucun aliment dans ce repas.</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════════════════════
             CONSULTATION ALIMENTS (hors CRUD)
             L'utilisateur consulte les aliments disponibles
             pour s'aider à composer ses repas.
             ══════════════════════════════════════════════════ -->
        <div style="position:sticky;top:80px;">
            <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:12px;">Aliments disponibles</h2>
            <p style="font-size:12px;color:#9ca3af;margin-bottom:14px;">
                Consultez les aliments pour vous aider à composer votre repas.
            </p>

            <!-- Recherche rapide d'aliment -->
            <div style="position:relative;margin-bottom:12px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                <input id="searchAlim" type="text" placeholder="Rechercher…"
                       oninput="filterConsult()"
                       style="width:100%;padding:9px 14px 9px 36px;border-radius:99px;border:1.5px solid #d0c8be;font-size:13px;outline:none;">
            </div>

            <div id="consultList" style="display:flex;flex-direction:column;gap:8px;max-height:480px;overflow-y:auto;padding-right:4px;">
            <?php foreach ($aliments as $al):
                $c  = typeConfig($al['type']);
                $sv = alimentSVG($al['nom'], $al['type'], $c, 36);
            ?>
            <div class="consult-card" data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>">
                <div style="display:flex;align-items:center;gap:10px;">
                    <?= $sv ?>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($al['nom']) ?></p>
                        <div style="display:flex;gap:4px;margin-top:2px;">
                            <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9px;"><?= htmlspecialchars($al['type']) ?></span>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= round($al['calories'],0) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">kcal/100g</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Lien vers la liste complète -->
            <a href="fo_alimentlist.php" style="display:block;text-align:center;margin-top:12px;font-size:12px;color:var(--violet);text-decoration:none;font-weight:600;">
                Voir tous les aliments →
            </a>
        </div>

    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     CRUD : CREATE — Modal de création d'un repas
     Formulaire envoyé en POST vers repascontroller.php
     L'utilisateur saisit le nom, la date et choisit
     les aliments qui composent le repas.
     ══════════════════════════════════════════════════════════ -->
<div id="createModal" class="modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal-box">
        <div style="padding:28px 32px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Créer un repas</h3>
                <button onclick="closeModal('createModal')" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;">&times;</button>
            </div>

            <form id="createRepasForm" action="../../controller/repascontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
                    <!-- Nom du repas -->
                    <div>
                        <label class="lbl">Nom du repas *</label>
                        <input type="text" name="nom_repas" id="f_nom_repas" class="fi"
                               placeholder="ex: Déjeuner équilibré" maxlength="150">
                        <p class="err-msg" id="e_nom_repas">Le nom est obligatoire (min 2 caractères).</p>
                    </div>
                    <!-- Date du repas -->
                    <div>
                        <label class="lbl">Date & heure du repas *</label>
                        <input type="datetime-local" name="date_repas" id="f_date_repas" class="fi">
                        <p class="err-msg" id="e_date_repas">La date est obligatoire.</p>
                    </div>
                </div>

                <!-- Sélection des aliments (jointure) -->
                <div style="margin-bottom:18px;">
                    <label class="lbl">Choisir les aliments * <span style="font-weight:400;text-transform:none;font-size:11px;color:#9ca3af;">(cliquez pour sélectionner)</span></label>
                    <p class="err-msg" id="e_aliments" style="display:none;margin-bottom:6px;">Sélectionnez au moins un aliment.</p>

                    <!-- Recherche dans les aliments -->
                    <div style="position:relative;margin-bottom:8px;">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:11px;"></i>
                        <input type="text" placeholder="Filtrer les aliments…" oninput="filterAlimModal(this.value)"
                               style="width:100%;padding:7px 12px 7px 34px;border-radius:10px;border:1.5px solid #e8e0d8;font-size:12px;outline:none;">
                    </div>

                    <!-- Grille des aliments disponibles -->
                    <div class="alim-grid" id="alimGrid">
                    <?php foreach ($aliments as $al):
                        $c  = typeConfig($al['type']);
                        $sv = alimentSVG($al['nom'], $al['type'], $c, 28);
                    ?>
                    <div class="alim-item" id="item_<?= $al['id_aliment'] ?>"
                         onclick="toggleAliment(<?= $al['id_aliment'] ?>, '<?= addslashes($al['nom']) ?>')"
                         data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>">
                        <input type="checkbox" name="aliments[]"
                               id="chk_<?= $al['id_aliment'] ?>"
                               value="<?= $al['id_aliment'] ?>"
                               onclick="event.stopPropagation();"
                               onchange="syncToggle(<?= $al['id_aliment'] ?>)">
                        <?= $sv ?>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:11px;font-weight:600;color:var(--vert);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($al['nom']) ?></p>
                            <p style="font-size:10px;color:#9ca3af;margin:0;"><?= round($al['calories'],0) ?> kcal</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Sélection actuelle + quantités -->
                    <div id="selectionZone" style="margin-top:12px;display:none;">
                        <p style="font-size:11px;font-weight:700;color:var(--vert);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Quantités (en grammes)</p>
                        <div id="quantitesList" style="display:flex;flex-direction:column;gap:8px;"></div>
                    </div>
                </div>

                <!-- Boutons -->
                <div style="display:flex;gap:10px;margin-top:10px;">
                    <button type="button" onclick="closeModal('createModal')"
                        style="flex:1;padding:12px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <button type="button" onclick="validateRepasForm('createRepasForm')"
                        style="flex:1;padding:12px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-check" style="margin-right:6px;"></i> Enregistrer le repas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════ -->
<script>
/* ── Curseur ──────────────────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button,input').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
})();

/* ── Modal ────────────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/* ── Toggle sélection aliment ─────────────────────────────── */
const selected = {}; /* {id: nom} */

function toggleAliment(id, nom) {
    const item = document.getElementById('item_'+id);
    const chk  = document.getElementById('chk_'+id);
    if (selected[id]) {
        delete selected[id];
        item.classList.remove('selected');
        chk.checked = false;
    } else {
        selected[id] = nom;
        item.classList.add('selected');
        chk.checked = true;
    }
    updateQuantitesList();
}

function syncToggle(id) {
    const chk  = document.getElementById('chk_'+id);
    const item = document.getElementById('item_'+id);
    const nom  = item.querySelector('p').textContent;
    if (chk.checked) {
        selected[id] = nom;
        item.classList.add('selected');
    } else {
        delete selected[id];
        item.classList.remove('selected');
    }
    updateQuantitesList();
}

/* Affiche les champs quantité pour chaque aliment sélectionné */
function updateQuantitesList() {
    const zone = document.getElementById('selectionZone');
    const list = document.getElementById('quantitesList');
    const keys = Object.keys(selected);
    if (keys.length === 0) { zone.style.display='none'; return; }
    zone.style.display = 'block';
    list.innerHTML = keys.map(id => `
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:12px;color:var(--vert);font-weight:600;flex:1;">${selected[id]}</span>
            <input type="number" name="quantites[${id}]" value="100" min="1" max="2000"
                   style="width:80px;padding:5px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;"
                   placeholder="g">
            <span style="font-size:11px;color:#9ca3af;">g</span>
        </div>
    `).join('');
}

/* Filtre les aliments dans la grille du formulaire */
function filterAlimModal(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#alimGrid .alim-item').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* Filtre les aliments dans la colonne de consultation */
function filterConsult() {
    const q = document.getElementById('searchAlim').value.toLowerCase();
    document.querySelectorAll('#consultList .consult-card').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* ── Validation formulaire création repas ─────────────────── */
function validateRepasForm(formId) {
    let valid = true;
    function setErr(id, errId, show) {
        const el=document.getElementById(id), er=document.getElementById(errId);
        if(!el||!er) return;
        if(show){ el.classList.add('error'); er.classList.add('show'); valid=false; }
        else     { el.classList.remove('error'); er.classList.remove('show'); }
    }
    /* Nom */
    setErr('f_nom_repas','e_nom_repas', document.getElementById('f_nom_repas').value.trim().length < 2);
    /* Date */
    setErr('f_date_repas','e_date_repas', !document.getElementById('f_date_repas').value);
    /* Au moins un aliment */
    const hasAlim = Object.keys(selected).length > 0;
    const eAlim   = document.getElementById('e_aliments');
    if (!hasAlim) { eAlim.style.display='block'; valid=false; }
    else            { eAlim.style.display='none'; }

    if (valid) document.getElementById(formId).submit();
    else {
        const first = document.querySelector('.fi.error');
        if (first) first.scrollIntoView({behavior:'smooth',block:'center'});
    }
}

/* Pré-remplir la date avec la date+heure actuelle */
document.addEventListener('DOMContentLoaded', () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const f = document.getElementById('f_date_repas');
    if (f) f.value = now.toISOString().slice(0,16);
});
</script>
</body>
</html>
