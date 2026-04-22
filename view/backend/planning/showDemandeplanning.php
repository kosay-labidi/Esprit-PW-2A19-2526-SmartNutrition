<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
require_once(__DIR__ . '/../../../controller/Sportsommeil.controller.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listDemandeplanning.php');
    exit;
}

$idDemande = (int) $_GET['id'];
$demandeC  = new DemandeplanningController();
$ssCtrl    = new SportSommeilController();
$d         = $demandeC->getDemandeById($idDemande);

if (!$d) {
    header('Location: listDemandeplanning.php');
    exit;
}

$ss      = $ssCtrl->getSportSommeilByDemande($idDemande);
$message = '';
$erreur  = '';

// ── Mode JSON pour le Side Drawer (AJAX) ─────────────────────
if (isset($_GET['json']) && $_GET['json'] == '1') {
    header('Content-Type: application/json; charset=utf-8');

    $planningLignesJson = $demandeC->afficherPlanningByDemande($idDemande);
    $nbLignesJson       = $ssCtrl->countPlanningByDemande($idDemande);

    $joursFrJson = [
        'Monday'=>'Lundi','Tuesday'=>'Mardi','Wednesday'=>'Mercredi',
        'Thursday'=>'Jeudi','Friday'=>'Vendredi','Saturday'=>'Samedi','Sunday'=>'Dimanche'
    ];
    $planningGroupeJson = [];
    foreach ($planningLignesJson as $ligne) {
        $date = $ligne['date'];
        if (!isset($planningGroupeJson[$date])) {
            $planningGroupeJson[$date] = [
                'jourFr'  => $joursFrJson[date('l', strtotime($date))] ?? date('l', strtotime($date)),
                'dateAff' => date('d/m', strtotime($date)),
                'repas'   => [], 'sport' => [], 'sommeil' => [],
            ];
        }
        $planningGroupeJson[$date][$ligne['type_activite']][] = $ligne['description'];
    }
    $pl = array_values($planningGroupeJson);

    echo json_encode([
        'success'      => true,
        'demande'      => $d,
        'sportsommeil' => $ss,
        'planning'     => $pl,
        'nb_lignes'    => $nbLignesJson,
        'stats'        => [
            'nb_jours'   => count($planningGroupeJson),
            'nb_repas'   => array_sum(array_map(fn($j) => count($j['repas']),   $planningGroupeJson)),
            'nb_sport'   => array_sum(array_map(fn($j) => count($j['sport']),   $planningGroupeJson)),
            'nb_sommeil' => array_sum(array_map(fn($j) => count($j['sommeil']), $planningGroupeJson)),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Traitement POST : génération du planning ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generer') {
    if (!$ss) {
        $erreur = "Impossible de générer : l'étape 2 (Sport & Sommeil) n'a pas encore été remplie pour cette demande.";
    } else {
        try {
            $ssCtrl->genererPlanningComplet($idDemande);
            $message = "Planning généré avec succès ! " . $ssCtrl->countPlanningByDemande($idDemande) . " lignes créées.";
        } catch (RuntimeException $e) {
            $erreur = "Erreur lors de la génération : " . $e->getMessage();
        }
    }
}

// ── Charger le planning via jointure (méthode du workshop) ────
// CORRECTION : on utilise afficherPlanningByDemande() qui fait la jointure
// planning INNER JOIN demandeplanning, exactement comme le PDF
$planningLignes = $demandeC->afficherPlanningByDemande($idDemande);
$nbLignes       = $ssCtrl->countPlanningByDemande($idDemande);

// Regrouper par date pour le tableau calendrier
$planningGroupe = [];
foreach ($planningLignes as $ligne) {
    $date = $ligne['date'];
    if (!isset($planningGroupe[$date])) {
        $planningGroupe[$date] = ['repas' => [], 'sport' => [], 'sommeil' => []];
    }
    $planningGroupe[$date][$ligne['type_activite']][] = $ligne['description'];
}

// CORRECTION : tableau de traduction des jours en français
$joursFr = [
    'Monday'    => 'Lundi',
    'Tuesday'   => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday'  => 'Jeudi',
    'Friday'    => 'Vendredi',
    'Saturday'  => 'Samedi',
    'Sunday'    => 'Dimanche',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaLumen | Planning #<?= $idDemande ?></title>
  <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../css/admin.css"/>
  <link rel="stylesheet" href="../css/challenges-admin.css"/>
  <style>
    .planning-table-wrap { overflow-x: auto; margin-top: 24px; }
    .planning-table {
      width: 100%; border-collapse: separate; border-spacing: 0;
      font-size: .82rem; min-width: 700px;
    }
    .planning-table th {
      padding: 10px 14px; text-align: center; font-weight: 700;
      font-size: .75rem; text-transform: uppercase; letter-spacing: .06em;
      border-bottom: 2px solid rgba(91,62,150,.3);
    }
    .planning-table th.col-type { background: rgba(91,62,150,.12); color: var(--violet); width: 120px; }
    .planning-table th.col-jour { background: rgba(91,62,150,.06); color: var(--muted); min-width: 150px; }
    .planning-table td {
      padding: 10px 14px; vertical-align: top;
      border-bottom: 1px solid rgba(91,62,150,.08); line-height: 1.5;
    }
    .planning-table tr:last-child td { border-bottom: none; }
    .planning-table td.cell-type {
      font-weight: 700; font-size: .75rem; text-transform: uppercase;
      letter-spacing: .05em; text-align: center; vertical-align: middle; white-space: nowrap;
    }
    .row-repas   td.cell-type { color: #27ae60; }
    .row-sport   td.cell-type { color: #f39c12; }
    .row-sommeil td.cell-type { color: #3498db; }
    .row-repas   td { background: rgba(46,204,113,.04); }
    .row-sport   td { background: rgba(241,196,15,.04); }
    .row-sommeil td { background: rgba(52,152,219,.04); }
    .cell-icon { font-size: 1rem; display: block; margin-bottom: 2px; }
    .cell-desc { color: var(--text); font-size: .82rem; }
    .jour-header { font-weight: 700; color: var(--text); display: block; }
    .jour-sub    { font-size: .7rem; color: var(--muted); }
    .btn-confirm {
      display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px;
      background: linear-gradient(135deg, #27ae60, #2ecc71);
      border: none; border-radius: 12px; color: #fff; font-weight: 700;
      font-size: .95rem; cursor: pointer; transition: transform .2s, box-shadow .3s;
    }
    .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(46,204,113,.4); }
    .btn-regen {
      display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px;
      background: rgba(52,152,219,.12); border: 1.5px solid rgba(52,152,219,.3);
      border-radius: 12px; color: #3498db; font-weight: 600; font-size: .88rem;
      cursor: pointer; transition: all .2s;
    }
    .btn-regen:hover { background: rgba(52,152,219,.2); }
    .alert-success {
      background: rgba(46,204,113,.12); border: 1px solid rgba(46,204,113,.3);
      border-left: 4px solid #27ae60; border-radius: 10px;
      padding: 12px 18px; color: #1a7a44; font-weight: 600; margin-bottom: 20px;
    }
    .alert-danger {
      background: rgba(231,76,60,.12); border: 1px solid rgba(231,76,60,.3);
      border-left: 4px solid #e74c3c; border-radius: 10px;
      padding: 12px 18px; color: #a93226; font-weight: 600; margin-bottom: 20px;
    }
    .alert-warning {
      background: rgba(243,156,18,.12); border: 1px solid rgba(243,156,18,.3);
      border-left: 4px solid #f39c12; border-radius: 10px;
      padding: 12px 18px; color: #7d6608; margin-bottom: 20px;
    }
    .stat-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .stat-pill {
      display: flex; align-items: center; gap: 6px;
      background: rgba(91,62,150,.08); border: 1px solid rgba(91,62,150,.18);
      border-radius: 20px; padding: 6px 14px; font-size: .82rem;
    }
    .stat-pill strong { color: var(--violet); }
    .empty-planning {
      text-align: center; padding: 50px 20px;
      border: 2px dashed rgba(91,62,150,.2); border-radius: 12px; color: var(--muted);
    }
    .empty-planning .icon { font-size: 2.5rem; margin-bottom: 12px; opacity: .5; }
  </style>
</head>
<body>

<aside class="sidebar-nav-wrapper">
  <div class="navbar-logo">
    <a href="../admin.html"><strong>GaiaLumen</strong></a>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li class="nav-item nav-item-has-children">
        <a href="#0" data-bs-toggle="collapse" data-bs-target="#ddmenu_planning" aria-expanded="true">
          <span class="icon"><i class="lni lni-calendar"></i></span>
          <span class="text">Planning</span>
        </a>
        <ul id="ddmenu_planning" class="collapse show dropdown-nav">
          <li><a href="listDemandeplanning.php">Liste des demandes</a></li>
        </ul>
      </li>
    </ul>
  </nav>
</aside>
<div class="overlay"></div>

<main class="main-wrapper">
  <header class="header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-6">
          <div class="header-left d-flex align-items-center">
            <div class="menu-toggle-btn mr-15">
              <button id="menu-toggle" class="main-btn danger-btn btn-hover">
                <i class="lni lni-chevron-left me-2"></i> Menu
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>📅 Planning #<?= $idDemande ?></h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-end">
                <li class="breadcrumb-item"><a href="../admin.html">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listDemandeplanning.php">Planning</a></li>
                <li class="breadcrumb-item active">Détail #<?= $idDemande ?></li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <?php if ($message): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($erreur): ?>
        <div class="alert-danger">⛔ <?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <div class="admin-layout">

        <!-- Bloc 1 : Infos demande -->
        <div class="table-panel">
          <div class="form-panel-header">
            <h3>📋 Demande de planning</h3>
            <a href="listDemandeplanning.php" class="btn-reset">← Retour</a>
          </div>
          <div class="table-container-admin" style="margin-top:16px;">
            <table class="data-table">
              <tbody>
                <tr><th style="background:rgba(91,62,150,.1); width:180px;">🔥 Calories</th>
                    <td><?= htmlspecialchars($d['calories']) ?> kcal / jour</td></tr>
                <tr><th style="background:rgba(91,62,150,.1);">💰 Budget</th>
                    <td><?= number_format((float)$d['budget'], 2) ?> €
                      <span class="badge badge-type" style="margin-left:6px;"><?= htmlspecialchars($d['type_budget']) ?></span></td></tr>
                <tr><th style="background:rgba(91,62,150,.1);">⏱️ Durée</th>
                    <td><?= htmlspecialchars($d['duree']) ?>
                      <span class="badge badge-type" style="margin-left:6px;"><?= htmlspecialchars($d['type_duree']) ?></span></td></tr>
                <tr><th style="background:rgba(91,62,150,.1);">👤 Utilisateur</th>
                    <td>#<?= htmlspecialchars($d['id_utilisateur']) ?></td></tr>
                <tr><th style="background:rgba(91,62,150,.1);">📅 Date demande</th>
                    <td><?= htmlspecialchars($d['date_demande'] ?? '—') ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Bloc 2 : Sport & Sommeil -->
        <?php if ($ss): ?>
        <div class="table-panel" style="margin-top:20px;">
          <div class="form-panel-header">
            <h3>🏃 Sport & Sommeil</h3>
          </div>
          <div class="table-container-admin" style="margin-top:16px;">
            <table class="data-table">
              <tbody>
                <tr><th style="background:rgba(241,196,15,.08); width:180px;">🏋️ Activité</th>
                    <td><?= htmlspecialchars($ss['activite_sportive']) ?></td></tr>
                <tr><th style="background:rgba(241,196,15,.08);">⏱️ Durée/semaine</th>
                    <td><?= htmlspecialchars($ss['duree_sport_hebdo']) ?> min
                    <small style="color:var(--muted);"> (<?= round($ss['duree_sport_hebdo']/7) ?> min/jour)</small></td></tr>
                <tr><th style="background:rgba(52,152,219,.08);">🌙 Coucher</th>
                    <td><?= htmlspecialchars(substr($ss['heure_coucher'],0,5)) ?></td></tr>
                <tr><th style="background:rgba(52,152,219,.08);">☀️ Réveil</th>
                    <td><?= htmlspecialchars(substr($ss['heure_reveil'],0,5)) ?></td></tr>
                <tr><th style="background:rgba(52,152,219,.08);">😴 Qualité</th>
                    <td><?= htmlspecialchars($ss['qualite_sommeil']) ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <?php else: ?>
        <div class="alert-warning" style="margin-top:20px;">
          ⚠️ L'étape 2 (Sport & Sommeil) n'a pas encore été remplie. Le planning ne peut pas être généré.
        </div>
        <?php endif; ?>

        <!-- Bloc 3 : Bouton génération -->
        <div class="table-panel" style="margin-top:20px; padding:24px;">
          <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <div>
              <h3 style="margin:0 0 6px;">📅 Tableau de planning</h3>
              <?php if ($nbLignes > 0): ?>
                <p style="color:var(--muted); font-size:.85rem; margin:0;">
                  <?= $nbLignes ?> lignes générées — <?= count($planningGroupe) ?> jour(s) planifiés
                </p>
              <?php else: ?>
                <p style="color:var(--muted); font-size:.85rem; margin:0;">
                  Aucun planning généré. Cliquez sur "Confirmer" pour générer.
                </p>
              <?php endif; ?>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
              <?php if ($ss): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="generer"/>
                  <?php if ($nbLignes > 0): ?>
                    <button type="submit" class="btn-regen"
                            onclick="return confirm('Régénérer le planning ? Cela supprimera les lignes existantes.')">
                      🔄 Régénérer
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn-confirm">
                      ✅ Confirmer et générer le planning
                    </button>
                  <?php endif; ?>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($nbLignes > 0): ?>
          <div class="stat-row" style="margin-top:18px;">
            <div class="stat-pill">📆 <strong><?= count($planningGroupe) ?></strong> jours</div>
            <div class="stat-pill">🍽️ <strong><?= array_sum(array_map(fn($j) => count($j['repas']), $planningGroupe)) ?></strong> repas</div>
            <div class="stat-pill">🏃 <strong><?= array_sum(array_map(fn($j) => count($j['sport']), $planningGroupe)) ?></strong> séances sport</div>
            <div class="stat-pill">🌙 <strong><?= array_sum(array_map(fn($j) => count($j['sommeil']), $planningGroupe)) ?></strong> nuits</div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Bloc 4 : Tableau calendrier -->
        <?php if (!empty($planningGroupe)): ?>
        <div class="table-panel" style="margin-top:20px;">
          <div class="form-panel-header">
            <h3>📊 Tableau hebdomadaire — Planning complet</h3>
            <span style="color:var(--muted); font-size:.82rem;">Jointure planning ↔ demandeplanning</span>
          </div>
          <div class="planning-table-wrap">
            <table class="planning-table">
              <thead>
                <tr>
                  <th class="col-type">Activité</th>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <th class="col-jour">
                      <!-- CORRECTION : jours affichés en français -->
                      <span class="jour-header"><?= $joursFr[date('l', strtotime($date))] ?? date('l', strtotime($date)) ?></span>
                      <span class="jour-sub"><?= date('d/m', strtotime($date)) ?></span>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <!-- Petit-déjeuner -->
                <tr class="row-repas">
                  <td class="cell-type"><span class="cell-icon">🍳</span>Petit-déj</td>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <td><span class="cell-desc"><?= htmlspecialchars($activites['repas'][0] ?? '—') ?></span></td>
                  <?php endforeach; ?>
                </tr>
                <!-- Déjeuner -->
                <tr class="row-repas">
                  <td class="cell-type"><span class="cell-icon">🍽️</span>Déjeuner</td>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <td><span class="cell-desc"><?= htmlspecialchars($activites['repas'][1] ?? '—') ?></span></td>
                  <?php endforeach; ?>
                </tr>
                <!-- Dîner -->
                <tr class="row-repas">
                  <td class="cell-type"><span class="cell-icon">🌮</span>Dîner</td>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <td><span class="cell-desc"><?= htmlspecialchars($activites['repas'][2] ?? '—') ?></span></td>
                  <?php endforeach; ?>
                </tr>
                <!-- Sport -->
                <tr class="row-sport">
                  <td class="cell-type"><span class="cell-icon">🏃</span>Sport</td>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <td><span class="cell-desc"><?= htmlspecialchars($activites['sport'][0] ?? '—') ?></span></td>
                  <?php endforeach; ?>
                </tr>
                <!-- Sommeil -->
                <tr class="row-sommeil">
                  <td class="cell-type"><span class="cell-icon">🌙</span>Sommeil</td>
                  <?php foreach ($planningGroupe as $date => $activites): ?>
                    <td><span class="cell-desc"><?= htmlspecialchars($activites['sommeil'][0] ?? '—') ?></span></td>
                  <?php endforeach; ?>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <?php else: ?>
        <div class="empty-planning" style="margin-top:20px;">
          <div class="icon">📅</div>
          <h3>Aucun planning généré</h3>
          <p>Remplissez les deux formulaires puis cliquez sur "Confirmer et générer le planning".</p>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="container-fluid">
      <p class="text-sm">GaiaLumen – Administration Planning</p>
    </div>
  </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
</body>
</html>