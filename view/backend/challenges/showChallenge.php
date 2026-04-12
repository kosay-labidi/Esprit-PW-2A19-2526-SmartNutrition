<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');

$challengeC = new ChallengeController();

if (isset($_GET['id'])) {
    $challenge = $challengeC->showChallenge($_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <title>Esprit Challenge | View</title>
  
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
                  <h2>Challenge Details</h2>
                </div>
              </div>
              <div class="col-md-6">
                <div class="breadcrumb-wrapper">
                  <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item">
                        <a href="#0">Dashboard</a>
                      </li>
                      <li class="breadcrumb-item">
                        <a href="listChallenges.php">Challenges</a>
                      </li>
                      <li class="breadcrumb-item active" aria-current="page">
                       Details
                      </li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
          </div>
       <div class="content">
    <div class="container mt-4">
        <?php if ($challenge) { ?>
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span class="fs-2 me-3"><?php echo $challenge['streak_icon']; ?></span>
                <h3 class="mb-0"><?php echo $challenge['titre']; ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?php echo $challenge['image']; ?>" alt="Challenge Image" class="img-fluid rounded mb-3">
                    </div>
                    <div class="col-md-8">
                        <p><strong>Description:</strong> <?php echo $challenge['description']; ?></p>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <p><strong>Type:</strong> <?php echo ucfirst($challenge['type']); ?></p>
                                <p><strong>Objective:</strong> <?php echo $challenge['objectif']; ?></p>
                                <p><strong>Target Value:</strong> <?php echo $challenge['valeur_cible']; ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p><strong>Start Date:</strong> <?php echo $challenge['date_debut']; ?></p>
                                <p><strong>End Date:</strong> <?php echo $challenge['date_fin']; ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo $challenge['statut']; ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="updateChallenge.php?id=<?php echo $challenge['id']; ?>" class="btn btn-warning">
                    <i class="lni lni-pencil"></i> Edit
                </a>
                <a href="listChallenges.php" class="btn btn-secondary">
                    <i class="lni lni-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
        <?php } else { echo '<p>Challenge not found.</p>'; } ?>
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
