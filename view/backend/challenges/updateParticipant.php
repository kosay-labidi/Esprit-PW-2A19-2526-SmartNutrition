<?php
require_once(__DIR__ . '/../../../controller/participant.controller.php');
require_once(__DIR__ . '/../../../Model/Participant.php');
require_once(__DIR__ . '/../../../controller/challenge.controller.php');

$error = "";
$participantC = new ParticipantController();
$challengeC = new ChallengeController();
$challenges = [];
$participant = null;
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $participant = $participantC->showParticipant($id);
    if (!$participant) {
        if ($isAjax) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Participant introuvable']);
            exit;
        }
        header('Location: showParticipant.php');
        exit;
    }
} else {
    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'ID participant manquant']);
        exit;
    }
    header('Location: showParticipant.php');
    exit;
}

try {
    $challenges = $challengeC->listChallenges();
} catch (Exception $e) {
    $challenges = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_challenge'], $_POST['nom'], $_POST['email'], $_POST['objectif'], $_POST['motivation'], $_POST['action'])) {
        $id_challenge = (int)$_POST['id_challenge'];
        $nom = trim((string)$_POST['nom']);
        $email = trim((string)$_POST['email']);
        $objectif = (int)$_POST['objectif'];
        $motivation = trim((string)$_POST['motivation']);
        $action = trim((string)$_POST['action']);
        $engagement = isset($_POST['engagement']) ? (int)$_POST['engagement'] : 0;
        $notifications = isset($_POST['notifications']) ? (int)$_POST['notifications'] : 0;

        if ($id_challenge <= 0 || $nom === "" || $email === "" || $motivation === "" || $action === "") {
            $error = "Missing information";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email";
        } else {
            $updatedParticipant = new Participant($id_challenge, $nom, $email, $objectif, $motivation, $action, $engagement, $notifications);

            if ($participantC->updateParticipant($updatedParticipant, $id)) {
                if ($isAjax) {
                    if (ob_get_length()) ob_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'id' => $id,
                        'id_challenge' => $id_challenge
                    ]);
                    exit;
                }
                header('Location: showParticipant.php?id_challenge=' . $id_challenge);
                exit;
            }
            $error = "Error: Could not update participant";
        }
    } else {
        $error = "Missing information";
    }

    if ($isAjax) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $error ?: 'Modification impossible']);
        exit;
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
    <title>Esprit Challenge | Update Participant</title>
  
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../css/admin.css" />
    <link rel="stylesheet" href="../../css/challenges-admin.css" />
</head>
<body>
    <div id="preloader">
      <div class="spinner"></div>
    </div>

    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.html">
          <img src="images/logoEspritBook.png" alt="logo" width="40%" height="70%" />
        </a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item nav-item-has-children">
            <a href="#0" data-bs-toggle="collapse" data-bs-target="#ddmenu_1" aria-controls="ddmenu_1" aria-expanded="false">
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8.74999 18.3333C12.2376 18.3333 15.1364 15.8128 15.7244 12.4941C15.8448 11.8143 15.2737 11.25 14.5833 11.25H9.99999C9.30966 11.25 8.74999 10.6903 8.74999 10V5.41666C8.74999 4.7263 8.18563 4.15512 7.50586 4.27556C4.18711 4.86357 1.66666 7.76243 1.66666 11.25C1.66666 15.162 4.83797 18.3333 8.74999 18.3333Z" fill="red" />
                  <path d="M17.0833 10C17.7737 10 18.3432 9.43708 18.2408 8.75433C17.7005 5.14918 14.8508 2.29947 11.2457 1.75912C10.5629 1.6568 10 2.2263 10 2.91665V9.16666C10 9.62691 10.3731 10 10.8333 10H17.0833Z" fill="red" />
                </svg>
              </span>
              <span class="text">Dashboard</span>
            </a>
          </li>
          <li class="nav-item nav-item-has-children">
            <a href="#0" class="collapsed" data-bs-toggle="collapse" data-bs-target="#ddmenu_5" aria-controls="ddmenu_5" aria-expanded="false">
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4.16666 3.33335C4.16666 2.41288 4.91285 1.66669 5.83332 1.66669H14.1667C15.0872 1.66669 15.8333 2.41288 15.8333 3.33335V16.6667C15.8333 17.5872 15.0872 18.3334 14.1667 18.3334H5.83332C4.91285 18.3334 4.16666 17.5872 4.16666 16.6667V3.33335ZM6.04166 5.00002C6.04166 5.3452 6.32148 5.62502 6.66666 5.62502H13.3333C13.6785 5.62502 13.9583 5.3452 13.9583 5.00002C13.9583 4.65485 13.6785 4.37502 13.3333 4.37502H6.66666C6.32148 4.37502 6.04166 4.65485 6.04166 5.00002ZM6.66666 6.87502C6.32148 6.87502 6.04166 7.15485 6.04166 7.50002C6.04166 7.8452 6.32148 8.12502 6.66666 8.12502H13.3333C13.6785 8.12502 13.9583 7.8452 13.9583 7.50002C13.9583 7.15485 13.6785 6.87502 13.3333 6.87502H6.66666ZM6.04166 10C6.04166 10.3452 6.32148 10.625 6.66666 10.625H9.99999C10.3452 10.625 10.625 10.3452 10.625 10C10.625 9.65485 10.3452 9.37502 9.99999 9.37502H6.66666C6.32148 9.37502 6.04166 9.65485 6.04166 10ZM9.99999 16.6667C10.9205 16.6667 11.6667 15.9205 11.6667 15C11.6667 14.0795 10.9205 13.3334 9.99999 13.3334C9.07949 13.3334 8.33332 14.0795 8.33332 15C8.33332 15.9205 9.07949 16.6667 9.99999 16.6667Z" fill="red" />
                </svg>
              </span>
              <span class="text"> Challenge Management </span>
            </a>
            <ul id="ddmenu_5" class="collapse show dropdown-nav">
              <li><a href="listChallenges.php"> Challenge List</a></li>
              <li><a href="addChallenge.php"> ADD</a></li>
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
            <div class="col-lg-5 col-md-5 col-6">
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
                <div class="title">
                  <h2>Update Participant</h2>
                </div>
              </div>
            </div>
          </div>

          <div class="form-elements-wrapper">
            <div class="row">
              <div class="col-lg-12">
                <div class="card-style mb-30">
                  <h6 class="mb-25">Edit Participant Information</h6>
                  
                  <?php if ($error !== "") { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                  <?php } ?>

                  <form action="updateParticipant.php?id=<?php echo $id; ?>" method="POST">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="select-style-1">
                          <label>Challenge</label>
                          <div class="select-position">
                            <select name="id_challenge" required>
                              <option value="">Select challenge</option>
                              <?php foreach ($challenges as $c) { ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $participant['id_challenge'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($c['titre']); ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="input-style-1">
                          <label>Full Name</label>
                          <input type="text" name="nom" value="<?php echo htmlspecialchars($participant['nom']); ?>" required />
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="input-style-1">
                          <label>Email</label>
                          <input type="email" name="email" value="<?php echo htmlspecialchars($participant['email']); ?>" required />
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="input-style-1">
                          <label>Goal (%)</label>
                          <input type="number" name="objectif" min="1" max="100" value="<?php echo htmlspecialchars($participant['objectif']); ?>" required />
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="input-style-1">
                          <label>Motivation</label>
                          <textarea name="motivation" rows="3" required><?php echo htmlspecialchars($participant['motivation']); ?></textarea>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="input-style-1">
                          <label>First Action</label>
                          <textarea name="action" rows="3" required><?php echo htmlspecialchars($participant['action']); ?></textarea>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                          <input class="form-check-input" type="checkbox" name="engagement" value="1" id="engagement" <?php echo $participant['engagement'] == 1 ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="engagement">Engagement formel</label>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input" type="checkbox" name="notifications" value="1" id="notifications" <?php echo $participant['notifications'] == 1 ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="notifications">Activer les notifications</label>
                        </div>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between mt-30">
                      <a href="showParticipant.php?id_challenge=<?php echo (int)$participant['id_challenge']; ?>" class="main-btn danger-btn-outline btn-hover">Cancel</a>
                      <button type="submit" class="main-btn primary-btn btn-hover">Update Participant</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/admin.js"></script>
</body>
</html>
