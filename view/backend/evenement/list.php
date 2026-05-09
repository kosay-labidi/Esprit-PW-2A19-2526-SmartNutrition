<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/EvenementController.php';
require_once __DIR__ . '/../../../Model/Evenement.php';

$evenementC = new EvenementController();
$error = "";
$successMsg = "";

// AJAX : retourner tous les événements filtrés en JSON (pour events-admin.html)
if (isset($_GET['action']) && $_GET['action'] === 'getall') {
    header('Content-Type: application/json');
    $type   = trim($_GET['type']   ?? '');
    $sort   = trim($_GET['sort']   ?? 'date ASC');
    $search = trim($_GET['search'] ?? '');
    $allowed = ['date ASC', 'date DESC', 'titre ASC', 'titre DESC'];
    if (!in_array($sort, $allowed)) $sort = 'date ASC';
    $db  = config::getConnexion();
    $conditions = [];
    $params     = [];
    if ($type !== '') {
        $conditions[] = "type = :type";
        $params[':type'] = $type;
    }
    if ($search !== '') {
        $conditions[] = "(titre LIKE :search1 OR description LIKE :search2)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql   = "SELECT * FROM evenement $where ORDER BY $sort";
    $req   = $db->prepare($sql);
    $req->execute($params);
    echo json_encode($req->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// AJAX : retourner les statistiques réelles en JSON (pour events-admin.html)
if (isset($_GET['action']) && $_GET['action'] === 'getstats') {
    header('Content-Type: application/json');
    echo json_encode($evenementC->getStats());
    exit();
}

// AJAX : retourner les données d'un événement (JSON)
if (isset($_GET['action']) && $_GET['action'] === 'getdata' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $data = $evenementC->showEvenement((int)$_GET['id']);
    echo json_encode($data ? $data : []);
    exit();
}

// POST : Ajouter un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!empty($_POST['titre']) && !empty($_POST['date']) && !empty($_POST['heure']) && !empty($_POST['type'])) {
        try {
            $date = new DateTime($_POST['date']);
            $evenement = new Evenement(null, $_POST['titre'], $_POST['description'] ?? '', $date, $_POST['heure'], $_POST['type']);
            $evenementC->addEvenement($evenement);
            header("Location: list.php?success=added");
            exit();
        } catch (Exception $e) { $error = "Erreur : " . $e->getMessage(); }
    } else { $error = "Tous les champs obligatoires doivent être remplis."; }
}

// POST : Modifier un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    if (!empty($_POST['titre']) && !empty($_POST['date']) && !empty($_POST['heure']) && !empty($_POST['type']) && $id) {
        try {
            $date = new DateTime($_POST['date']);
            $evenement = new Evenement(null, $_POST['titre'], $_POST['description'] ?? '', $date, $_POST['heure'], $_POST['type']);
            $evenementC->updateEvenement($evenement, $id);
            header("Location: list.php?success=updated");
            exit();
        } catch (Exception $e) { $error = "Erreur : " . $e->getMessage(); }
    } else { $error = "Tous les champs obligatoires doivent être remplis."; }
}

// Message de succès via GET
if (isset($_GET['success'])) {
    $msgs = ['added' => '✅ Événement ajouté avec succès !', 'updated' => '✅ Événement mis à jour avec succès !', 'deleted' => '✅ Événement supprimé avec succès !'];
    $successMsg = $msgs[$_GET['success']] ?? '';
}

$list = $evenementC->listEvenements();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Événements - GaiaLumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --green:#1F3D2B; --sand:#F2E8CF; --violet:#5B3E96; --blue:#3A86C4;
            --bg:#0a1a10; --surface:#0f2318; --text:#F2E8CF; --muted:#a8b8a0;
            --card-bg:rgba(15,35,24,0.72); --glass:rgba(31,61,43,0.45);
            --shadow:0 8px 32px rgba(0,0,0,0.4); --radius:18px;
            --tr:0.4s cubic-bezier(.4,0,.2,1);
        }
        [data-theme="light"] {
            --green:#2d5a3f; --bg:#F2E8CF; --surface:#ffffff; --text:#1F3D2B;
            --muted:#5a6b5a; --card-bg:rgba(255,255,255,0.9);
            --glass:rgba(45,90,63,0.1); --shadow:0 8px 32px rgba(0,0,0,0.1);
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--bg);font-family:'Lato',sans-serif;min-height:100vh;transition:all var(--tr);color:var(--text);}
        h1,h2,h3,h4,h5,h6,.navbar-brand,.header-section h2{font-family:'Cormorant Garamond',serif;font-weight:700;}
        
        /* Navbar améliorée */
        .navbar-custom{background:var(--green);backdrop-filter:blur(10px);box-shadow:var(--shadow);padding:1rem 0;}
        .navbar-brand{font-size:1.8rem;font-weight:700;letter-spacing:1px;color:var(--sand)!important;transition:all var(--tr);}
        .navbar-brand i{font-size:1.8rem;margin-right:10px;color:var(--violet);transition:all var(--tr);}
        .navbar-brand:hover{transform:scale(1.05);}
        
        /* Header section */
        .header-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:40px;flex-wrap:wrap;gap:20px;}
        .header-section h2{color:var(--text);font-weight:800;margin:0;display:flex;align-items:center;gap:15px;font-size:2rem;}
        .header-section h2 i{font-size:2.2rem;color:var(--violet);}
        
        /* Boutons */
        .btn-add{background:linear-gradient(135deg, var(--violet), var(--blue));color:white;padding:14px 32px;border-radius:50px;font-weight:600;transition:all var(--tr);border:none;display:inline-flex;align-items:center;gap:10px;font-family:'Lato',sans-serif;cursor:pointer;box-shadow:0 4px 15px rgba(91,62,150,0.3);}
        .btn-add:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(91,62,150,0.5);}
        .btn-dashboard{background:var(--glass);border:1px solid var(--sand);color:var(--sand);padding:14px 32px;border-radius:50px;font-weight:600;transition:all var(--tr);display:inline-flex;align-items:center;gap:10px;text-decoration:none;}
        .btn-dashboard:hover{background:var(--violet);color:var(--sand);transform:translateY(-3px);border-color:var(--violet);}
        
        /* Compteur */
        .counter-badge{background:var(--glass);border-radius:50px;padding:6px 18px;font-size:.9rem;color:var(--violet);font-weight:600;margin-left:12px;}
        
        /* Tableau */
        .table-container{background:var(--card-bg);backdrop-filter:blur(12px);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);border:1px solid rgba(91,62,150,0.1);}
        .table-custom{margin-bottom:0;width:100%;}
        .table-custom thead{background:var(--green);}
        .table-custom thead th{padding:18px 15px;font-weight:700;font-size:.9rem;border:none;color:var(--sand);text-transform:uppercase;letter-spacing:0.5px;}
        .table-custom tbody tr{transition:all 0.3s ease;border-bottom:1px solid rgba(91,62,150,0.1);}
        .table-custom tbody tr:hover{background:rgba(91,62,150,0.15);transform:scale(1.01);}
        .table-custom tbody td{padding:16px 15px;vertical-align:middle;color:var(--text);font-size:.95rem;}
        
        /* Badges de type améliorés */
        .badge-type{padding:6px 14px;border-radius:20px;font-weight:600;font-size:.8rem;display:inline-flex;align-items:center;gap:6px;}
        .badge-repas{background:rgba(230,126,34,0.2);color:#e67e22;border:1px solid rgba(230,126,34,0.3);}
        .badge-sport{background:rgba(46,204,113,0.2);color:#2ecc71;border:1px solid rgba(46,204,113,0.3);}
        .badge-medical{background:rgba(241,196,15,0.2);color:#f1c40f;border:1px solid rgba(241,196,15,0.3);}
        .badge-atelier{background:rgba(155,89,182,0.2);color:#9b59b6;border:1px solid rgba(155,89,182,0.3);}
        
        /* Boutons d'action */
        .btn-action{border-radius:8px;padding:8px 12px;margin:0 4px;transition:all 0.3s ease;border:none;cursor:pointer;}
        .btn-action:hover{transform:translateY(-3px);}
        .btn-info{background:rgba(58,134,196,0.15);color:var(--blue);}
        .btn-info:hover{background:var(--blue);color:white;box-shadow:0 4px 12px rgba(58,134,196,0.4);}
        .btn-warning{background:rgba(91,62,150,0.15);color:var(--violet);}
        .btn-warning:hover{background:var(--violet);color:white;box-shadow:0 4px 12px rgba(91,62,150,0.4);}
        .btn-danger{background:rgba(231,76,60,0.15);color:#e74c3c;}
        .btn-danger:hover{background:#e74c3c;color:white;box-shadow:0 4px 12px rgba(231,76,60,0.4);}
        
        /* État vide */
        .empty-row td{text-align:center;padding:60px 20px!important;color:var(--muted);}
        
        /* Theme toggle */
        .theme-toggle{background:var(--glass);border:1px solid var(--sand);border-radius:50px;padding:8px 20px;color:var(--sand);cursor:pointer;transition:all var(--tr);font-weight:500;}
        .theme-toggle:hover{background:var(--violet);border-color:var(--violet);transform:scale(1.05);}
        
        /* Footer */
        .footer{background:var(--surface);color:var(--muted);padding:25px 0;margin-top:60px;text-align:center;border-top:1px solid var(--glass);}
        .button-group{display:flex;gap:15px;flex-wrap:wrap;}
        
        /* Alertes améliorées */
        .alert-custom{border-radius:12px;border:none;padding:15px 20px;margin-bottom:25px;}
        .alert-success-custom{background:rgba(46,204,113,0.15);border-left:4px solid #2ecc71;color:#2ecc71;}
        .alert-danger-custom{background:rgba(231,76,60,0.15);border-left:4px solid #e74c3c;color:#e74c3c;}
        
        /* MODALS améliorées */
        .modal-content{background:var(--surface);border:1px solid rgba(91,62,150,0.2);border-radius:var(--radius);color:var(--text);}
        .modal-header-custom{background:linear-gradient(135deg, var(--green), var(--green));padding:20px 25px;border-radius:var(--radius) var(--radius) 0 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(91,62,150,0.2);}
        .modal-header-custom h5{color:var(--sand);margin:0;font-family:'Cormorant Garamond',serif;font-size:1.5rem;display:flex;align-items:center;gap:12px;}
        .modal-header-custom h5 i{color:var(--violet);}
        .btn-close-custom{background:none;border:none;color:var(--sand);font-size:1.3rem;cursor:pointer;opacity:.7;transition:all 0.2s;}
        .btn-close-custom:hover{opacity:1;transform:rotate(90deg);}
        .modal-body{padding:30px;}
        .modal .form-label{font-weight:600;color:var(--text);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
        .modal .form-label i{color:var(--violet);}
        .modal .form-control,.modal .form-select{border-radius:12px;border:1px solid rgba(91,62,150,0.2);padding:12px 16px;transition:all var(--tr);background-color:var(--card-bg);color:var(--text);}
        .modal .form-control:focus,.modal .form-select:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(91,62,150,0.2);outline:none;}
        .btn-modal-submit{padding:12px 32px;border-radius:50px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:10px;transition:all var(--tr);}
        .btn-modal-submit.add-btn,.btn-modal-submit.edit-btn{background:linear-gradient(135deg, var(--violet), var(--blue));color:white;}
        .btn-modal-submit.add-btn:hover,.btn-modal-submit.edit-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(91,62,150,0.5);}
        .btn-modal-cancel{background:var(--glass);border:1px solid var(--sand);color:var(--sand);padding:12px 28px;border-radius:50px;font-weight:600;cursor:pointer;transition:all var(--tr);}
        .btn-modal-cancel:hover{background:var(--violet);border-color:var(--violet);transform:translateY(-2px);}
        .modal-footer-custom{padding:20px 30px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid rgba(91,62,150,0.1);}
        
        /* Vue détails */
        .show-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;}
        .show-info-item{background:var(--card-bg);border-radius:14px;padding:18px 20px;border-left:4px solid var(--violet);transition:all var(--tr);}
        .show-info-item:hover{transform:translateX(5px);}
        .show-info-label{font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:8px;}
        .show-info-label i{color:var(--violet);}
        .show-info-value{font-size:1.1rem;font-weight:700;color:var(--text);margin:0;}
        .show-desc-box{background:var(--card-bg);border-radius:14px;padding:20px;border:1px solid rgba(91,62,150,0.2);line-height:1.7;}
        .modal-spinner{display:flex;justify-content:center;align-items:center;padding:60px;color:var(--violet);font-size:2rem;}
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-section{flex-direction:column;align-items:stretch;}
            .button-group{justify-content:stretch;}
            .btn-add,.btn-dashboard{justify-content:center;}
            .show-info-grid{grid-template-columns:1fr;}
            .table-custom thead th,.table-custom tbody td{padding:12px 8px;font-size:.8rem;}
        }
    </style>
</head>
<body data-theme="dark">

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="../admin.html">
            <i class="fas fa-leaf"></i> GaiaLumen
        </a>
        <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun me-1"></i> Mode clair
            </button>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <?php if ($successMsg): ?>
    <div class="alert alert-success-custom alert-custom alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= $successMsg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger-custom alert-custom alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="header-section">
        <h2>
            <i class="fas fa-calendar-alt"></i>
            Liste des Événements
            <span class="counter-badge">
                <i class="fas fa-chart-simple me-1"></i><?= $list ? $list->rowCount() : 0 ?> événements
            </span>
        </h2>
        <div class="button-group">
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus-circle"></i> Ajouter un événement
            </button>
            <a href="../admin.html" class="btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="table-custom table">
            <thead>
                <tr>
                    <th><i class="fas fa-hashtag me-1"></i> ID</th>
                    <th><i class="fas fa-heading me-1"></i> Titre</th>
                    <th><i class="fas fa-calendar-day me-1"></i> Date</th>
                    <th><i class="fas fa-clock me-1"></i> Heure</th>
                    <th><i class="fas fa-tag me-1"></i> Type</th>
                    <th><i class="fas fa-cogs me-1"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hasEvents = false;
                foreach ($list as $evenement):
                    $hasEvents = true;
                    $badgeClass = 'badge-type';
                    switch($evenement['type']) {
                        case 'repas':   $badgeClass .= ' badge-repas';   break;
                        case 'sport':   $badgeClass .= ' badge-sport';   break;
                        case 'medical': $badgeClass .= ' badge-medical'; break;
                        case 'atelier': $badgeClass .= ' badge-atelier'; break;
                        default:        $badgeClass .= ' bg-secondary';
                    }
                    $icons = ['repas'=>'🍲','sport'=>'🧘','medical'=>'🥗','atelier'=>'🌱'];
                    $typeIcon = $icons[$evenement['type']] ?? '📌';
                ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($evenement['id_event']) ?></strong></td>
                    <td>
                        <a href="../participation/list.php?id_event=<?= $evenement['id_event'] ?>"
                           title="Voir les participations de cet événement"
                           style="color:var(--violet);font-weight:600;text-decoration:none;transition:all 0.3s;"
                           onmouseover="this.style.textDecoration='underline'"
                           onmouseout="this.style.textDecoration='none'">
                            <?= htmlspecialchars($evenement['titre']) ?>
                            <i class="fas fa-users ms-1" style="font-size:0.8rem;opacity:0.7;"></i>
                        </a>
                    </td>
                    <td><?= date('d/m/Y', strtotime($evenement['date'])) ?></td>
                    <td><?= htmlspecialchars($evenement['heure']) ?></td>
                    <td><span class="<?= $badgeClass ?>"><?= $typeIcon ?> <?= ucfirst(htmlspecialchars($evenement['type'])) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-action btn-info" title="Voir"
                                onclick="openShowModal(<?= $evenement['id_event'] ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-action btn-warning" title="Modifier"
                                onclick="openEditModal(<?= $evenement['id_event'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="delete.php?id=<?= $evenement['id_event'] ?>"
                           onclick="return confirm('⚠️ Supprimer cet événement ? Cette action est irréversible.')"
                           class="btn btn-sm btn-action btn-danger" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$hasEvents): ?>
                <tr class="empty-row">
                    <td colspan="6">
                        <i class="fas fa-calendar-times fa-3x mb-3 d-block" style="color:var(--muted);"></i>
                        <h4 style="margin-bottom:10px;">Aucun événement trouvé</h4>
                        <p style="margin-bottom:20px;">Commencez par créer votre premier événement</p>
                        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus-circle"></i> Créer le premier événement
                        </button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL AJOUTER -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5><i class="fas fa-calendar-plus"></i> Ajouter un Événement</h5>
                <button class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="list.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i> Titre *</label>
                        <input type="text" name="titre" class="form-control" placeholder="Ex: Atelier Bien-être Naturel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Décrivez votre événement..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-calendar-day"></i> Date *</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Heure *</label>
                            <input type="time" name="heure" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-layer-group"></i> Type d'événement *</label>
                        <select name="type" class="form-select" required>
                            <option value="" disabled selected>-- Sélectionner un type --</option>
                            <option value="repas">🍲 Repas / Cuisine</option>
                            <option value="sport">🧘 Sport / Yoga</option>
                            <option value="medical">🥗 Consultation Nutrition</option>
                            <option value="atelier">🌱 Atelier Pratique</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Annuler</button>
                    <button type="submit" class="btn-modal-submit add-btn"><i class="fas fa-save"></i> Créer l'événement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL MODIFIER -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5><i class="fas fa-edit"></i> Modifier l'Événement <span id="editModalIdLabel" style="opacity:.7;font-size:1rem;"></span></h5>
                <button class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div id="editSpinner" class="modal-spinner"><i class="fas fa-spinner fa-spin"></i></div>
            <form method="POST" action="list.php" id="editForm" style="display:none;">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-tag"></i> Titre *</label>
                        <input type="text" name="titre" id="editTitre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-calendar-day"></i> Date *</label>
                            <input type="date" name="date" id="editDate" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Heure *</label>
                            <input type="time" name="heure" id="editHeure" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-layer-group"></i> Type d'événement *</label>
                        <select name="type" id="editType" class="form-select" required>
                            <option value="repas">🍲 Repas / Cuisine</option>
                            <option value="sport">🧘 Sport / Yoga</option>
                            <option value="medical">🥗 Consultation Nutrition</option>
                            <option value="atelier">🌱 Atelier Pratique</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Annuler</button>
                    <button type="submit" class="btn-modal-submit edit-btn"><i class="fas fa-save"></i> Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL AFFICHER -->
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5><i class="fas fa-info-circle"></i> Détails de l'Événement</h5>
                <button class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div id="showSpinner" class="modal-spinner"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="showContent" style="display:none;">
                <div class="modal-body">
                    <div class="show-info-grid">
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-heading"></i> TITRE</div>
                            <p class="show-info-value" id="showTitre">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-tag"></i> TYPE</div>
                            <p class="show-info-value" id="showType">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-calendar-day"></i> DATE</div>
                            <p class="show-info-value" id="showDate">—</p>
                        </div>
                        <div class="show-info-item">
                            <div class="show-info-label"><i class="fas fa-clock"></i> HEURE</div>
                            <p class="show-info-value" id="showHeure">—</p>
                        </div>
                    </div>
                    <div class="show-desc-label"><i class="fas fa-align-left"></i> Description</div>
                    <div class="show-desc-box" id="showDescription">—</div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i> Fermer</button>
                    <button type="button" class="btn-modal-submit edit-btn" id="showToEditBtn">
                        <i class="fas fa-edit"></i> Modifier cet événement
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; 2026 GaiaLumen - Héritage de Gaia. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Theme
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) { document.body.setAttribute('data-theme', savedTheme); updateBtnText(savedTheme); }
    themeToggle.addEventListener('click', () => {
        const t = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
        updateBtnText(t);
    });
    function updateBtnText(t) {
        themeToggle.innerHTML = t === 'dark'
            ? '<i class="fas fa-sun me-1"></i> Mode clair'
            : '<i class="fas fa-moon me-1"></i> Mode sombre';
    }

    // Helpers
    const typeIcons  = {repas:'🍲',sport:'🧘',medical:'🥗',atelier:'🌱'};
    const typeBadges = {repas:'badge-type badge-repas',sport:'badge-type badge-sport',medical:'badge-type badge-medical',atelier:'badge-type badge-atelier'};
    function fmtDate(d) { if (!d) return '—'; const [y,m,dd]=d.split('-'); return `${dd}/${m}/${y}`; }

    // MODAL AFFICHER
    let currentShowId = null;
    function openShowModal(id) {
        currentShowId = id;
        const modal = new bootstrap.Modal(document.getElementById('showModal'));
        document.getElementById('showSpinner').style.display = 'flex';
        document.getElementById('showContent').style.display = 'none';
        modal.show();
        fetch(`list.php?action=getdata&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.id_event) { document.getElementById('showSpinner').innerHTML='<span style="color:#ff6b7a">Événement introuvable.</span>'; return; }
                document.getElementById('showTitre').textContent = data.titre||'—';
                const icon=typeIcons[data.type]||'📌', cls=typeBadges[data.type]||'badge-type bg-secondary';
                document.getElementById('showType').innerHTML=`<span class="${cls}">${icon} ${data.type}</span>`;
                document.getElementById('showDate').textContent = fmtDate(data.date);
                document.getElementById('showHeure').textContent = data.heure||'—';
                document.getElementById('showDescription').innerHTML = data.description
                    ? data.description.replace(/\n/g,'<br>')
                    : '<span style="color:var(--muted)"><i class="fas fa-comment-slash me-2"></i>Aucune description.</span>';
                document.getElementById('showSpinner').style.display='none';
                document.getElementById('showContent').style.display='block';
            })
            .catch(()=>{ document.getElementById('showSpinner').innerHTML='<span style="color:#ff6b7a">Erreur de chargement.</span>'; });
    }

    document.getElementById('showToEditBtn').addEventListener('click', () => {
        bootstrap.Modal.getInstance(document.getElementById('showModal')).hide();
        setTimeout(() => openEditModal(currentShowId), 350);
    });

    // MODAL MODIFIER
    function openEditModal(id) {
        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        document.getElementById('editSpinner').style.display='flex';
        document.getElementById('editForm').style.display='none';
        document.getElementById('editModalIdLabel').textContent=`#${id}`;
        modal.show();
        fetch(`list.php?action=getdata&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data || !data.id_event) { document.getElementById('editSpinner').innerHTML='<span style="color:#ff6b7a">Événement introuvable.</span>'; return; }
                document.getElementById('editId').value          = data.id_event;
                document.getElementById('editTitre').value       = data.titre||'';
                document.getElementById('editDescription').value = data.description||'';
                document.getElementById('editDate').value        = data.date||'';
                document.getElementById('editHeure').value       = data.heure||'';
                document.getElementById('editType').value        = data.type||'repas';
                document.getElementById('editSpinner').style.display='none';
                document.getElementById('editForm').style.display='block';
            })
            .catch(()=>{ document.getElementById('editSpinner').innerHTML='<span style="color:#ff6b7a">Erreur de chargement.</span>'; });
    }
</script>
</body>
</html>