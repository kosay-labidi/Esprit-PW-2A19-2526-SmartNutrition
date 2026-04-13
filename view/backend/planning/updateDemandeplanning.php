<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
require_once(__DIR__ . '/../../../Model/Demandeplanning.php');

$demandeC = new DemandeplanningController();
$error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listDemandeplanning.php');
    exit;
}

$id = (int)$_GET['id'];
$demande = $demandeC->getDemandeById($id);

if (!$demande) {
    header('Location: listDemandeplanning.php');
    exit;
}

if (isset($_POST["calories"]) && isset($_POST["budget"]) && isset($_POST["type_budget"]) && isset($_POST["duree"]) && isset($_POST["type_duree"])) {
    if (!empty($_POST["calories"]) && !empty($_POST["budget"]) && !empty($_POST["type_budget"]) && !empty($_POST["duree"]) && !empty($_POST["type_duree"])) {

        $updated = new Demandeplanning(
            $id,
            (int)$demande['id_utilisateur'],
            (int)$_POST['calories'],
            (float)$_POST['budget'],
            $_POST['type_budget'],
            (int)$_POST['duree'],
            $_POST['type_duree'],
            null
        );

        $demandeC->updateDemande($updated, $id);
        header('Location: listDemandeplanning.php');
        exit;

    } else {
        $error = "Informations manquantes";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaLumen | Modifier Demande</title>
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
            <div class="title"><h2>✏️ Modifier la Demande #<?= $id ?></h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-end">
                <li class="breadcrumb-item"><a href="../admin.html">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="listDemandeplanning.php">Planning</a></li>
                <li class="breadcrumb-item active">Modifier</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <div class="admin-layout">
        <div class="form-panel">
          <div class="form-panel-header">
            <h3>📝 Modifier la demande</h3>
            <a href="listDemandeplanning.php" class="btn-reset">← Retour</a>
          </div>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" action="">

            <div class="form-row-compact">
              <div class="form-group">
                <label class="form-label">🔥 Calories (kcal/jour)</label>
                <input type="number" name="calories" class="form-input"
                       value="<?= htmlspecialchars($_POST['calories'] ?? $demande['calories']) ?>"
                       min="1" required/>
              </div>
              <div class="form-group">
                <label class="form-label">💰 Budget (€)</label>
                <input type="number" name="budget" class="form-input"
                       value="<?= htmlspecialchars($_POST['budget'] ?? $demande['budget']) ?>"
                       min="0.01" step="0.01" required/>
              </div>
              <div class="form-group">
                <label class="form-label">📊 Type budget</label>
                <select name="type_budget" class="form-select" required>
                  <option value="quotidien"    <?= ($demande['type_budget'] === 'quotidien')    ? 'selected' : '' ?>>Quotidien</option>
                  <option value="hebdomadaire" <?= ($demande['type_budget'] === 'hebdomadaire') ? 'selected' : '' ?>>Hebdomadaire</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">⏱️ Durée</label>
                <input type="number" name="duree" class="form-input"
                       value="<?= htmlspecialchars($_POST['duree'] ?? $demande['duree']) ?>"
                       min="1" required/>
              </div>
              <div class="form-group">
                <label class="form-label">📅 Type durée</label>
                <select name="type_duree" class="form-select" required>
                  <option value="jours"    <?= ($demande['type_duree'] === 'jours')    ? 'selected' : '' ?>>Jours</option>
                  <option value="semaines" <?= ($demande['type_duree'] === 'semaines') ? 'selected' : '' ?>>Semaines</option>
                </select>
              </div>
              <div class="form-group form-actions-inline">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn-compact btn-primary">💾 Enregistrer</button>
              </div>
            </div>

          </form>
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
