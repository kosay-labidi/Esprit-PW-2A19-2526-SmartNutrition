<?php
ob_start();
error_reporting(0);
ini_set('display_errors','0');

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

$demandeC = new DemandeplanningController();

// ── EXPORT CSV : téléchargement direct, AVANT tout header JSON ──────────
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    ob_end_clean();
    $statut = isset($_GET['statut']) && in_array($_GET['statut'], ['en_attente','approuve','rejete'], true)
        ? $_GET['statut'] : null;
    $demandeC->exportCSV($statut);
    exit;
}

// ── MODE JSON : appelé par le SPA admin (planning-admin.js) ──────────────
// Détection : header X-Requested-With ou paramètre ?json=1
$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || isset($_GET['json'])
);

if ($isAjax) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        // Action statut (approuver / rejeter)
        if (isset($_GET['action']) && $_GET['action'] === 'statut') {
            $id  = (int)($_GET['id']  ?? 0);
            $val = trim($_GET['val']  ?? '');
            if ($id <= 0 || !in_array($val, ['en_attente','approuve','rejete'], true)) {
                echo json_encode(['success'=>false,'error'=>'Parametres invalides']);
                exit;
            }
            $ok = $demandeC->updateStatut($id, $val);
            // Si approuve → generer le planning
            $nbLignes = 0;
            if ($ok && $val === 'approuve') {
                require_once __DIR__ . '/../../../controller/Sportsommeil.controller.php';
                try {
                    $ssCtrl   = new SportSommeilController();
                    $lignes   = $ssCtrl->genererPlanningComplet($id);
                    $nbLignes = count($lignes);
                } catch (RuntimeException $e) { $nbLignes = 0; }
            }
            echo json_encode([
                'success'  => $ok,
                'statut'   => $val,
                'nb_lignes'=> $nbLignes,
                'message'  => $ok
                    ? ($val==='approuve' && $nbLignes>0 ? "Approuve + $nbLignes lignes generees" : "Statut mis a jour : $val")
                    : 'Erreur (verifiez la migration SQL)',
            ]);
            exit;
        }

        // Action generer (force regeneration)
        if (isset($_GET['action']) && $_GET['action'] === 'generer') {
            $id = (int)($_GET['id'] ?? 0);
            require_once __DIR__ . '/../../../controller/Sportsommeil.controller.php';
            $ssCtrl = new SportSommeilController();
            $lignes = $ssCtrl->genererPlanningComplet($id);
            echo json_encode(['success'=>true,'nb_lignes'=>count($lignes),'message'=>count($lignes).' lignes generees']);
            exit;
        }

        // Action delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete') {
            $id = (int)($_GET['id'] ?? 0);
            $ok = $demandeC->deleteDemande($id);
            echo json_encode(['success'=>$ok]);
            exit;
        }

        // Liste par defaut
        $data = $demandeC->listAllDemandesAvecStats();
        echo json_encode(['success'=>true,'data'=>$data,'total'=>count($data)]);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
    }
    exit;
}

// ── MODE HTML : page backend classique ──────────────────────────────────
$demandes = $demandeC->listAllDemandesAvecStats();

$list        = [];
$idRecherche = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demande'], $_POST['search'])) {
    $idRecherche = (int) $_POST['demande'];
    $list        = $demandeC->afficherPlanningByDemande($idRecherche);
}
ob_end_clean();
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
  <style>
    .badge-statut{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.74rem;font-weight:700;white-space:nowrap}
    .badge-en_attente{background:rgba(243,156,18,.15);border:1px solid rgba(243,156,18,.3);color:#f39c12}
    .badge-approuve{background:rgba(46,204,113,.15);border:1px solid rgba(46,204,113,.3);color:#2ecc71}
    .badge-rejete{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.3);color:#e74c3c}
    .action-btns{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
    .btn-statut{padding:5px 10px;border:none;border-radius:7px;font-size:.76rem;font-weight:700;cursor:pointer;transition:all .2s}
    .btn-approuver{background:rgba(46,204,113,.2);color:#2ecc71;border:1px solid rgba(46,204,113,.3)}
    .btn-approuver:hover{background:rgba(46,204,113,.4)}
    .btn-rejeter{background:rgba(231,76,60,.15);color:#e74c3c;border:1px solid rgba(231,76,60,.3)}
    .btn-rejeter:hover{background:rgba(231,76,60,.3)}
    .btn-regen{background:rgba(52,152,219,.15);color:#3498db;border:1px solid rgba(52,152,219,.3)}
    .btn-regen:hover{background:rgba(52,152,219,.3)}
    #toastMsg{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;font-weight:600;font-size:.88rem;z-index:9999;display:none}
    #toastMsg.ok{background:#1a4731;border:1px solid #2ecc71;color:#2ecc71}
    #toastMsg.err{background:#4a1515;border:1px solid #e74c3c;color:#e74c3c}
  </style>
</head>
<body>

<aside class="sidebar-nav-wrapper">
  <div class="navbar-logo"><a href="../admin.html"><strong>GaiaLumen</strong></a></div>
  <nav class="sidebar-nav">
    <ul>
      <li class="nav-item nav-item-has-children">
        <a href="#0" data-bs-toggle="collapse" data-bs-target="#ddmenu_planning">
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
            <div class="title"><h2>📅 Gestion des Demandes de Planning</h2></div>
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
        <div class="quick-stats-mini" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;margin-bottom:20px">
          <div class="stat-mini">
            <div class="stat-mini-value"><?= count($demandes) ?></div>
            <div class="stat-mini-label">Total demandes</div>
          </div>
          <div class="stat-mini" style="border-color:rgba(243,156,18,.3)">
            <div class="stat-mini-value"><?= count(array_filter($demandes, fn($d) => ($d['statut']??'en_attente')==='en_attente')) ?></div>
            <div class="stat-mini-label">⏳ En attente</div>
          </div>
          <div class="stat-mini" style="border-color:rgba(46,204,113,.3)">
            <div class="stat-mini-value"><?= count(array_filter($demandes, fn($d) => ($d['statut']??'')==='approuve')) ?></div>
            <div class="stat-mini-label">✅ Approuvés</div>
          </div>
          <div class="stat-mini" style="border-color:rgba(231,76,60,.3)">
            <div class="stat-mini-value"><?= count(array_filter($demandes, fn($d) => ($d['statut']??'')==='rejete')) ?></div>
            <div class="stat-mini-label">❌ Rejetés</div>
          </div>
        </div>

        <!-- Tableau principal -->
        <div class="table-panel">
          <div class="form-panel-header">
            <h3>📋 Toutes les demandes</h3>
            <div style="display:flex;gap:10px;align-items:center">
              <input type="text" class="search-input-admin" id="searchInput"
                     placeholder="🔍 Rechercher..." oninput="filterTable()"/>
              <select id="filterStatut" onchange="filterTable()" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(91,62,150,.3);background:var(--card-bg);color:var(--text);font-size:.85rem">
                <option value="">Tous statuts</option>
                <option value="en_attente">⏳ En attente</option>
                <option value="approuve">✅ Approuvés</option>
                <option value="rejete">❌ Rejetés</option>
              </select>
              <button onclick="location.reload()" class="btn-sm" title="Actualiser">🔄</button>
              <button onclick="exportCSV()" class="btn-sm" title="Exporter en CSV"
                style="background:rgba(46,204,113,.15);color:#2ecc71;border:1px solid rgba(46,204,113,.3);padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer">
                ⬇️ Export CSV
              </button>
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
                  <th>Statut</th>
                  <th>Planning</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <?php if (empty($demandes)): ?>
                  <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">Aucune demande.</td></tr>
                <?php else: ?>
                  <?php foreach ($demandes as $d):
                    $statut = $d['statut'] ?? 'en_attente';
                    $badges = ['en_attente'=>'⏳ En attente','approuve'=>'✅ Approuvé','rejete'=>'❌ Rejeté'];
                  ?>
                  <tr class="table-row-animated" data-statut="<?= htmlspecialchars($statut) ?>">
                    <td><strong><?= htmlspecialchars($d['id']) ?></strong></td>
                    <td>👤 <?= htmlspecialchars($d['id_utilisateur']) ?></td>
                    <td><?= htmlspecialchars($d['calories']) ?> kcal</td>
                    <td><?= number_format((float)$d['budget'],2) ?> €
                      <span class="badge badge-type"><?= htmlspecialchars($d['type_budget']) ?></span></td>
                    <td><?= htmlspecialchars($d['duree']) ?>
                      <span class="badge badge-type"><?= htmlspecialchars($d['type_duree']) ?></span></td>
                    <td>
                      <span class="badge-statut badge-<?= htmlspecialchars($statut) ?>">
                        <?= $badges[$statut] ?? $statut ?>
                      </span>
                    </td>
                    <td>
                      <?php if ((int)$d['nb_lignes_planning'] > 0): ?>
                        <span style="color:#2ecc71;font-weight:700"><?= (int)$d['nb_lignes_planning'] ?> lignes</span>
                        <?php if ($d['activite_sport']): ?>
                          <br><small style="color:var(--muted)"><?= htmlspecialchars($d['activite_sport']) ?></small>
                        <?php endif; ?>
                      <?php else: ?>
                        <span style="color:var(--muted)">—</span>
                      <?php endif; ?>
                    </td>
                    <td style="color:var(--muted);font-size:.8rem"><?= htmlspecialchars($d['date_demande'] ?? '—') ?></td>
                    <td>
                      <div class="action-btns">
                        <a href="showDemandeplanning.php?id=<?= $d['id'] ?>" class="btn-icon" title="Voir">👁️</a>
                        <?php if ($statut === 'en_attente'): ?>
                          <button class="btn-statut btn-approuver" onclick="changerStatut(<?= $d['id'] ?>,'approuve',this)">✅ Approuver</button>
                          <button class="btn-statut btn-rejeter"  onclick="changerStatut(<?= $d['id'] ?>,'rejete',this)">❌ Rejeter</button>
                        <?php elseif ($statut === 'approuve'): ?>
                          <button class="btn-statut btn-regen" onclick="regenPlanning(<?= $d['id'] ?>,this)">🔄 Regénérer</button>
                          <button class="btn-statut btn-rejeter" onclick="changerStatut(<?= $d['id'] ?>,'rejete',this)">❌ Rejeter</button>
                        <?php elseif ($statut === 'rejete'): ?>
                          <button class="btn-statut btn-approuver" onclick="changerStatut(<?= $d['id'] ?>,'en_attente',this)">↩️ Remettre</button>
                        <?php endif; ?>
                        <a href="deleteDemandeplanning.php?id=<?= $d['id'] ?>"
                           class="btn-icon btn-danger" title="Supprimer"
                           onclick="return confirm('Supprimer cette demande et tout son planning ?')">🗑️</a>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="table-pagination">
            <div class="pagination-info"><?= count($demandes) ?> demande(s) au total</div>
          </div>
        </div>

        <!-- Recherche par jointure -->
        <div class="table-panel" style="margin-top:20px">
          <div class="form-panel-header">
            <h3>🔗 Voir le planning d'une demande (jointure)</h3>
          </div>
          <form method="POST" action="" style="display:flex;gap:12px;align-items:center;padding:16px;flex-wrap:wrap">
            <select name="demande" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(91,62,150,.3);background:var(--card-bg);color:var(--text)">
              <?php foreach ($demandes as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($idRecherche==$d['id'])?'selected':'' ?>>
                  #<?= $d['id'] ?> — Utilisateur <?= $d['id_utilisateur'] ?> (<?= $d['calories'] ?> kcal)
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="search" class="main-btn primary-btn btn-hover">Afficher le planning</button>
          </form>
          <?php if (!empty($list)): ?>
            <div style="padding:0 16px 16px">
              <table class="data-table">
                <thead>
                  <tr><th>Date</th><th>Activité</th><th>Description</th><th>Calories obj.</th><th>Budget</th><th>Durée</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($list as $l): $icons=['repas'=>'🍽️','sport'=>'🏃','sommeil'=>'🌙']; ?>
                  <tr>
                    <td><?= htmlspecialchars($l['date']) ?></td>
                    <td><?= ($icons[$l['type_activite']]??'').' '.htmlspecialchars($l['type_activite']) ?></td>
                    <td><?= htmlspecialchars($l['description']) ?></td>
                    <td><?= htmlspecialchars($l['objectif_calories']) ?> kcal</td>
                    <td><?= number_format((float)$l['objectif_budget'],2) ?> €</td>
                    <td><?= htmlspecialchars($l['duree']) ?> <?= htmlspecialchars($l['type_duree']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php elseif ($idRecherche !== null): ?>
            <p style="padding:0 16px 16px;color:var(--muted)">Aucun planning généré pour cette demande.</p>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>
  <footer class="footer"><div class="container-fluid"><p class="text-sm">GaiaLumen – Administration Planning</p></div></footer>
</main>

<div id="toastMsg"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/admin.js"></script>
<script>
function exportCSV() {
  const statut = document.getElementById('filterStatut').value;
  const url = 'listDemandeplanning.php?action=export_csv' + (statut ? '&statut=' + encodeURIComponent(statut) : '');
  window.location.href = url;
}

function filterTable() {
  const val    = document.getElementById('searchInput').value.toLowerCase();
  const statut = document.getElementById('filterStatut').value;
  document.querySelectorAll('#tableBody tr').forEach(row => {
    const matchText   = row.textContent.toLowerCase().includes(val);
    const matchStatut = !statut || row.dataset.statut === statut;
    row.style.display = (matchText && matchStatut) ? '' : 'none';
  });
}

function showToast(msg, type) {
  const t = document.getElementById('toastMsg');
  t.textContent = msg;
  t.className = type;
  t.style.display = 'block';
  setTimeout(() => t.style.display='none', 3500);
}

function changerStatut(id, val, btn) {
  if (!confirm('Confirmer : ' + (val==='approuve'?'approuver':(val==='rejete'?'rejeter':'remettre en attente')) + ' la demande #'+id+' ?')) return;
  btn.disabled = true;
  const orig = btn.textContent;
  btn.textContent = '⏳';
  fetch('listDemandeplanning.php?json=1&action=statut&id='+id+'&val='+val, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(res => {
      if (!res.success) throw new Error(res.error || res.message || 'Erreur');
      showToast(res.message || 'Statut mis à jour', 'ok');
      setTimeout(() => location.reload(), 1000);
    })
    .catch(err => { showToast('Erreur : '+err.message, 'err'); btn.disabled=false; btn.textContent=orig; });
}

function regenPlanning(id, btn) {
  if (!confirm('Régénérer le planning #'+id+' ?')) return;
  btn.disabled = true; btn.textContent = '⏳';
  fetch('listDemandeplanning.php?json=1&action=generer&id='+id, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(r=>r.json())
    .then(res => {
      if (!res.success) throw new Error(res.error || 'Erreur');
      showToast(res.message, 'ok');
      setTimeout(() => location.reload(), 1000);
    })
    .catch(err => { showToast('Erreur : '+err.message, 'err'); btn.disabled=false; btn.textContent='🔄 Regénérer'; });
}
</script>
</body>
</html>
