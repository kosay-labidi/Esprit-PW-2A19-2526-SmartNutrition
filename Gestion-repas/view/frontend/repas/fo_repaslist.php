<?php

require_once __DIR__ . '/../../../helpers/aliment_helpers.php';
require_once __DIR__ . '/../../../helpers/repas_helpers.php';

if (!isset($mesRepas)) {
    /* Fallback : accès direct à la vue → on passe par le Controller */
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../controller/repascontroller.php';
    require_once __DIR__ . '/../../../controller/alimentcontroller.php';
    global $pdo;
    $mesRepas     = repas_getAllByUser($pdo, 1);
    $aliments     = aliment_getAll($pdo);
    $repaDetails  = [];
    foreach ($mesRepas as $r) {
        $id = (int)$r['id_repas'];
        $repaDetails[$id] = [
            'aliments' => repas_getAlimentsOfRepas($pdo, $id),
            'totaux'   => repas_getTotauxNutritionnels($pdo, $id),
        ];
    }
    $success  = $_GET['success'] ?? '';
    $error    = urldecode($_GET['error'] ?? '');
    $alimentsJson = json_encode(array_column($aliments, null, 'id_aliment'), JSON_UNESCAPED_UNICODE);
    $historiqueJson = json_encode(array_map(function($r) use ($repaDetails) {
        $id  = (int)$r['id_repas'];
        $tot = $repaDetails[$id]['totaux'] ?? [];
        $al  = $repaDetails[$id]['aliments'] ?? [];
        return ['id'=>$id,'nom'=>$r['nom_repas'],'date'=>$r['date_repas'],
                'calories'=>round($tot['total_calories']??0,1),
                'proteines'=>round($tot['total_proteines']??0,1),
                'lipides'=>round($tot['total_lipides']??0,1),
                'fibres'=>round($tot['total_fibres']??0,1),
                'sucre'=>round($tot['total_sucre']??0,1),
                'sodium'=>round($tot['total_sodium']??0,1),
                'co2'=>round($tot['total_co2']??0,2),
                'aliments'=>array_column($al,'nom')];
    }, $mesRepas), JSON_UNESCAPED_UNICODE);
    if ($alimentsJson === false) $alimentsJson = '{}';
    if ($historiqueJson === false) $historiqueJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Mes Repas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <link rel="stylesheet" href="../css/fo_repaslist.css">
    <script src="../js/fo_repaslist_init.js"></script>
    <script src="../js/fo_repaslist.js"></script>
    <link rel="stylesheet" href="../css/cursor.css">
</head>
<body>
<div id="cursor"></div><div id="cursor-trail"></div>

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
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <button onclick="openModal('createModal')"
            id="btnCreerRepas" style="background:rgba(255,255,255,.1);color:white;padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:1px solid rgba(255,255,255,.4);cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-plus"></i> Créer un repas
        </button>
        <button onclick="toggleCulture()" id="btnCulture"
            style="background:rgba(255,255,255,.1);color:white;padding:10px 20px;border-radius:99px;font-size:13px;font-weight:600;border:1px solid rgba(255,255,255,.25);cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
            <span style="width:22px;height:22px;border-radius:50%;background:#EAF3DE;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-lightbulb" style="color:#1a372f;font-size:10px;"></i></span>
            Culture du jour
            <span id="cultureTypeLabel" style="font-size:11px;opacity:.7;">- chargement...</span>
        </button>
        </div>
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
            <!-- RECHERCHE + TRI + STATS -->
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <h2 class="hf" style="font-size:28px;color:var(--vert);margin:0;">Mes repas</h2>
                <div style="position:relative;flex:1;min-width:160px;">
                    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                    <input id="rechercheRepas" type="text" placeholder="Rechercher..."
                           oninput="appliquerFiltresRepas()"
                           style="width:100%;padding:8px 12px 8px 34px;border-radius:99px;border:1.5px solid #d0c8be;font-size:12px;outline:none;background:white;">
                </div>
                <select id="triRepas" onchange="appliquerTriRepas()"
                    style="padding:8px 12px;border-radius:10px;border:1.5px solid #d0c8be;font-size:12px;color:var(--vert);background:white;cursor:pointer;outline:none;">
                    <option value="">Trier par...</option>
                    <option value="date-desc">Date (recent)</option>
                    <option value="date-asc">Date (ancien)</option>
                    <option value="nom-asc">Nom A vers Z</option>
                    <option value="nom-desc">Nom Z vers A</option>
                    <option value="cal-desc">Calories (haut)</option>
                    <option value="cal-asc">Calories (bas)</option>
                    <option value="score-desc">Score eco (haut)</option>
                    <option value="score-asc">Score eco (bas)</option>
                    <option value="nb-desc">Nb aliments (haut)</option>
                </select>
                <button onclick="toggleStats()"
                    style="padding:8px 14px;border-radius:10px;border:1.5px solid var(--violet);background:rgba(167,139,250,.1);color:#7c5cbf;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;">
                    <i class="fas fa-chart-bar"></i> Statistiques
                </button>
            </div>

            <!-- TABLEAU DE BORD STATS -->
            <div id="statsPanel" style="display:none;background:white;border-radius:20px;border:1px solid #ede8e0;padding:22px;margin-bottom:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
                    <div><h3 class="hf" style="font-size:24px;color:var(--vert);margin:0;">Tableau de bord</h3></div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <select id="statsPeriode" onchange="calculerStats()"
                            style="padding:6px 12px;border-radius:8px;border:1.5px solid #d0c8be;font-size:12px;color:var(--vert);background:var(--sable);cursor:pointer;outline:none;">
                            <option value="tout">Tous les repas</option>
                            <option value="semaine">7 derniers jours</option>
                            <option value="mois">30 derniers jours</option>
                        </select>
                        <select id="statsCritere" onchange="calculerStats()"
                            style="padding:6px 12px;border-radius:8px;border:1.5px solid #d0c8be;font-size:12px;color:var(--vert);background:var(--sable);cursor:pointer;outline:none;">
                            <option value="calories">Calories</option>
                            <option value="score">Score ecologique</option>
                            <option value="nb">Nb aliments</option>
                            <option value="proteines">Proteines</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;" id="statsMetriques"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div style="border:1px solid #f0ece5;border-radius:14px;padding:14px;">
                        <p style="font-size:11px;font-weight:700;color:var(--vert);margin:0 0 4px;text-transform:uppercase;">Profil nutritionnel moyen</p>
                        <div style="position:relative;height:180px;"><canvas id="statsRadar"></canvas></div>
                    </div>
                    <div style="border:1px solid #f0ece5;border-radius:14px;padding:14px;">
                        <p id="statsEvoLabel" style="font-size:11px;font-weight:700;color:var(--vert);margin:0 0 4px;text-transform:uppercase;">Evolution</p>
                        <div style="position:relative;height:180px;"><canvas id="statsEvo"></canvas></div>
                    </div>
                </div>
                <div style="border:1px solid #f0ece5;border-radius:14px;padding:14px;">
                    <p id="statsBarLabel" style="font-size:11px;font-weight:700;color:var(--vert);margin:0 0 10px;text-transform:uppercase;">Par repas</p>
                    <div style="position:relative;height:140px;"><canvas id="statsBar"></canvas></div>
                </div>
            </div>

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

            <div id="repasContainer" style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach ($mesRepas as $r):
                /* Données via repaDetails (préparées par le Controller) */
                $id        = (int)$r['id_repas'];
                $alimRepas = $repaDetails[$id]['aliments'] ?? [];
                $totaux    = $repaDetails[$id]['totaux']   ?? [];

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
            <div class="repas-card"
                 data-nom="<?= strtolower(htmlspecialchars($r['nom_repas'])) ?>"
                 data-date="<?= $r['date_repas'] ?>"
                 data-cal="<?= !empty($totaux) ? round($totaux['total_calories'],0) : 0 ?>"
                 data-score="<?= $score ?>"
                 data-nb="<?= $r['nb_aliments'] ?>"
                 data-proteines="<?= round((float)($totaux['total_proteines'] ?? 0),1) ?>">

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
                            <button class="bedit" onclick="ouvrirUpdateModal(<?= $r['id_repas'] ?>)">
                                <i class="fas fa-pen" style="font-size:10px;"></i> Modifier
                            </button>
                            <a href="../../../controller/repascontroller.php?action=delete&id=<?= $r['id_repas'] ?>"
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
                            ['Calories',  round($totaux['total_calories'],0),  'kcal', 'var(--cal-color,#1a372f)'],
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

        <!-- PANNEAU DROIT : bascule entre Aliments et Culture du jour -->
        <div style="position:sticky;top:80px;">

            <div style="display:flex;gap:6px;margin-bottom:14px;">
                <button id="tabAliments" onclick="showTab('aliments')"
                    style="flex:1;padding:7px 10px;border-radius:10px;border:1.5px solid var(--vert);background:var(--vert);color:white;font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-carrot" style="font-size:11px;margin-right:4px;"></i> Aliments
                </button>
                <button id="tabCulture" onclick="showTab('culture')"
                    style="flex:1;padding:7px 10px;border-radius:10px;border:1.5px solid #d0c8be;background:white;color:var(--vert);font-size:12px;font-weight:600;cursor:pointer;">
                    <i class="fas fa-lightbulb" style="font-size:11px;margin-right:4px;"></i> Culture
                </button>
            </div>

            <div id="voletAliments">
            <div style="position:relative;margin-bottom:10px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                <input id="searchAlim" type="text" placeholder="Rechercher…" oninput="filterConsult()"
                       style="width:100%;padding:8px 12px 8px 34px;border-radius:99px;border:1.5px solid var(--border-input);font-size:12px;outline:none;background:var(--bg-input);color:var(--text-main);">
            </div>
            <div id="consultList" style="display:flex;flex-direction:column;gap:7px;max-height:360px;overflow-y:auto;padding-right:2px;">

            <?php foreach ($aliments as $al):
                $c=$c=typeConfig($al['type']); $sv=alimentSVG($al['nom'],$al['type'],$c,32); ?>
            <div class="consult-card" data-nom="<?= strtolower(htmlspecialchars($al['nom'])) ?>">
                <div style="display:flex;align-items:center;gap:8px;">
                    <?= $sv ?>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:12px;font-weight:600;color:var(--text-main);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($al['nom']) ?></p>
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9px;"><?= htmlspecialchars($al['type']) ?></span>
                    </div>
                    <p style="font-size:12px;font-weight:700;color:var(--vert);flex-shrink:0;"><?= round($al['calories'],0) ?><span style="font-size:9px;color:#9ca3af;"> kcal</span></p>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <a href="fo_alimentlist.php" style="display:block;text-align:center;margin-top:10px;font-size:12px;color:var(--violet);text-decoration:none;font-weight:600;">Voir tous les aliments</a>
            </div>

            <div id="voletCulture" style="display:none;">
                <div id="cultureLoading" style="text-align:center;padding:30px 10px;color:#9ca3af;">
                    <div style="width:24px;height:24px;border:2px solid #d0c8be;border-top-color:var(--vert);border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 10px;"></div>
                    <p style="font-size:12px;margin:0;">Gemini analyse...</p>
                </div>
                <div id="cultureContent" style="display:none;"></div>
            </div>

        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODALE MODIFICATION REPAS (même logique que createModal)
     ══════════════════════════════════════════════════════════ -->
<?php
/* Variables pour la modale update — chargées dynamiquement via JS */
$upId = 0; $upRepas = null; $upAlimActuels = []; $upAlimActuelsId = []; $upAlimActuelsQte = [];
?>
<div id="updateModal" class="modal-overlay" onclick="if(event.target===this)closeModal('updateModal')">
    <div class="modal-box">
        <div style="padding:24px 28px;">

            <!-- En-tête -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <div>
                    <h2 class="hf" style="font-size:32px;color:var(--text-main);margin:0;">Modifier le repas</h2>
                    <p style="font-size:13px;color:var(--text-muted);margin:4px 0 0;" id="updateModalSubtitle">—</p>
                </div>
                <button onclick="closeModal('updateModal')"
                    style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--text-muted);line-height:1;">×</button>
            </div>

            <!-- Formulaire (rempli dynamiquement par JS) -->
            <div id="updateModalBody">
                <div style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:12px;display:block;"></i>
                    Chargement du repas...
                </div>
            </div>
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
                    <form id="createRepasForm" action="../../../controller/repascontroller.php" method="POST" novalidate>
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
                                 onclick="toggleAliment(<?= $al['id_aliment'] ?>, <?= htmlspecialchars(json_encode($al['nom'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)"
                                 data-nom-orig="<?= htmlspecialchars($al['nom']) ?>"
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
                                id="btnEnregistrerRepas" style="flex:1;padding:11px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
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

const HISTORIQUE_REPAS = <?= $historiqueJson ?>;

/* ── Curseur (silent if elements missing in iframe) ──────────── */
try {
    (function(){
        const c=document.getElementById('cur'),t=document.getElementById('curt');
        if (!c && !t) return;
        let mx=0,my=0,tx=0,ty=0;
        if (c) document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
        if (t) (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
        if (c) document.querySelectorAll('a,button,input').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
    })();
} catch(e){}

/* ── Modal ─────────────────────────────────────────────────────── */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    if (id === 'createModal') {
        var f = document.getElementById('f_date');
        if (f && !f.value) {
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            f.value = now.toISOString().slice(0, 16);
        }
    }
}
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
    try {
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
        analyseTempsReel();
    } catch(e) {
        document.getElementById('recosPanel').style.display = 'block';
        document.getElementById('recosList').innerHTML = '<div style="padding:10px 14px;background:#faeaea;border-radius:10px;color:#8a2020;font-size:11px;"><b>Erreur JS :</b> ' + e.message + '</div>';
    }
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
            <input type="number" name="quantites[${id}]" value="100"
                   onchange="validerQuantite(this,'qte_err_${id}'); analyseTempsReel();"
                   id="qte_${id}"
                   style="width:70px;padding:4px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;">
            <span style="font-size:10px;color:#9ca3af;">g</span>
            <span id="qte_err_${id}" style="font-size:10px;color:#8a2020;display:none;font-weight:600;" title="Entre 1 et 2000 g">!</span>
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
    try {
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
    /* Recommandations IA via Gemini */
    var alimListIA = Object.keys(selected).map(function(id) {
        var inp = document.querySelector('input[name="quantites['+id+']"]');
        return {nom: selected[id], quantite: inp ? parseFloat(inp.value)||100 : 100};
    });
    genRecommandationsIA(totaux, alimListIA, moment, saison, score);
    } catch(e) {
        var rcP=document.getElementById("recosPanel"), rcList=document.getElementById("recosList");
        if (rcP) rcP.style.display='block';
        if (rcList) rcList.innerHTML='<div style="padding:10px 14px;background:#faeaea;border-radius:10px;color:#8a2020;font-size:11px;"><b>Erreur JS :</b> '+e.message+'</div>';
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
/* ────────────────────────────────────────────────────────────
   CONTRÔLE DE SAISIE — Validation JS (sans attributs HTML5)
   Règle : pas de required / min / max dans le HTML.
   Tout le contrôle se fait ici en JavaScript.
   ─────────────────────────────────────────────────────────── */

/**
 * validerQuantite() — Valide une quantité saisie (1 à 2000 g)
 * Appelée à chaque changement d'un champ quantité.
 * Affiche un indicateur rouge "!" si la valeur est invalide.
 */
function validerQuantite(input, errId) {
    const val = parseFloat(input.value);
    const err = document.getElementById(errId);
    const invalid = isNaN(val) || val < 1 || val > 2000;
    if (invalid) {
        input.style.borderColor = '#c09090';
        input.style.background  = '#fdf5f5';
        if (err) { err.style.display = 'inline'; err.title = 'Valeur entre 1 et 2000 g'; }
    } else {
        input.style.borderColor = '#e8e0d8';
        input.style.background  = 'white';
        if (err) err.style.display = 'none';
    }
    return !invalid;
}

/**
 * validateAndCreate() — Validation complète du formulaire
 * Vérifie : nom, date, sélection d'aliments, quantités.
 * Sans aucun attribut HTML5 (required, min, max).
 */
function validateAndCreate() {
    let valid = true;

    /* Utilitaire : marque/démarque un champ en erreur */
    function setErr(id, errId, show) {
        const el=document.getElementById(id), er=document.getElementById(errId);
        if(!el||!er) return;
        if(show){ el.classList.add('error'); er.classList.add('show'); valid=false; }
        else    { el.classList.remove('error'); er.classList.remove('show'); }
    }

    /* 1. Nom du repas : obligatoire, minimum 2 caractères */
    const nom = document.getElementById('f_nom').value.trim();
    setErr('f_nom', 'e_nom', nom.length < 2);

    /* 2. Date du repas : obligatoire */
    const date = document.getElementById('f_date').value;
    setErr('f_date', 'e_date', !date || date.trim() === '');

    /* 3. Aliments : au moins un sélectionné */
    const hasAlim = Object.keys(selected).length > 0;
    const eAlim   = document.getElementById('e_alim');
    if (!hasAlim) { eAlim.style.display = 'block'; valid = false; }
    else            eAlim.style.display = 'none';

    /* 4. Quantités : chaque aliment sélectionné doit avoir
          une quantité valide entre 1 et 2000 grammes.
          (validation purement JS, aucun attribut HTML5) */
    let qtesValides = true;
    Object.keys(selected).forEach(id => {
        const input = document.querySelector(`input[name="quantites[${id}]"]`);
        const errId = `qte_err_${id}`;
        if (input) {
            const ok = validerQuantite(input, errId);
            if (!ok) { qtesValides = false; valid = false; }
        }
    });

    if (!qtesValides) {
        /* Afficher un message global pour les quantités */
        const eAlim = document.getElementById('e_alim');
        eAlim.textContent = 'Vérifiez les quantités (entre 1 et 2000 g par aliment).';
        eAlim.style.display = 'block';
    }

    /* Soumission si tout est valide */
    if (valid) {
        document.getElementById('createRepasForm').submit();
    } else {
        const first = document.querySelector('.fi.error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/* ── Initialisation ──────────────────────────────────────────── */
(function() {
    var f = document.getElementById('f_date');
    if (f && !f.value) {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        f.value = now.toISOString().slice(0, 16);
    }
})();

/* === RECHERCHE === */
function appliquerFiltresRepas() {
    var q = document.getElementById("rechercheRepas").value.toLowerCase().trim();
    document.querySelectorAll("#repasContainer .repas-card").forEach(function(card) {
        card.style.display = (!q || card.dataset.nom.includes(q)) ? "" : "none";
    });
}

/* === TRI === */
function appliquerTriRepas() {
    var val = document.getElementById("triRepas").value;
    if (!val) return;
    var parts = val.split("-"), crit = parts[0], asc = parts[1] === "asc";
    function gv(el) {
        if (crit==="nom")   return el.dataset.nom  || "";
        if (crit==="date")  return el.dataset.date || "";
        if (crit==="cal")   return parseFloat(el.dataset.cal)   || 0;
        if (crit==="score") return parseFloat(el.dataset.score) || 0;
        if (crit==="nb")    return parseFloat(el.dataset.nb)    || 0;
        return "";
    }
    var box = document.getElementById("repasContainer");
    if (!box) return;
    var cards = Array.from(box.querySelectorAll(".repas-card"));
    cards.sort(function(a, b) {
        var va = gv(a), vb = gv(b);
        if (typeof va === "string") return asc ? va.localeCompare(vb) : vb.localeCompare(va);
        return asc ? va - vb : vb - va;
    });
    cards.forEach(function(el) { box.appendChild(el); });
}

/* === STATISTIQUES === */
var statsRadarInst = null, statsEvoInst = null, statsBarInst = null;

function toggleStats() {
    var panel = document.getElementById("statsPanel");
    if (!panel) return;
    var visible = panel.style.display === "block";
    panel.style.display = visible ? "none" : "block";
    if (!visible) calculerStats();
}

function calculerStats() {
    var crit = document.getElementById("statsCritere").value;
    var periode = document.getElementById("statsPeriode").value;
    var now = new Date();
    var all = Array.from(document.querySelectorAll("#repasContainer .repas-card"));
    var cards = all.filter(function(c) {
        if (periode === "tout") return true;
        var d = new Date(c.dataset.date), diff = (now - d) / 86400000;
        return periode === "semaine" ? diff <= 7 : diff <= 30;
    });
    if (cards.length === 0) {
        document.getElementById("statsMetriques").innerHTML = "<div style=\"grid-column:1/-1;text-align:center;padding:20px;color:#9ca3af;\">Aucun repas pour cette periode.</div>";
        return;
    }
    var labels=[], values=[], scores=[], dates=[], prots=[];
    cards.forEach(function(card) {
        var n = card.dataset.nom || "repas";
        labels.push(n.charAt(0).toUpperCase() + n.slice(1,14));
        scores.push(parseFloat(card.dataset.score) || 0);
        dates.push((card.dataset.date || "").slice(0,10));
        prots.push(parseFloat(card.dataset.proteines) || 0);
        var v = 0;
        if (crit==="calories")  v = parseFloat(card.dataset.cal)        || 0;
        if (crit==="score")     v = parseFloat(card.dataset.score)      || 0;
        if (crit==="nb")        v = parseFloat(card.dataset.nb)         || 0;
        if (crit==="proteines") v = parseFloat(card.dataset.proteines)  || 0;
        values.push(Math.round(v));
    });
    var total = values.reduce(function(a,b){return a+b;},0);
    var moy = Math.round(total / values.length);
    var max = Math.max.apply(null, values);
    var moySc = Math.round(scores.reduce(function(a,b){return a+b;},0) / scores.length);
    var u = {calories:"kcal",score:"/100",nb:"alim.",proteines:"g"}[crit] || "";
    var col = {calories:"#1a372f",score:"#4caf50",nb:"#a78bfa",proteines:"#60a5fa"}[crit] || "#1a372f";
    var dark = document.body.classList.contains('dark');
    var rows = [["Nb repas",cards.length,"",dark?"#e2e8f0":"#1a372f",dark?"#1e2a3a":"#e8f0e9"],["Moyenne",moy,u,dark?"#60a5fa":col,dark?"#1a2433":"#f9f6f2"],["Maximum",max,u,"#7c5cbf","#eeedfe"],["Score eco.",moySc,"/100",moySc>=60?"#1a372f":"#8a2020",moySc>=60?"#e8f0e9":"#faeaea"]];
    document.getElementById("statsMetriques").innerHTML = rows.map(function(r) {
        return "<div style=\"background:"+r[4]+";border-radius:12px;padding:14px;text-align:center;\">"
             + "<p style=\"font-size:22px;font-weight:700;color:"+r[3]+";font-family:'Cormorant Garamond',serif;margin:0;\">"+r[1]+"</p>"
             + "<p style=\"font-size:11px;color:#6b7280;margin:2px 0 0;\">"+r[0]+" <span style=\"opacity:.7;\">"+r[2]+"</span></p>"
             + "</div>";
    }).join("");
    var ptC = scores.map(function(s){return s>=60?"#4caf50":s>=40?"#EF9F27":"#E24B4A";});
    var bC  = scores.map(function(s){return s>=60?(dark?"rgba(96,165,250,.8)":"rgba(26,55,47,.8)"):s>=40?"rgba(239,159,39,.8)":(dark?"rgba(167,139,250,.8)":"rgba(226,75,74,.8)");});
    var mp  = Math.round(prots.reduce(function(a,b){return a+b;},0)/prots.length);
    var rd  = [Math.min(100,mp*3),Math.min(100,moySc),75,65,50];
    if (statsRadarInst) statsRadarInst.destroy();
    statsRadarInst = new Chart(document.getElementById("statsRadar").getContext("2d"),{type:"radar",data:{labels:["Prot.","Score eco.","Lipides","Glucides","Sodium"],datasets:[{data:rd,borderColor:dark?"#e2e8f0":"#1a372f",backgroundColor:dark?"rgba(226,232,240,0.1)":"rgba(26,55,47,0.15)",pointBackgroundColor:dark?"#e2e8f0":"#1a372f",pointRadius:4},{data:[70,70,60,70,50],borderColor:"rgba(160,160,160,0.5)",backgroundColor:"transparent",borderDash:[5,4],pointRadius:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{r:{min:0,max:100,ticks:{display:false},grid:{color:"rgba(180,180,180,0.2)"},pointLabels:{font:{size:11},color:"#6b7280"}}}}});
    document.getElementById("statsEvoLabel").textContent={calories:"Evolution calories",score:"Evolution score eco.",nb:"Evolution nb aliments",proteines:"Evolution proteines"}[crit]||"";
    if (statsEvoInst) statsEvoInst.destroy();
    statsEvoInst = new Chart(document.getElementById("statsEvo").getContext("2d"),{type:"line",data:{labels:dates,datasets:[{data:values,borderColor:dark?"#e2e8f0":"#1a372f",backgroundColor:dark?"rgba(226,232,240,0.06)":"rgba(26,55,47,0.08)",fill:true,tension:0.4,pointBackgroundColor:ptC,pointBorderColor:"#fff",pointBorderWidth:2,pointRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:"rgba(180,180,180,0.15)"},ticks:{font:{size:10},color:"#9ca3af"}},x:{grid:{display:false},ticks:{font:{size:10},color:"#9ca3af",maxRotation:30}}}}});
    document.getElementById("statsBarLabel").textContent={calories:"Calories par repas",score:"Score eco.",nb:"Nb aliments",proteines:"Proteines"}[crit]||"";
    if (statsBarInst) statsBarInst.destroy();
    statsBarInst = new Chart(document.getElementById("statsBar").getContext("2d"),{type:"bar",data:{labels:labels,datasets:[{data:values,backgroundColor:bC,borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:"rgba(180,180,180,0.15)"},ticks:{font:{size:10},color:"#9ca3af"}},x:{grid:{display:false},ticks:{font:{size:10},color:"#9ca3af",autoSkip:false,maxRotation:30}}}}});
}

/* === RECOMMANDATIONS IA === */
function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

async function genRecommandationsIA(totaux, alimList, moment, saison, score) {
    var rcP=document.getElementById("recosPanel"), rcList=document.getElementById("recosList"), ctxInfo=document.getElementById("contextInfo");
    rcP.style.display="block";
    rcList.innerHTML="<div style=\"display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f0f7ff;border-radius:12px;color:#1a4a7a;font-size:12px;border:1px solid #b0d0f0;\">"
        +"<div style=\"width:16px;height:16px;border:2px solid #60a5fa;border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;\"></div>"
        +"<span>Gemini AI analyse votre repas...</span></div>"
        +"    ";
    ctxInfo.textContent="Analyse IA . "+moment.label+" . "+saison.label;
    var payload={aliments:alimList,totaux:totaux,moment:moment.key,saison:saison.key,score_eco:score};
    try {
        var response=await fetch("../../../api/recommandation_ia.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)});
        if (!response.ok) throw new Error("HTTP "+response.status);
        var data=await response.json();
        if (data.error) throw new Error(data.error);
        var html="";
        if (data.bilan) html+="<div style=\"padding:10px 14px;border-radius:12px;margin-bottom:8px;background:#e8f0e9;color:#1a372f;font-size:12px;font-weight:600;border:1px solid #1a372f30;\">"+escapeHtml(data.bilan)+"</div>";
        if (data.problemes&&data.problemes.length>0) data.problemes.forEach(function(p){html+="<div class=\"alerte-item alerte-erreur\"><span>&#9888;&#65039;</span><span>"+escapeHtml(p)+"</span></div>";});
        if (data.recommandations&&data.recommandations.length>0) data.recommandations.forEach(function(r){html+="<div class=\"reco-item\"><span style=\"font-size:15px;flex-shrink:0;\">"+(r.emoji||"&#128161;")+"</span><div><strong style=\"display:block;margin-bottom:2px;\">"+escapeHtml(r.titre||"")+"</strong>"+escapeHtml(r.texte||"")+"</div></div>";});
        if (data.conseil_saison&&data.conseil_saison.texte) html+="<div class=\"alerte-item alerte-warning\"><span>"+(data.conseil_saison.emoji||"&#127807;")+"</span><span>"+escapeHtml(data.conseil_saison.texte)+"</span></div>";
        if (data.note_eco) html+="<div class=\"reco-item\" style=\"background:#e8f0e9;color:#1a372f;border-color:#1a372f30;\"><span>&#127757;</span><span>"+escapeHtml(data.note_eco)+"</span></div>";
        html+="<p style=\"font-size:10px;color:#9ca3af;margin-top:6px;text-align:right;\">&#10024; Gemini 2.0 Flash</p>";
        rcList.innerHTML=html;
    } catch(err) {
        console.warn("IA indisponible:",err.message);
        rcList.innerHTML="<div class=\"alerte-item alerte-warning\"><span>&#9888;</span><span>Analyse IA indisponible. Verifiez votre cle API.</span></div>";
    }
}

/* ================================================================
   CULTURE DU JOUR — Visuel 3 (panneau lateral)
   ================================================================ */

var cultureCharge = false;
var cultureData   = null;

function toggleCulture() { showTab('culture'); }

function showTab(tab) {
    var vA = document.getElementById('voletAliments');
    var vC = document.getElementById('voletCulture');
    var tA = document.getElementById('tabAliments');
    var tC = document.getElementById('tabCulture');
    if (tab === 'culture') {
        vA.style.display = 'none';
        vC.style.display = 'block';
        var dark = document.body.classList.contains('dark');
        var actif = dark ? '#60a5fa' : 'var(--vert)';
        var inactifBg = dark ? 'var(--bg-card)' : 'white';
        var inactifCol = dark ? 'var(--text-muted)' : 'var(--vert)';
        tA.style.background = inactifBg;  tA.style.color = inactifCol; tA.style.borderColor = '#d0c8be';
        tC.style.background = actif; tC.style.color = 'white'; tC.style.borderColor = actif;
        if (!cultureCharge) chargerCulture();
    } else {
        vC.style.display = 'none';
        vA.style.display = 'block';
        var dark2 = document.body.classList.contains('dark');
        var actif2 = dark2 ? '#60a5fa' : 'var(--vert)';
        var inactifBg2 = dark2 ? 'var(--bg-card)' : 'white';
        var inactifCol2 = dark2 ? 'var(--text-muted)' : 'var(--vert)';
        tC.style.background = inactifBg2; tC.style.color = inactifCol2; tC.style.borderColor = '#d0c8be';
        tA.style.background = actif2; tA.style.color = 'white'; tA.style.borderColor = actif2;
    }
}

function chargerCulture() {
    document.getElementById('cultureLoading').style.display = 'block';
    document.getElementById('cultureContent').style.display = 'none';
    fetch('../../../api/culture_ia.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            cultureData = data; cultureCharge = true;
            afficherCulture(data);
            var labels = {'mythe':'Mythe','proverbe':'Proverbe','fait_historique':'Histoire','chiffre':'Chiffre','etude_scientifique':'Etude'};
            var lbl = document.getElementById('cultureTypeLabel');
            if (lbl) lbl.textContent = '- ' + (labels[data.type] || data.type);
        })
        .catch(function() { afficherCultureErreur(); });
}

function afficherCulture(d) {
    document.getElementById('cultureLoading').style.display = 'none';
    var typeConf = {
        'mythe':              {bg:'#FAEEDA',border:'#FAC775',tc:'#633806',icon:'fa-ghost'},
        'proverbe':           {bg:'#EAF3DE',border:'#97C459',tc:'#27500A',icon:'fa-quote-left'},
        'fait_historique':    {bg:'#E6F1FB',border:'#85B7EB',tc:'#0C447C',icon:'fa-landmark'},
        'chiffre':            {bg:'#EEEDFE',border:'#AFA9EC',tc:'#3C3489',icon:'fa-chart-bar'},
        'etude_scientifique': {bg:'#E1F5EE',border:'#5DCAA5',tc:'#085041',icon:'fa-microscope'},
    };
    var cf = typeConf[d.type] || typeConf['mythe'];
    var sentConf = {
        'inspirant': {bg:'#EAF3DE',tc:'#27500A',bar:'#639922'},
        'alarmant':  {bg:'#FCEBEB',tc:'#791F1F',bar:'#E24B4A'},
        'surprenant':{bg:'#FAEEDA',tc:'#633806',bar:'#EF9F27'},
        'neutre':    {bg:'#E6F1FB',tc:'#0C447C',bar:'#378ADD'},
    };
    var ton = (d.sentiment && d.sentiment.ton) ? d.sentiment.ton : 'neutre';
    var sc  = sentConf[ton] || sentConf['neutre'];
    var pct = (d.sentiment && d.sentiment.score) ? Math.min(100, Math.round(d.sentiment.score)) : 70;
    var typeLbl = {'mythe':'Mythe','proverbe':'Proverbe','fait_historique':'Fait historique','chiffre':'Chiffre surprenant','etude_scientifique':'Etude scientifique'}[d.type] || d.type;
    var tonLbl  = {'inspirant':'Inspirant','alarmant':'Alarmant','surprenant':'Surprenant','neutre':'Neutre'}[ton] || 'Neutre';

    var chiffreHTML = '';
    if (d.chiffre_cle && d.chiffre_cle !== 'null' && d.chiffre_cle !== null) {
        chiffreHTML = '<div style="text-align:center;padding:10px;background:'+cf.bg+';border-radius:10px;margin-bottom:10px;border:0.5px solid '+cf.border+';">'
                    + '<p style="font-size:26px;font-weight:700;color:'+cf.tc+';font-family:\'Cormorant Garamond\',serif;margin:0;">'+escH(d.chiffre_cle)+'</p>'
                    + '</div>';
    }

    var html =
        '<div style="background:'+cf.bg+';border-radius:12px 12px 0 0;padding:11px 13px;border:0.5px solid '+cf.border+';border-bottom:none;">'
      +   '<div style="display:flex;align-items:center;justify-content:space-between;">'
      +     '<div style="display:flex;align-items:center;gap:8px;">'
      +       '<div style="width:28px;height:28px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
      +         '<i class="fas '+cf.icon+'" style="color:'+cf.tc+';font-size:12px;"></i>'
      +       '</div>'
      +       '<p style="font-size:12px;font-weight:700;color:'+cf.tc+';margin:0;">'+typeLbl+'</p>'
      +     '</div>'
      +     '<button onclick="chargerCulture()" style="background:none;border:0.5px solid '+cf.border+';cursor:pointer;font-size:11px;color:'+cf.tc+';padding:4px 8px;border-radius:6px;">'
      +       '<i class="fas fa-sync-alt" style="font-size:10px;"></i> Nouveau'
      +     '</button>'
      +   '</div>'
      + '</div>'
      + '<div style="background:white;border:0.5px solid '+cf.border+';border-top:none;border-radius:0 0 12px 12px;padding:12px 13px;margin-bottom:10px;">'
      +   chiffreHTML
      +   '<p style="font-size:13px;font-weight:700;color:var(--vert);margin:0 0 7px;line-height:1.4;">'+escH(d.titre)+'</p>'
      +   '<p style="font-size:11px;color:#6b7280;line-height:1.6;margin:0 0 8px;">'+escH(d.resume)+'</p>';

    if (d.detail && d.detail.length > 10) {
        html += '<button onclick="toggleDetailCulture(this)" data-detail="'+escA(d.detail)+'" data-source="'+escA(d.source||'')+'"'
             +  ' style="font-size:11px;padding:4px 10px;border:0.5px solid #d0c8be;border-radius:8px;background:transparent;color:var(--vert);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">'
             +  '<i class="fas fa-book-open" style="font-size:10px;"></i> Lire la suite</button>'
             +  '<div class="detailExpand" style="display:none;margin-top:8px;padding:10px;background:var(--sable);border-radius:8px;font-size:11px;color:#6b7280;line-height:1.7;"></div>';
    }
    html += '</div>';

    html += '<div style="background:white;border:0.5px solid #ede8e0;border-radius:10px;padding:10px 12px;margin-bottom:8px;">'
          +   '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">'
          +     '<p style="font-size:10px;font-weight:700;color:var(--vert);margin:0;text-transform:uppercase;letter-spacing:.04em;">Analyse sentimentale</p>'
          +     '<span style="font-size:10px;padding:2px 8px;border-radius:99px;background:'+sc.bg+';color:'+sc.tc+';font-weight:600;">'+tonLbl+' '+pct+'%</span>'
          +   '</div>'
          +   '<div style="height:5px;border-radius:3px;background:#ede8e0;overflow:hidden;">'
          +     '<div style="width:'+pct+'%;height:100%;border-radius:3px;background:'+sc.bar+';"></div>'
          +   '</div>'
          + '</div>';

    if (d.defi) {
        html += '<div style="background:#EAF3DE;border-radius:10px;padding:10px 12px;display:flex;align-items:flex-start;gap:8px;">'
              +   '<i class="fas fa-trophy" style="color:#27500A;font-size:14px;flex-shrink:0;margin-top:2px;"></i>'
              +   '<div>'
              +     '<p style="font-size:11px;font-weight:700;color:#27500A;margin:0;">Defi du jour</p>'
              +     '<p style="font-size:11px;color:#3B6D11;margin:3px 0 0;line-height:1.5;">'+escH(d.defi)+'</p>'
              +   '</div>'
              + '</div>';
    }

    document.getElementById('cultureContent').innerHTML = html;
    document.getElementById('cultureContent').style.display = 'block';
}

function toggleDetailCulture(btn) {
    var detail = btn.nextElementSibling;
    var isOpen = detail.style.display === 'block';
    if (!isOpen) {
        var src = btn.dataset.source ? '<br><span style="font-size:10px;opacity:.7;">Source : ' + escH(btn.dataset.source) + '</span>' : '';
        detail.innerHTML = escH(btn.dataset.detail) + src;
    }
    detail.style.display = isOpen ? 'none' : 'block';
    btn.innerHTML = isOpen
        ? '<i class="fas fa-book-open" style="font-size:10px;"></i> Lire la suite'
        : '<i class="fas fa-chevron-up" style="font-size:10px;"></i> Reduire';
}

function afficherCultureErreur() {
    document.getElementById('cultureLoading').style.display = 'none';
    document.getElementById('cultureContent').innerHTML =
        '<div style="padding:14px;background:#faeaea;border-radius:10px;color:#8a2020;font-size:12px;text-align:center;">'
      + '<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>'
      + 'Culture IA indisponible. Verifiez votre cle API dans culture_ia.php'
      + '</div>';
    document.getElementById('cultureContent').style.display = 'block';
}

function escH(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escA(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

</script>

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
