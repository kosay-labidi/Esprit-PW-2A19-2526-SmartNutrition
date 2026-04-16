<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');

$error = "";
$challengeC = new ChallengeController();

if (
    isset($_POST["titre"]) && isset($_POST["description"]) && isset($_POST["type"]) && isset($_POST["objectif"]) && isset($_POST["valeur_cible"]) && isset($_POST["date_debut"]) && isset($_POST["date_fin"]) && isset($_POST["statut"]) && isset($_POST["streak_icon"])
) {
    if (
        !empty($_POST["titre"]) && !empty($_POST["description"]) && !empty($_POST["type"]) && !empty($_POST["objectif"]) && !empty($_POST["valeur_cible"]) && !empty($_POST["date_debut"]) && !empty($_POST["date_fin"]) && !empty($_POST["statut"]) && !empty($_POST["streak_icon"])
    ) {
        $image = isset($_POST["image"]) ? $_POST["image"] : "";
        $challenge = new Challenge(
            null, // id
            $_POST['titre'],
            $_POST['description'],
            $_POST['type'],
            $_POST['objectif'],
            (int)$_POST['valeur_cible'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['statut'],
            $_POST['streak_icon'],
            $image
        );
        $challengeC->addChallenge($challenge);
        
        // Si c'est une requête AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Défi créé avec succès !']);
            exit;
        }

        header('Location: listChallenges.php');
        exit;
    } else {
        $error = "Missing information";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <title>Esprit Challenge | Add</title>
  
    <!-- ========== All CSS files linkup ========= -->
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../css/admin.css" />
    <link rel="stylesheet" href="../../css/challenges-admin.css" />
</head>
<body>
    <!-- ======== Preloader =========== -->
    <div id="preloader">
      <div class="spinner"></div>
    </div>
    <!-- ======== Preloader =========== -->

    <!-- ======== sidebar-nav start =========== -->
    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.html">
          <img src="images/logoEspritBook.png" alt="logo" width="40%" height="70%" />
        </a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item nav-item-has-children">
            <a
              href="#0"
              data-bs-toggle="collapse"
              data-bs-target="#ddmenu_1"
              aria-controls="ddmenu_1"
              aria-expanded="false"
              aria-label="Toggle navigation"
            >
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M8.74999 18.3333C12.2376 18.3333 15.1364 15.8128 15.7244 12.4941C15.8448 11.8143 15.2737 11.25 14.5833 11.25H9.99999C9.30966 11.25 8.74999 10.6903 8.74999 10V5.41666C8.74999 4.7263 8.18563 4.15512 7.50586 4.27556C4.18711 4.86357 1.66666 7.76243 1.66666 11.25C1.66666 15.162 4.83797 18.3333 8.74999 18.3333Z"
                    fill="red" />
                  <path
                    d="M17.0833 10C17.7737 10 18.3432 9.43708 18.2408 8.75433C17.7005 5.14918 14.8508 2.29947 11.2457 1.75912C10.5629 1.6568 10 2.2263 10 2.91665V9.16666C10 9.62691 10.3731 10 10.8333 10H17.0833Z"
                    fill="red" />
                </svg>
              </span>
              <span class="text">Dashboard</span>
            </a>
            <ul id="ddmenu_1" class="collapse show dropdown-nav">
              <li>
                <a href="index.html" class="active"> Challenge Store </a>
              </li>
            </ul>
          </li>
           <li class="nav-item nav-item-has-children">
            <a
              href="#0"
              class="collapsed"
              data-bs-toggle="collapse"
              data-bs-target="#ddmenu_5"
              aria-controls="ddmenu_5"
              aria-expanded="false"
              aria-label="Toggle navigation"
            >
             <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" 
                     xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M4.16666 3.33335C4.16666 2.41288 4.91285 1.66669 5.83332 1.66669H14.1667C15.0872 1.66669 15.8333 2.41288 15.8333 3.33335V16.6667C15.8333 17.5872 15.0872 18.3334 14.1667 18.3334H5.83332C4.91285 18.3334 4.16666 17.5872 4.16666 16.6667V3.33335ZM6.04166 5.00002C6.04166 5.3452 6.32148 5.62502 6.66666 5.62502H13.3333C13.6785 5.62502 13.9583 5.3452 13.9583 5.00002C13.9583 4.65485 13.6785 4.37502 13.3333 4.37502H6.66666C6.32148 4.37502 6.04166 4.65485 6.04166 5.00002ZM6.66666 6.87502C6.32148 6.87502 6.04166 7.15485 6.04166 7.50002C6.04166 7.8452 6.32148 8.12502 6.66666 8.12502H13.3333C13.6785 8.12502 13.9583 7.8452 13.9583 7.50002C13.9583 7.15485 13.6785 6.87502 13.3333 6.87502H6.66666ZM6.04166 10C6.04166 10.3452 6.32148 10.625 6.66666 10.625H9.99999C10.3452 10.625 10.625 10.3452 10.625 10C10.625 9.65485 10.3452 9.37502 9.99999 9.37502H6.66666C6.32148 9.37502 6.04166 9.65485 6.04166 10ZM9.99999 16.6667C10.9205 16.6667 11.6667 15.9205 11.6667 15C11.6667 14.0795 10.9205 13.3334 9.99999 13.3334C9.07949 13.3334 8.33332 14.0795 8.33332 15C8.33332 15.9205 9.07949 16.6667 9.99999 16.6667Z"
                    fill="red" />
                </svg>
              </span>
              <span class="text"> Challenge Management </span>
            </a>
            <ul id="ddmenu_5" class="collapse dropdown-nav">
              <li>
                <a href="listChallenges.php"> Challenge List</a>
              </li>
              <li>
                <a href="addChallenge.php"> ADD</a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>
 <main class="main-wrapper">
      <!-- ========== header start ========== -->
      <header class="header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-5 col-md-5 col-6">
              <div class="header-left d-flex align-items-center">
                <div class="menu-toggle-btn mr-15">
                  <button id="menu-toggle" class="main-btn danger-btn btn-hover">
                    <i class="lni lni-chevron-left me-2"></i> Menu
                  </button>
                </div>
                <div class="header-search d-none d-md-flex">
                  <form action="#">
                    <input type="text" placeholder="Search..." />
                    <button><i class="lni lni-search-alt"></i></button>
                  </form>
                </div>
              </div>
            </div>
            <div class="col-lg-7 col-md-7 col-6">
              <div class="header-right">
                <div class="profile-box ml-15">
                  <button class="dropdown-toggle bg-transparent border-0" type="button" id="profile"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-info">
                      <div class="info">
                        <div class="image">
                          <img src="assets/images/profile/profile-image.png" alt="" />
                        </div>
                        <div>
                          <h6 class="fw-500">Challenge Store</h6>
                          <p>Admin</p>
                        </div>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>
      <!-- ========== header end ========== -->

      <!-- ========== section start ========== -->
      <section class="section">
        <div class="container-fluid">
          <!-- ========== title-wrapper start ========== -->
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title">
                  <h2>Add new challenge page</h2>
                </div>
              </div>
              <div class="col-md-6">
                <div class="breadcrumb-wrapper">
                  <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item">
                        <a href="#0">Dashboard</a>
                      </li>
                      <li class="breadcrumb-item active" aria-current="page">
                       Add
                      </li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
          </div>
       <div class="content">
    
    <!-- Form Card -->
    <div class="container mt-4">
      <?php if (!empty($error)) { echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; } ?>
      
        <form action="" method="POST">
          <!-- Title -->
          <div class="mb-3">
            <label for="titre" class="form-label">Title</label>
            <input type="text" class="form-control" id="titre" name="titre" placeholder="Enter challenge title" required>
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" placeholder="Enter challenge description" required></textarea>
          </div>

          <!-- Type -->
          <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type" required>
              <option value="Quotidien">Daily</option>
              <option value="Hebdomadaire">Weekly</option>
              <option value="Mensuel">Monthly</option>
            </select>
          </div>

          <!-- Objectif -->
          <div class="mb-3">
            <label for="objectif" class="form-label">Objective</label>
            <input type="text" class="form-control" id="objectif" name="objectif" placeholder="Enter objective" required>
          </div>

          <!-- Valeur Cible -->
          <div class="mb-3">
            <label for="valeur_cible" class="form-label">Target Value</label>
            <input type="number" class="form-control" id="valeur_cible" name="valeur_cible" placeholder="Enter target value" required>
          </div>

          <!-- Date Début -->
          <div class="mb-3">
            <label for="date_debut" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
          </div>

          <!-- Date Fin -->
          <div class="mb-3">
            <label for="date_fin" class="form-label">End Date</label>
            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
          </div>

          <!-- Status -->
          <div class="mb-3">
            <label for="statut" class="form-label">Status</label>
            <select class="form-select" id="statut" name="statut" required>
              <option value="En cours">In Progress</option>
              <option value="Terminé">Completed</option>
              <option value="A venir">Upcoming</option>
            </select>
          </div>

          <!-- Streak Icon -->
          <div class="mb-3">
            <label for="streak_icon" class="form-label">Streak Icon</label>
            <input type="text" class="form-control" id="streak_icon" name="streak_icon" placeholder="Enter streak icon class (e.g., lni-fire)" required>
          </div>

          <!-- Image -->
          <div class="mb-3">
            <label for="image" class="form-label">Image URL</label>
            <input type="text" class="form-control" id="image" name="image" placeholder="Enter image URL" required>
          </div>

          <!-- Submit -->
          <div class="text-center">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Add Challenge
            </button>
          </div>
    </form>
    </div>
  </div>
        </div>
      </section>
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-6 order-last order-md-first">
              <div class="copyright text-center text-md-start">
                <p class="text-sm">
                  Designed and Developed by Esprit Student 
                </p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="terms d-flex justify-content-center justify-content-md-end">
                <a href="#0" class="text-sm">Term & Conditions</a>
                <a href="#0" class="text-sm ml-15">Privacy & Policy</a>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/admin.js"></script>
</body>
</html>
<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');

$error = "";
$challengeC = new ChallengeController();

if (
    isset($_POST["titre"]) && isset($_POST["description"]) && isset($_POST["type"]) && isset($_POST["objectif"]) && 
    isset($_POST["valeur_cible"]) && isset($_POST["date_debut"]) && isset($_POST["date_fin"]) && 
    isset($_POST["statut"]) && isset($_POST["streak_icon"]) && isset($_POST["image"])
) {
    if (
        !empty($_POST["titre"]) && !empty($_POST["description"]) && !empty($_POST["type"]) && !empty($_POST["objectif"]) && 
        !empty($_POST["valeur_cible"]) && !empty($_POST["date_debut"]) && !empty($_POST["date_fin"]) && 
        !empty($_POST["statut"]) && !empty($_POST["streak_icon"]) && !empty($_POST["image"])
    ) {
        $challenge = new Challenge(
            null, 
            $_POST['titre'],
            $_POST['description'],
            $_POST['type'],
            $_POST['objectif'],
            (int)$_POST['valeur_cible'],
            $_POST['date_debut'],
            $_POST['date_fin'],
            $_POST['statut'],
            $_POST['streak_icon'],
            $_POST['image']
        );
        $challengeC->addChallenge($challenge);
        header('Location: listChallenges.php');
        exit;
    } else {
        $error = "Informations manquantes";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Esprit Challenge | Ajouter</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
</head>
<body>
    <main class="main-wrapper">
        <section class="section">
            <div class="container-fluid">
                <div class="title-wrapper pt-30">
                    <h2>Ajouter un nouveau défi</h2>
                </div>
                <div class="container mt-4">
                    <?php if (!empty($error)) { echo '<div class="alert alert-danger">'.$error.'</div>'; } ?>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="titre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="Quotidien">Quotidien</option>
                                <option value="Hebdomadaire">Hebdomadaire</option>
                                <option value="Mensuel">Mensuel</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Objectif</label>
                            <input type="text" class="form-control" name="objectif" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valeur Cible</label>
                            <input type="number" class="form-control" name="valeur_cible" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Début</label>
                            <input type="date" class="form-control" name="date_debut" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Fin</label>
                            <input type="date" class="form-control" name="date_fin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut" required>
                                <option value="En cours">En cours</option>
                                <option value="Terminé">Terminé</option>
                                <option value="A venir">A venir</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icône (Classe CSS)</label>
                            <input type="text" class="form-control" name="streak_icon" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Image</label>
                            <input type="text" class="form-control" name="image" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter le défi</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>