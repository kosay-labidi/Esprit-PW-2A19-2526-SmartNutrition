<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listDemandeplanning.php');
    exit;
}

$demandeC = new DemandeplanningController();
$d = $demandeC->getDemandeById((int)$_GET['id']);

if (!$d) {
    header('Location: listDemandeplanning.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaLumen | Détail Demande</title>
  <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../css/admin.css"/>
  <link rel="stylesheet" href="../css/challenges-admin.css"/>
</head>
<body>

<aside class="sidebar-nav-wrapper">
  <div class="navbar-logo">
    <a href="../admin.html"><strong>GaiaLumen</strong></a>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li class="nav-item nav-item-has-children">
        <a href="#0" data-bs-toggle="collapse" data-bs-target="#ddmenu_planning"
           aria-controls="ddmenu_planning" aria-expanded="true">
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
            <div class="title"><h2>👁️ Détail Demande #<?= $d['id'] ?></h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-end">
                <li class="breadcrumb-item"><a href="../admin.html">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listDemandeplanning.php">Planning</a></li>
                <li class="breadcrumb-item active">Détail</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <div class="admin-layout">
        <div class="table-panel">
          <div class="form-panel-header">
            <h3>📋 Informations de la demande</h3>
            <a href="listDemandeplanning.php" class="btn-reset">← Retour</a>
          </div>

          <div class="table-container-admin" style="margin-top:20px;">
            <table class="data-table">
              <tbody>
                <tr class="animate-in">
                  <th style="width:200px; background:rgba(91,62,150,.1);">🔢 ID Demande</th>
                  <td><?= htmlspecialchars($d['id']) ?></td>
                </tr>
                <tr class="animate-in">
                  <th style="background:rgba(91,62,150,.1);">👤 ID Utilisateur</th>
                  <td><?= htmlspecialchars($d['id_utilisateur']) ?></td>
                </tr>
                <tr class="animate-in">
                  <th style="background:rgba(91,62,150,.1);">🔥 Calories</th>
                  <td><?= htmlspecialchars($d['calories']) ?> kcal / jour</td>
                </tr>
                <tr class="animate-in">
                  <th style="background:rgba(91,62,150,.1);">💰 Budget</th>
                  <td>
                    <?= number_format((float)$d['budget'], 2) ?> €
                    <span class="badge badge-type" style="margin-left:8px;"><?= htmlspecialchars($d['type_budget']) ?></span>
                  </td>
                </tr>
                <tr class="animate-in">
                  <th style="background:rgba(91,62,150,.1);">⏱️ Durée</th>
                  <td>
                    <?= htmlspecialchars($d['duree']) ?>
                    <span class="badge badge-type" style="margin-left:8px;"><?= htmlspecialchars($d['type_duree']) ?></span>
                  </td>
                </tr>
                <tr class="animate-in">
                  <th style="background:rgba(91,62,150,.1);">📅 Date demande</th>
                  <td><?= htmlspecialchars($d['date_demande'] ?? '—') ?></td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
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
