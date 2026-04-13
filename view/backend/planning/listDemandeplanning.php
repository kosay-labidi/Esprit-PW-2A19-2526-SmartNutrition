<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

$demandeC = new DemandeplanningController();
$demandes = $demandeC->listAllDemandes();

// Si c'est une requête AJAX (XMLHttpRequest), on retourne du JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($demandes);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GaiaLumen | Liste Planning</title>
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
          <li><a href="listDemandeplanning.php" class="active">Liste des demandes</a></li>
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
            <div class="title"><h2>📅 Demandes de Planning</h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-end">
                <li class="breadcrumb-item"><a href="../admin.html">Dashboard</a></li>
                <li class="breadcrumb-item active">Planning</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <div class="admin-layout">

        <!-- Stats -->
        <div class="quick-stats-mini">
          <div class="stat-mini">
            <div class="stat-mini-value"><?= count($demandes) ?></div>
            <div class="stat-mini-label">Total demandes</div>
          </div>
          <div class="stat-mini">
            <div class="stat-mini-value">
              <?= count(array_filter($demandes, fn($d) => $d['type_budget'] === 'quotidien')) ?>
            </div>
            <div class="stat-mini-label">Budget quotidien</div>
          </div>
          <div class="stat-mini">
            <div class="stat-mini-value">
              <?= count(array_filter($demandes, fn($d) => $d['type_budget'] === 'hebdomadaire')) ?>
            </div>
            <div class="stat-mini-label">Budget hebdomadaire</div>
          </div>
        </div>

        <!-- Tableau -->
        <div class="table-panel">
          <div class="form-panel-header">
            <h3>📋 Liste des demandes</h3>
            <div style="display:flex; gap:10px; align-items:center;">
              <input type="text" class="search-input-admin" id="searchInput"
                     placeholder="🔍 Rechercher..." oninput="filterTable()"/>
              <button class="btn-sm" onclick="location.reload()" title="Actualiser">🔄</button>
            </div>
          </div>

          <div class="table-container-admin">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#ID</th>
                  <th>Utilisateur</th>
                  <th>Calories</th>
                  <th>Budget</th>
                  <th>Durée</th>
                  <th>Date demande</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <?php if (empty($demandes)): ?>
                  <tr>
                    <td colspan="7" style="text-align:center; color:var(--muted); padding:40px;">
                      Aucune demande trouvée.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($demandes as $d): ?>
                  <tr class="table-row-animated">
                    <td><?= htmlspecialchars($d['id']) ?></td>
                    <td><?= htmlspecialchars($d['id_utilisateur']) ?></td>
                    <td><?= htmlspecialchars($d['calories']) ?> kcal</td>
                    <td>
                      <?= number_format((float)$d['budget'], 2) ?> €
                      <span class="badge badge-type"><?= htmlspecialchars($d['type_budget']) ?></span>
                    </td>
                    <td>
                      <?= htmlspecialchars($d['duree']) ?>
                      <span class="badge badge-type"><?= htmlspecialchars($d['type_duree']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($d['date_demande'] ?? '—') ?></td>
                    <td>
                      <div class="action-btns">
                        <a href="updateDemandeplanning.php?id=<?= $d['id'] ?>"
                           class="btn-icon" title="Modifier">✏️</a>
                        <a href="deleteDemandeplanning.php?id=<?= $d['id'] ?>"
                           class="btn-icon btn-danger"
                           title="Supprimer"
                           onclick="return confirm('Supprimer cette demande ?')">🗑️</a>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="table-pagination">
            <div class="pagination-info">
              <?= count($demandes) ?> demande(s) au total
            </div>
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
<script>
function filterTable() {
  const val = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#tableBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}
</script>
</body>
</html>
