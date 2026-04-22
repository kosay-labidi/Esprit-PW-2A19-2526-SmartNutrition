<?php
/* ================================================================
   view/FrontOffice/fo_repaslist.php — FRONT OFFICE Repas
   ARCHITECTURE MVC :
     Model      : model/repas_model.php + model/aliment.php
     View       : ce fichier
     Controller : controller/repascontroller.php
     Helpers    : helpers/aliment_helpers.php
                  helpers/repas_helpers.php  ← FONCTIONNALITÉS INNOVANTES

   ┌──────────────────────────────────────────────────────────────┐
   │  SECTION A — CRUD (opérations sur la BDD)                   │
   │    Create  : modal formulaire + envoi POST au Controller    │
   │    Read    : liste des repas avec composition               │
   │    Update  : lien vers fo_updaterepas.php                   │
   │    Delete  : lien GET vers repascontroller.php              │
   ├──────────────────────────────────────────────────────────────┤
   │  SECTION B — FONCTIONNALITÉS INNOVANTES (hors CRUD)         │
   │    1. Score écologique en temps réel (dans le formulaire     │
   │       ET sur la carte du repas après validation)            │
   │    2. Recommandations & détection d'erreurs (formulaire)    │
   │    3. Description automatique du repas (carte après valid.) │
   └──────────────────────────────────────────────────────────────┘
   ================================================================ */


/* ════════════════════════════════════════════════════════════════
   SECTION 1 — DÉPENDANCES
   ════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/repas_model.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';
/* Helpers des fonctionnalités innovantes */
require_once __DIR__ . '/../../helpers/repas_helpers.php';

$repasModel   = new Repas();
$alimentModel = new Aliment();


/* ════════════════════════════════════════════════════════════════
   SECTION 2 — DONNÉES (appels Model → BDD)
   ════════════════════════════════════════════════════════════════ */
$mesRepas  = $repasModel->getAllByUser(1);
$aliments  = $alimentModel->getAll();
$success   = $_GET['success'] ?? '';
$error     = urldecode($_GET['error'] ?? '');

/* Préparer les données JSON des aliments pour le JS (Fonctionnalité 1&2) */
$alimentsJson = json_encode(array_column($aliments, null, 'id_aliment'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Mes Repas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        body { background:var(--sable); color:var(--vert); }

        /* ── Curseur ─────────────────────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ── Navbar ───────────────────────────────────────────── */
        .navbar { background:linear-gradient(90deg,var(--vert) 0%,#11241f 100%); position:sticky; top:0; z-index:50; }

        /* ── Hero ─────────────────────────────────────────────── */
        .hero { background-image:linear-gradient(rgba(26,55,47,.72),rgba(26,55,47,.72)),url('../BackOffice/assets/images/1000051721.jpg'); background-size:cover; background-position:center; padding:48px 0 36px; }

        /* ── Cartes repas ─────────────────────────────────────── */
        .repas-card { background:white; border-radius:20px; border:1px solid #ede8e0; overflow:hidden; transition:box-shadow .2s; }
        .repas-card:hover { box-shadow:0 8px 28px rgba(26,55,47,.12); }
        .badge { display:inline-block; font-size:10px; padding:2px 9px; border-radius:99px; font-weight:600; }

        /* ── Modal ────────────────────────────────────────────── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:200; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white; border-radius:24px; width:100%; max-width:860px; margin:0 16px; max-height:94vh; overflow-y:auto; }

        /* ── Champs formulaire ────────────────────────────────── */
        .fi { width:100%; padding:9px 14px; border-radius:12px; border:1.5px solid #e8e0d8; font-size:13px; outline:none; }
        .fi:focus { border-color:var(--violet); }
        .fi.error { border-color:#c09090; background:#fdf5f5; }
        .lbl { display:block; font-size:11px; font-weight:700; color:var(--vert); margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
        .err-msg { font-size:11px; color:#8a2020; margin-top:3px; display:none; }
        .err-msg.show { display:block; }

        /* ── Grille aliments dans le formulaire ─────────────── */
        .alim-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:8px; max-height:240px; overflow-y:auto; padding:4px; }
        .alim-item { border:1.5px solid #ede8e0; border-radius:12px; padding:9px; cursor:pointer; transition:all .18s; display:flex; align-items:center; gap:7px; }
        .alim-item:hover { border-color:var(--violet); background:#f9f5ff; }
        .alim-item.selected { border-color:var(--vert); background:#e8f0e9; }

        /* ── Boutons action ───────────────────────────────────── */
        .bedit { display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid var(--bleu);color:#1a5fa8;background:transparent;cursor:pointer;text-decoration:none;transition:all .14s; }
        .bedit:hover { background:#e8f2fc; }
        .bdel  { display:inline-flex;align-items:center;gap:4px;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid #c09090;color:#8a2020;background:transparent;cursor:pointer;text-decoration:none;transition:all .14s; }
        .bdel:hover  { background:#faeaea; }

        /* ── Pill stats hero ──────────────────────────────────── */
        .sp { background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.2); border-radius:99px; padding:5px 14px; font-size:12px; color:white; display:inline-flex; align-items:center; gap:4px; }
        .sp b { font-weight:700; }

        /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           STYLES DES FONCTIONNALITÉS INNOVANTES
           ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */

        /* ── FONCTIONNALITÉ 1 : Score écologique (indicateur) ── */
        .eco-panel {
            border-radius:16px; padding:16px 18px; transition:all .4s ease;
        }
        .eco-score-circle {
            width:70px; height:70px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            flex-direction:column; flex-shrink:0;
            font-family:'Cormorant Garamond',serif;
            border:3px solid currentColor;
            transition:all .4s ease;
        }
        .eco-bar-track { height:10px; border-radius:5px; background:rgba(0,0,0,.1); overflow:hidden; margin-top:8px; }
        .eco-bar-fill  { height:100%; border-radius:5px; transition:width .6s ease; }

        /* ── FONCTIONNALITÉ 2 : Alertes & recommandations ─────── */
        .alerte-item {
            display:flex; align-items:flex-start; gap:10px;
            padding:10px 14px; border-radius:12px; margin-bottom:8px;
            font-size:12px; line-height:1.5;
        }
        .alerte-erreur   { background:#faeaea; color:#8a2020; border:1px solid #e0b0b0; }
        .alerte-warning  { background:#fff9e6; color:#8a6510; border:1px solid #e0d080; }
        .alerte-gaspillage { background:#fff3e0; color:#c07020; border:1px solid #f0c070; }
        .reco-item {
            display:flex; align-items:flex-start; gap:10px;
            padding:10px 14px; border-radius:12px; margin-bottom:7px;
            font-size:12px; line-height:1.5;
            background:#f0f7ff; color:#1a4a7a; border:1px solid #b0d0f0;
        }

        /* ── FONCTIONNALITÉ 3 : Description du repas (carte) ──── */
        .desc-panel {
            background:var(--sable); border-radius:14px; padding:14px 16px;
            border:1px solid #e8e0d8; margin-top:12px;
        }
        .desc-badge {
            display:inline-flex; align-items:center; gap:5px;
            font-size:10px; padding:3px 10px; border-radius:99px;
            font-weight:600; margin:3px 3px 0 0;
        }

        /* ── Consult. aliments (colonne droite) ──────────────── */
        .consult-card { background:white; border-radius:14px; border:1px solid #ede8e0; padding:12px; transition:transform .15s; }
        .consult-card:hover { transform:translateY(-2px); }

        /* ── Macro mini-bars sur cartes ───────────────────────── */
        .mbar { height:4px; border-radius:2px; background:#ede8e0; overflow:hidden; margin:2px 0 5px; }
        .mfil { height:100%; border-radius:2px; }

        /* ── Accordéon détail repas ───────────────────────────── */
        .repas-detail { display:none; }
        .repas-detail.open { display:block; }
    </style>
</head>
<body>
<div id="cur"></div><div id="curt"></div>

<!-- ════════════════════════════════════════════════════════════
     NAVBAR HORIZONTALE
     ════════════════════════════════════════════════════════════ -->
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
            <li><a href="../../index.html"   class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
            <li><a href="fo_alimentlist.php" class="hover:text-[#a78bfa] transition-colors">Aliments</a></li>
            <li><a href="fo_repaslist.php"   class="text-[#a78bfa] font-semibold">Mes Repas</a></li>
            <li><a href="#"                  class="hover:text-[#a78bfa] transition-colors">Défis</a></li>
        </ul>
        <a href="#" class="flex items-center gap-2 text-sm bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full">
            <i class="fas fa-user-circle"></i> Mon compte
        </a>
    </div>
</nav>

<!-- ════════════════════════════════════════════════════════════
     HERO avec photo
     ════════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="max-w-screen-xl mx-auto px-8">
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:4px 14px;border-radius:99px;font-size:11px;color:rgba(255,255,255,.85);margin-bottom:14px;">
            <i class="fas fa-utensils" style="color:var(--violet);"></i> MON JOURNAL ALIMENTAIRE
        </span>
        <h1 class="hf" style="font-size:52px;color:white;line-height:1;margin-bottom:10px;">Mes Repas</h1>
        <p style="font-size:14px;color:rgba(255,255,255,.75);margin-bottom:20px;">
            Composez vos repas, suivez vos apports nutritionnels et votre impact écologique.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <span class="sp"><b><?= count($mesRepas) ?></b> repas enregistrés</span>
            <span class="sp"><b><?= count($aliments) ?></b> aliments disponibles</span>
        </div>

        <!-- ════════════════════════════════════════════════════
             SECTION A — CRUD : BOUTON CREATE
             ════════════════════════════════════════════════════ -->
        <button onclick="openModal('createModal')"
            style="background:white;color:var(--vert);padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-plus"></i> Créer un repas
        </button>
    </div>
</section>

<div class="max-w-screen-xl mx-auto px-8 py-8">

    <!-- Messages -->
    <?php if ($success): ?>
    <div style="background:#e8f0e9;border:1px solid #1a372f30;color:var(--vert);padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-check-circle"></i>
        <?php if($success==='created') echo 'Repas créé avec succès !';
              if($success==='updated') echo 'Repas modifié avec succès !';
              if($success==='deleted') echo 'Repas supprimé.'; ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:#faeaea;border:1px solid #c09090;color:#8a2020;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

        <!-- ════════════════════════════════════════════════════
             SECTION A — CRUD : READ — Liste des repas
             ════════════════════════════════════════════════════ -->
        <div>
            <h2 class="hf" style="font-size:28px;color:var(--vert);margin-bottom:16px;">Mes repas</h2>

            <?php if (empty($mesRepas)): ?>
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
            <?php foreach ($mesRepas as $r):
                /* Données du repas via jointure */
                $alimRepas = $repasModel->getAlimentsOfRepas((int)$r['id_repas']);
                $totaux    = $repasModel->getTotauxNutritionnels((int)$r['id_repas']);

                /* ─────────────────────────────────────────────────────
                   FONCTIONNALITÉS INNOVANTES — calculées en PHP
                   (côté serveur, après validation du repas)
                   ───────────────────────────────────────────────────── */
                $moment       = detecterMomentJournee($r['date_repas']);
                $saison       = detecterSaison($r['date_repas']);
                $score        = scoreEcologique($totaux);
                $lbl          = labelEcologique($score);
                /* FONCTIONNALITÉ 3 : description automatique */
                $descData     = descriptionRepas($totaux, $alimRepas, $score, $moment);
            ?>
            <div class="repas-card">

                <!-- En-tête : nom + date + actions CRUD -->
                <div style="padding:16px 20px 12px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="flex:1;min-width:0;">
                        <!-- Bouton pour déplier/replier la description -->
                        <button onclick="toggleDetail(<?= $r['id_repas'] ?>)"
                            style="background:none;border:none;cursor:pointer;text-align:left;padding:0;width:100%;">
                            <h3 class="hf" style="font-size:22px;color:var(--vert);margin:0;display:flex;align-items:center;gap:8px;">
                                <?= htmlspecialchars($r['nom_repas']) ?>
                                <i class="fas fa-chevron-down" id="chevron_<?= $r['id_repas'] ?>"
                                   style="font-size:12px;color:#9ca3af;transition:transform .2s;"></i>
                            </h3>
                        </button>
                        <p style="font-size:12px;color:#9ca3af;margin:3px 0 0;">
                            <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>
                            <?= date('d/m/Y à H:i', strtotime($r['date_repas'])) ?>
                            · <?= $r['nb_aliments'] ?> aliment(s)
                        </p>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                        <!-- Score écologique : cercle affiché sur la carte -->
                        <div style="text-align:center;">
                            <div style="width:44px;height:44px;border-radius:50%;background:<?= $lbl['bg'] ?>;color:<?= $lbl['color'] ?>;border:2px solid <?= $lbl['color'] ?>;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                                <span style="font-size:14px;font-weight:700;font-family:'Cormorant Garamond',serif;"><?= $score ?></span>
                            </div>
                            <p style="font-size:9px;color:#9ca3af;margin:2px 0 0;white-space:nowrap;"><?= $lbl['emoji'] ?> éco</p>
                        </div>

                        <!-- CRUD : Update + Delete -->
                        <div style="display:flex;gap:6px;">
                            <a href="fo_updaterepas.php?id=<?= $r['id_repas'] ?>" class="bedit">
                                <i class="fas fa-pen" style="font-size:10px;"></i> Modifier
                            </a>
                            <a href="../../controller/repascontroller.php?action=delete&id=<?= $r['id_repas'] ?>"
                               onclick="return confirm('Supprimer ce repas ?')" class="bdel">
                                <i class="fas fa-trash" style="font-size:10px;"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Totaux nutritionnels + aliments -->
                <?php if (!empty($totaux) && $totaux['total_calories'] > 0): ?>
                <div style="padding:0 20px 14px;">
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px;">
                        <?php foreach ([
                            ['Calories',  round($totaux['total_calories'],0),  'kcal', '#1a372f'],
                            ['Protéines', round($totaux['total_proteines'],1), 'g',    '#60a5fa'],
                            ['Glucides',  round($totaux['total_glucides'],1),  'g',    '#c9a44a'],
                            ['Lipides',   round($totaux['total_lipides'],1),   'g',    '#a78bfa'],
                        ] as [$l,$v,$u,$col]): ?>
                        <div style="background:var(--sable);border-radius:10px;padding:8px;text-align:center;">
                            <p style="font-size:14px;font-weight:700;color:<?= $col ?>;font-family:'Cormorant Garamond',serif;margin:0;"><?= $v ?></p>
                            <p style="font-size:9px;color:#9ca3af;margin:0;"><?= $l ?> (<?= $u ?>)</p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Composition : aliments via jointure -->
                    <?php if (!empty($alimRepas)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
                    <?php foreach ($alimRepas as $al):
                        $c = typeConfig($al['type']); ?>
                        <span style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;padding:3px 10px;border-radius:99px;font-size:11px;">
                            <?= htmlspecialchars($al['nom']) ?> <span style="opacity:.6;">(<?= $al['quantite'] ?>g)</span>
                        </span>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- FONCTIONNALITÉ 1 : Score écologique barre après validation -->
                    <div class="eco-panel" style="background:<?= $lbl['bg'] ?>;border:1px solid <?= $lbl['color'] ?>30;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="eco-score-circle" style="color:<?= $lbl['color'] ?>;border-color:<?= $lbl['color'] ?>;width:52px;height:52px;">
                                <span style="font-size:18px;font-weight:700;"><?= $score ?></span>
                                <span style="font-size:8px;opacity:.7;">/100</span>
                            </div>
                            <div style="flex:1;">
                                <p style="font-size:13px;font-weight:700;color:<?= $lbl['color'] ?>;margin:0;"><?= $lbl['emoji'] ?> <?= $lbl['label'] ?></p>
                                <p style="font-size:11px;color:<?= $lbl['color'] ?>;opacity:.8;margin:2px 0 6px;"><?= $lbl['desc'] ?></p>
                                <div class="eco-bar-track">
                                    <div class="eco-bar-fill" style="width:<?= $score ?>%;background:<?= $lbl['bar'] ?>;"></div>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <p style="font-size:11px;color:<?= $lbl['color'] ?>;margin:0;">CO₂</p>
                                <p style="font-size:14px;font-weight:700;color:<?= $lbl['color'] ?>;font-family:'Cormorant Garamond',serif;margin:0;"><?= round($totaux['total_co2'],2) ?> kg</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FONCTIONNALITÉ 3 : Description accordéon du repas -->
                <div class="repas-detail" id="detail_<?= $r['id_repas'] ?>">
                    <div class="desc-panel" style="margin:0 20px 16px;">
                        <p style="font-size:12px;font-weight:700;color:var(--vert);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
                            <i class="fas fa-info-circle" style="margin-right:5px;"></i> Description du repas
                        </p>
                        <!-- Résumé narratif -->
                        <p style="font-size:13px;color:var(--vert);line-height:1.7;margin-bottom:10px;">
                            <?= $descData['resume'] ?>
                        </p>
                        <!-- Badges caractéristiques -->
                        <div style="margin-bottom:10px;">
                        <?php foreach ($descData['badges'] as $b): ?>
                            <span class="desc-badge" style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;">
                                <?= $b['emoji'] ?> <?= $b['label'] ?>
                            </span>
                        <?php endforeach; ?>
                        </div>
                        <!-- Détails chiffrés -->
                        <?php foreach ($descData['details'] as $d): ?>
                        <p style="font-size:11px;color:#6b7280;margin:2px 0;"><?= $d ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Consultation aliments (colonne droite, hors CRUD) -->
        <div style="position:sticky;top:80px;">
            <h2 class="hf" style="font-size:22px;color:var(--vert);margin-bottom:10px;">Aliments disponibles</h2>
            <div style="position:relative;margin-bottom:10px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                <input id="searchAlim" type="text" placeholder="Rechercher…" oninput="filterConsult()"
                       style="width:100%;padding:8px 12px 8px 34px;border-radius:99px;border:1.5px solid #d0c8be;font-size:12px;outline:none;">
            </div>
            <div id="consultList" style="display:flex;flex-direction:column;gap:7px;max-height:420px;overflow-y:auto;padding-right:2px;">
            <?php foreach ($aliments as $al):
                $c=$c=typeConfig($al['type']); $sv=alimentSVG($al['nom'],$al['type'],$c,32); ?>
            <div class="consult-card" data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>">
                <div style="display:flex;align-items:center;gap:8px;">
                    <?= $sv ?>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:12px;font-weight:600;color:var(--vert);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($al['nom']) ?></p>
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9px;"><?= htmlspecialchars($al['type']) ?></span>
                    </div>
                    <p style="font-size:12px;font-weight:700;color:var(--vert);flex-shrink:0;"><?= round($al['calories'],0) ?><span style="font-size:9px;color:#9ca3af;"> kcal</span></p>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <a href="fo_alimentlist.php" style="display:block;text-align:center;margin-top:10px;font-size:12px;color:var(--violet);text-decoration:none;font-weight:600;">Voir tous les aliments →</a>
        </div>

    </div>
</div>


<!-- ════════════════════════════════════════════════════════════
     SECTION A — CRUD : CREATE — Modal formulaire
     ════════════════════════════════════════════════════════════ -->
<div id="createModal" class="modal-overlay" onclick="if(event.target===this)closeModal('createModal')">
    <div class="modal-box">
        <div style="padding:24px 28px;">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Créer un repas</h3>
                <button onclick="closeModal('createModal')" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;">&times;</button>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Colonne gauche : formulaire CRUD -->
                <div>
                    <form id="createRepasForm" action="../../controller/repascontroller.php" method="POST" novalidate>
                        <input type="hidden" name="action" value="create">

                        <!-- Nom + Date -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                            <div>
                                <label class="lbl">Nom du repas *</label>
                                <input type="text" name="nom_repas" id="f_nom" class="fi" placeholder="ex: Déjeuner équilibré" maxlength="150">
                                <p class="err-msg" id="e_nom">Nom obligatoire (min 2 caractères).</p>
                            </div>
                            <div>
                                <label class="lbl">Date & heure *</label>
                                <input type="datetime-local" name="date_repas" id="f_date" class="fi" onchange="onDateChange()">
                                <p class="err-msg" id="e_date">Date obligatoire.</p>
                            </div>
                        </div>

                        <!-- Sélection des aliments -->
                        <div style="margin-bottom:14px;">
                            <label class="lbl">Aliments * <span style="font-weight:400;text-transform:none;font-size:11px;color:#9ca3af;">— cliquez pour sélectionner</span></label>
                            <p class="err-msg" id="e_alim" style="display:none;margin-bottom:6px;">Sélectionnez au moins un aliment.</p>

                            <div style="position:relative;margin-bottom:7px;">
                                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:11px;"></i>
                                <input type="text" placeholder="Filtrer…" oninput="filterAlimModal(this.value)"
                                       style="width:100%;padding:7px 10px 7px 30px;border-radius:10px;border:1.5px solid #e8e0d8;font-size:12px;outline:none;">
                            </div>

                            <div class="alim-grid" id="alimGrid">
                            <?php foreach ($aliments as $al):
                                $c=$c=typeConfig($al['type']); $sv=alimentSVG($al['nom'],$al['type'],$c,24); ?>
                            <div class="alim-item" id="item_<?= $al['id_aliment'] ?>"
                                 onclick="toggleAliment(<?= $al['id_aliment'] ?>, '<?= addslashes($al['nom']) ?>')"
                                 data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>"
                                 data-calories="<?= $al['calories'] ?>"
                                 data-proteines="<?= $al['proteines'] ?>"
                                 data-glucides="<?= $al['glucides'] ?>"
                                 data-lipides="<?= $al['lipides'] ?>"
                                 data-fibres="<?= $al['fibres'] ?>"
                                 data-sucre="<?= $al['sucre'] ?>"
                                 data-sodium="<?= $al['sodium'] ?>"
                                 data-co2="<?= $al['co2'] ?>">
                                <input type="checkbox" name="aliments[]" id="chk_<?= $al['id_aliment'] ?>"
                                       value="<?= $al['id_aliment'] ?>"
                                       onclick="event.stopPropagation();" onchange="syncToggle(<?= $al['id_aliment'] ?>)">
                                <?= $sv ?>
                                <div style="flex:1;min-width:0;">
                                    <p style="font-size:10px;font-weight:600;color:var(--vert);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($al['nom']) ?></p>
                                    <p style="font-size:9px;color:#9ca3af;margin:0;"><?= round($al['calories'],0) ?> kcal</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            </div>

                            <!-- Quantités -->
                            <div id="selectionZone" style="margin-top:10px;display:none;">
                                <p style="font-size:10px;font-weight:700;color:var(--vert);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Quantités (grammes)</p>
                                <div id="quantitesList" style="display:flex;flex-direction:column;gap:7px;max-height:120px;overflow-y:auto;"></div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div style="display:flex;gap:10px;margin-top:14px;">
                            <button type="button" onclick="closeModal('createModal')"
                                style="flex:1;padding:11px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                                Annuler
                            </button>
                            <button type="button" onclick="validateAndCreate()"
                                style="flex:1;padding:11px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                                <i class="fas fa-check" style="margin-right:5px;"></i> Enregistrer le repas
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ════════════════════════════════════════════
                     SECTION B — FONCTIONNALITÉS INNOVANTES
                     Colonne droite du formulaire
                     Tout est calculé en JS en temps réel
                     ════════════════════════════════════════════ -->
                <div>

                    <!-- FONCTIONNALITÉ 1 : Indicateur écologique en temps réel -->
                    <div id="ecoPanel" class="eco-panel" style="background:#f4ede4;border:1px solid #e8e0d8;margin-bottom:14px;">
                        <p style="font-size:11px;font-weight:700;color:var(--vert);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                            🌿 Score écologique du repas
                        </p>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="eco-score-circle" id="ecoCircle" style="color:#9ca3af;border-color:#d0c8be;">
                                <span id="ecoScoreVal" style="font-size:18px;font-weight:700;">—</span>
                                <span style="font-size:8px;opacity:.7;">/100</span>
                            </div>
                            <div style="flex:1;">
                                <p id="ecoLabel" style="font-size:13px;font-weight:700;color:#9ca3af;margin:0;">Sélectionnez des aliments</p>
                                <p id="ecoDesc"  style="font-size:11px;color:#9ca3af;margin:2px 0 6px;">L'indicateur se mettra à jour en temps réel</p>
                                <div class="eco-bar-track">
                                    <div class="eco-bar-fill" id="ecoBarFill" style="width:0%;background:#9ca3af;"></div>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <p style="font-size:10px;color:#9ca3af;margin:0;">CO₂ estimé</p>
                                <p id="ecoCo2" style="font-size:13px;font-weight:700;color:#9ca3af;font-family:'Cormorant Garamond',serif;margin:0;">0 kg</p>
                            </div>
                        </div>
                    </div>

                    <!-- FONCTIONNALITÉ 2 : Alertes nutritionnelles en temps réel -->
                    <div id="alertesPanel" style="margin-bottom:14px;display:none;">
                        <p style="font-size:11px;font-weight:700;color:#8a2020;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
                            ⚠️ Déséquilibres détectés
                        </p>
                        <div id="alertesList"></div>
                    </div>

                    <!-- FONCTIONNALITÉ 2 : Recommandations contextuelles -->
                    <div id="recosPanel" style="display:none;">
                        <p style="font-size:11px;font-weight:700;color:#1a4a7a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">
                            💡 Suggestions pour votre repas
                        </p>
                        <div id="recosList"></div>
                        <p id="contextInfo" style="font-size:10px;color:#9ca3af;margin-top:8px;font-style:italic;"></p>
                    </div>

                    <!-- Placeholder quand rien n'est sélectionné -->
                    <div id="noSelMsg" style="text-align:center;padding:24px;color:#c4bdb5;">
                        <i class="fas fa-leaf" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                        <p style="font-size:12px;">Sélectionnez des aliments pour voir l'analyse en temps réel</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════════
     JAVASCRIPT
     ════════════════════════════════════════════════════════════ -->
<script>
/* ── Données PHP → JS (tous les aliments avec leurs valeurs nutritionnelles) */
const ALIMENTS_DATA = <?= $alimentsJson ?>;

/* ── Curseur ──────────────────────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button,input').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
})();

/* ── Modal ─────────────────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/* ── Accordéon FONCTIONNALITÉ 3 ─────────────────────────────────
   Affiche/cache la description du repas au clic                  */
function toggleDetail(id) {
    const panel   = document.getElementById('detail_' + id);
    const chevron = document.getElementById('chevron_' + id);
    const isOpen  = panel.classList.contains('open');
    panel.classList.toggle('open', !isOpen);
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}

/* ════════════════════════════════════════════════════════════════
   SECTION A — CRUD : Gestion des sélections d'aliments
   ════════════════════════════════════════════════════════════════ */

const selected = {}; /* { id_aliment: nom } */

function toggleAliment(id, nom) {
    const item = document.getElementById('item_' + id);
    const chk  = document.getElementById('chk_' + id);
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
    /* Déclenche la mise à jour des fonctionnalités innovantes */
    analyseTempsReel();
}

function syncToggle(id) {
    const chk  = document.getElementById('chk_' + id);
    const item = document.getElementById('item_' + id);
    const nom  = item.querySelector('p').textContent;
    if (chk.checked) { selected[id] = nom; item.classList.add('selected'); }
    else { delete selected[id]; item.classList.remove('selected'); }
    updateQuantitesList();
    analyseTempsReel();
}

/* Met à jour la liste des quantités */
function updateQuantitesList() {
    const zone = document.getElementById('selectionZone');
    const list = document.getElementById('quantitesList');
    const keys = Object.keys(selected);
    if (!keys.length) { zone.style.display = 'none'; return; }
    zone.style.display = 'block';
    list.innerHTML = keys.map(id => `
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:11px;color:var(--vert);font-weight:600;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${selected[id]}</span>
            <input type="number" name="quantites[${id}]" value="100" min="1" max="2000"
                   onchange="analyseTempsReel()"
                   style="width:70px;padding:4px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;" placeholder="g">
            <span style="font-size:10px;color:#9ca3af;">g</span>
        </div>
    `).join('');
}

/* Filtre la grille d'aliments dans le formulaire */
function filterAlimModal(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#alimGrid .alim-item').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* Filtre la consultation d'aliments */
function filterConsult() {
    const q = document.getElementById('searchAlim').value.toLowerCase();
    document.querySelectorAll('#consultList .consult-card').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* ════════════════════════════════════════════════════════════════
   SECTION B — FONCTIONNALITÉS INNOVANTES (JS)
   Toutes calculées côté client en temps réel dans le formulaire
   ════════════════════════════════════════════════════════════════ */

/**
 * Détermine le moment de la journée selon l'heure saisie
 */
function getMomentJournee(heure) {
    const h = parseInt(heure.split(':')[0]);
    if (h >= 6  && h < 10) return { key:'petit_dej', label:'☀️ Petit-déjeuner' };
    if (h >= 11 && h < 15) return { key:'dejeuner',  label:'🌤️ Déjeuner' };
    if (h >= 18 && h < 23) return { key:'diner',     label:'🌙 Dîner' };
    return { key:'collation', label:'🍎 Collation' };
}

/**
 * Détermine la saison selon le mois actuel
 */
function getSaison() {
    const m = new Date().getMonth() + 1;
    if ([3,4,5].includes(m))  return { key:'printemps', label:'🌸 Printemps' };
    if ([6,7,8].includes(m))  return { key:'ete',       label:'☀️ Été' };
    if ([9,10,11].includes(m))return { key:'automne',   label:'🍂 Automne' };
    return { key:'hiver', label:'❄️ Hiver' };
}

/**
 * FONCTIONNALITÉ 1 : Calcule le score écologique (JS)
 * Même logique que scoreEcologique() dans repas_helpers.php
 */
function calcScore(totaux) {
    if (totaux.poids <= 0) return 0;
    const co2ParKg   = totaux.co2 / (totaux.poids / 1000);
    const scoreCo2   = Math.max(0, Math.min(100, 100 - (co2ParKg / 6) * 100));
    const scoreFib   = Math.min(100, (totaux.fibres / 10) * 100);
    const ratioSucre = totaux.glucides > 0 ? totaux.sucre / totaux.glucides : 0;
    const scoreSucre = Math.max(0, Math.min(100, 100 - (ratioSucre * 100)));
    return Math.round(scoreCo2 * 0.60 + scoreFib * 0.20 + scoreSucre * 0.20);
}

/**
 * Retourne le label écologique selon le score
 */
function getLabelEco(score) {
    if (score >= 80) return { label:'Repas écologique',      emoji:'🌿', color:'#1a372f', bg:'#e8f0e9', bar:'#4caf50', desc:'Excellent choix pour la planète !' };
    if (score >= 60) return { label:'Repas acceptable',      emoji:'🌱', color:'#4a7a50', bg:'#f1f8e9', bar:'#8bc34a', desc:'Bon bilan, quelques ajustements possibles.' };
    if (score >= 40) return { label:'Repas à améliorer',     emoji:'⚠️', color:'#8a6510', bg:'#fff9e6', bar:'#ffc107', desc:'Impact modéré, des substitutions sont conseillées.' };
    if (score >= 20) return { label:'Repas polluant',         emoji:'🌫️', color:'#c07020', bg:'#fff3e0', bar:'#ff9800', desc:'Impact élevé, privilégiez des aliments locaux.' };
    return             { label:'Repas très polluant',        emoji:'🔴', color:'#8a2020', bg:'#faeaea', bar:'#f44336', desc:'Impact très fort. Revoyez la composition.' };
}

/**
 * FONCTIONNALITÉ 2 : Détecte les déséquilibres nutritionnels (JS)
 * Même logique que analyseNutritionnelle() dans repas_helpers.php
 */
function detecterAlertes(totaux, moment) {
    const alertes = [];
    const objets = {
        petit_dej : { calMax:600,  calMin:250, protMin:10, lipMax:20 },
        dejeuner  : { calMax:900,  calMin:400, protMin:20, lipMax:35 },
        diner     : { calMax:700,  calMin:300, protMin:15, lipMax:25 },
        collation : { calMax:300,  calMin:50,  protMin:5,  lipMax:15 },
    };
    const obj = objets[moment] || objets['dejeuner'];
    if (totaux.calories <= 0) return alertes;

    if (totaux.calories > obj.calMax)
        alertes.push({ type:'erreur',    emoji:'🔥', msg:`Trop calorique : ${Math.round(totaux.calories)} kcal (max ${obj.calMax} kcal).` });
    if (totaux.calories < obj.calMin && totaux.calories > 0)
        alertes.push({ type:'warning',   emoji:'⚡', msg:`Repas trop léger : ${Math.round(totaux.calories)} kcal.` });
    if (totaux.proteines < obj.protMin)
        alertes.push({ type:'erreur',    emoji:'💪', msg:`Manque de protéines : ${totaux.proteines.toFixed(1)}g (min ${obj.protMin}g).` });
    if (totaux.lipides > obj.lipMax)
        alertes.push({ type:'erreur',    emoji:'🧈', msg:`Trop de gras : ${totaux.lipides.toFixed(1)}g (max ${obj.lipMax}g).` });
    if (totaux.glucides > 0 && (totaux.sucre / totaux.glucides) > 0.5)
        alertes.push({ type:'erreur',    emoji:'🍬', msg:`Trop de sucre : ${totaux.sucre.toFixed(1)}g = ${Math.round(totaux.sucre/totaux.glucides*100)}% des glucides.` });
    if (totaux.calories > 200 && totaux.fibres < 5)
        alertes.push({ type:'warning',   emoji:'🥦', msg:`Manque de fibres : ${totaux.fibres.toFixed(1)}g. Ajoutez des légumes.` });
    if (totaux.sodium > 800)
        alertes.push({ type:'warning',   emoji:'🧂', msg:`Trop de sodium : ${Math.round(totaux.sodium)}mg.` });
    if (totaux.poids > 1200)
        alertes.push({ type:'gaspillage',emoji:'♻️', msg:`Quantité importante (${Math.round(totaux.poids)}g). Risque de gaspillage.` });

    return alertes;
}

/**
 * FONCTIONNALITÉ 2 : Génère les recommandations contextuelles (JS)
 * Même logique que recommandations() dans repas_helpers.php
 */
function genRecommandations(alertes, moment, saison) {
    const recs = [];
    const saisonLegumes = {
        printemps:'asperges, petits pois',
        ete:'tomates, courgettes, concombres',
        automne:'carottes, courges, poireaux',
        hiver:'brocoli, chou, navets'
    };

    alertes.forEach(a => {
        if (a.msg.includes('calorique'))   recs.push({ emoji:'🥗', texte:`Remplacez un aliment calorique par des légumes frais de ${saison.label.split(' ')[1]||'saison'}.` });
        if (a.msg.includes('léger'))       recs.push({ emoji:'🫘', texte:`Enrichissez avec des légumineuses ou des céréales complètes pour tenir jusqu'au prochain repas.` });
        if (a.msg.includes('protéines')) {
            const p = moment === 'petit_dej' ? '🥚 Ajoutez des œufs ou du fromage blanc.' :
                      moment === 'dejeuner'  ? '🐟 Privilégiez poisson ou légumineuses.' :
                                               '🍗 Une portion de viande maigre ou tofu.';
            recs.push({ emoji:'💪', texte: p });
        }
        if (a.msg.includes('gras'))        recs.push({ emoji:'🫒', texte:`Remplacez les graisses saturées par de l'huile d'olive. Évitez les fritures.` });
        if (a.msg.includes('sucre'))       recs.push({ emoji:'🍓', texte:`Substituez le sucre par des fruits frais de saison (${saisonLegumes[saison.key]||'fruits de saison'}).` });
        if (a.msg.includes('fibres'))      recs.push({ emoji:'🥦', texte:`Ajoutez des légumes de saison : ${saisonLegumes[saison.key]||'légumes frais'}.` });
        if (a.msg.includes('sodium'))      recs.push({ emoji:'🌿', texte:`Remplacez le sel par des herbes aromatiques fraîches.` });
        if (a.msg.includes('gaspillage'))  recs.push({ emoji:'📦', texte:`Réduisez les quantités. Un repas équilibré ≈ 400–800g selon le moment.` });
    });

    /* Conseil saisonnier permanent */
    const saisonConseils = {
        printemps: { emoji:'🌸', texte:'Au printemps, profitez des asperges, radis et petits pois de saison.' },
        ete:       { emoji:'☀️', texte:'En été, hydratez-vous avec concombres, tomates et pastèques.' },
        automne:   { emoji:'🍂', texte:'En automne, les courges et champignons sont nutritifs et de saison.' },
        hiver:     { emoji:'❄️', texte:'En hiver, les soupes de légumes racines réchauffent et renforcent l\'immunité.' },
    };
    recs.push(saisonConseils[saison.key]);

    return recs;
}

/**
 * ANALYSE EN TEMPS RÉEL — Mise à jour de l'interface innovante
 * Appelée à chaque sélection/désélection d'aliment ou changement
 * de quantité. Met à jour les 2 panneaux fonctionnalités.
 */
function analyseTempsReel() {
    const ids  = Object.keys(selected);
    const noSel= document.getElementById('noSelMsg');
    const ecoP = document.getElementById('ecoPanel');
    const alP  = document.getElementById('alertesPanel');
    const rcP  = document.getElementById('recosPanel');

    if (ids.length === 0) {
        /* Rien sélectionné : on remet l'état par défaut */
        noSel.style.display = 'block';
        document.getElementById('ecoScoreVal').textContent = '—';
        document.getElementById('ecoLabel').textContent    = 'Sélectionnez des aliments';
        document.getElementById('ecoDesc').textContent     = 'L\'indicateur se mettra à jour en temps réel';
        document.getElementById('ecoBarFill').style.width  = '0%';
        document.getElementById('ecoBarFill').style.background = '#9ca3af';
        document.getElementById('ecoCircle').style.color   = '#9ca3af';
        document.getElementById('ecoCircle').style.borderColor = '#d0c8be';
        document.getElementById('ecoCo2').textContent      = '0 kg';
        ecoP.style.background = '#f4ede4';
        ecoP.style.borderColor = '#e8e0d8';
        alP.style.display  = 'none';
        rcP.style.display  = 'none';
        return;
    }
    noSel.style.display = 'none';

    /* Calcul des totaux à partir des données PHP injectées en JS */
    const totaux = { calories:0, proteines:0, glucides:0, lipides:0,
                     fibres:0, sucre:0, sodium:0, co2:0, poids:0 };

    ids.forEach(id => {
        /* Récupère la quantité saisie (ou 100g par défaut) */
        const qteInput = document.querySelector(`input[name="quantites[${id}]"]`);
        const qte      = qteInput ? parseFloat(qteInput.value) || 100 : 100;
        const a        = ALIMENTS_DATA[id];
        if (!a) return;
        const f = qte / 100;
        totaux.calories  += (parseFloat(a.calories)  || 0) * f;
        totaux.proteines += (parseFloat(a.proteines) || 0) * f;
        totaux.glucides  += (parseFloat(a.glucides)  || 0) * f;
        totaux.lipides   += (parseFloat(a.lipides)   || 0) * f;
        totaux.fibres    += (parseFloat(a.fibres)    || 0) * f;
        totaux.sucre     += (parseFloat(a.sucre)     || 0) * f;
        totaux.sodium    += (parseFloat(a.sodium)    || 0) * f;
        totaux.co2       += (parseFloat(a.co2)       || 0) * f;
        totaux.poids     += qte;
    });

    /* Contexte temporel */
    const dateVal  = document.getElementById('f_date').value;
    const heure    = dateVal ? dateVal.split('T')[1] || '12:00' : getHeureLocale();
    const moment   = getMomentJournee(heure);
    const saison   = getSaison();

    /* ── FONCTIONNALITÉ 1 : Mise à jour score écologique ───── */
    const score = calcScore(totaux);
    const lbl   = getLabelEco(score);

    document.getElementById('ecoScoreVal').textContent = score;
    document.getElementById('ecoLabel').textContent    = `${lbl.emoji} ${lbl.label}`;
    document.getElementById('ecoDesc').textContent     = lbl.desc;
    document.getElementById('ecoBarFill').style.width  = score + '%';
    document.getElementById('ecoBarFill').style.background = lbl.bar;
    document.getElementById('ecoCircle').style.color       = lbl.color;
    document.getElementById('ecoCircle').style.borderColor = lbl.color;
    document.getElementById('ecoCo2').textContent  = totaux.co2.toFixed(2) + ' kg';
    document.getElementById('ecoCo2').style.color  = lbl.color;
    ecoP.style.background  = lbl.bg;
    ecoP.style.borderColor = lbl.color + '40';

    /* ── FONCTIONNALITÉ 2 : Alertes nutritionnelles ─────────── */
    const alertes = detecterAlertes(totaux, moment.key);
    const alList  = document.getElementById('alertesList');

    if (alertes.length > 0) {
        alP.style.display = 'block';
        alList.innerHTML = alertes.map(a => {
            const cssClass = a.type === 'erreur' ? 'alerte-erreur'
                           : a.type === 'gaspillage' ? 'alerte-gaspillage'
                           : 'alerte-warning';
            return `<div class="alerte-item ${cssClass}">
                        <span style="font-size:16px;flex-shrink:0;">${a.emoji}</span>
                        <span>${a.msg}</span>
                    </div>`;
        }).join('');
    } else {
        alP.style.display = 'none';
    }

    /* ── FONCTIONNALITÉ 2 : Recommandations contextuelles ────── */
    const recs   = genRecommandations(alertes, moment, saison);
    const rcList = document.getElementById('recosList');
    const ctxInfo= document.getElementById('contextInfo');

    if (recs.length > 0) {
        rcP.style.display = 'block';
        rcList.innerHTML  = recs.map(r =>
            `<div class="reco-item">
                <span style="font-size:15px;flex-shrink:0;">${r.emoji}</span>
                <span>${r.texte}</span>
             </div>`
        ).join('');
        ctxInfo.textContent = `Analyse basée sur : ${moment.label} · ${saison.label}`;
    } else {
        rcP.style.display = 'none';
    }
}

/* Récupère l'heure locale actuelle */
function getHeureLocale() {
    const now = new Date();
    return `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
}

/* Déclenche l'analyse quand la date change */
function onDateChange() {
    analyseTempsReel();
}

/* ── CRUD : Validation et soumission du formulaire ────────────── */
function validateAndCreate() {
    let valid = true;
    function setErr(id, errId, show) {
        const el=document.getElementById(id), er=document.getElementById(errId);
        if(!el||!er) return;
        if(show){ el.classList.add('error'); er.classList.add('show'); valid=false; }
        else    { el.classList.remove('error'); er.classList.remove('show'); }
    }
    setErr('f_nom',  'e_nom',  document.getElementById('f_nom').value.trim().length < 2);
    setErr('f_date', 'e_date', !document.getElementById('f_date').value);
    const hasAlim = Object.keys(selected).length > 0;
    document.getElementById('e_alim').style.display = hasAlim ? 'none' : 'block';
    if (!hasAlim) valid = false;
    if (valid) document.getElementById('createRepasForm').submit();
    else {
        const first = document.querySelector('.fi.error');
        if (first) first.scrollIntoView({behavior:'smooth',block:'center'});
    }
}

/* ── Initialisation ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    /* Pré-remplir date/heure actuelle */
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const f = document.getElementById('f_date');
    if (f) f.value = now.toISOString().slice(0, 16);
});
</script>
</body>
</html>
