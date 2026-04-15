<?php
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../config.php';

$userController = new UserController();

// Détecter si c'est une requête AJAX/JSON
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || 
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

/* ════════════════════════════════════════════════
   Mode API JSON (appelé depuis fetch)
════════════════════════════════════════════════ */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($contentType, 'application/json')) {

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($input['id'] ?? 0);
    $nom    = trim($input['nom']    ?? '');
    $prenom = trim($input['prenom'] ?? '');
    $email  = trim($input['email']  ?? '');
    $role   = trim($input['role']   ?? 'utilisateur');

    $errors = [];

    if ($id < 1) { $errors[] = 'ID utilisateur invalide.'; }

    if ($nom === '')               { $errors[] = 'Le nom est requis.'; }
    elseif (preg_match('/\d/', $nom)) { $errors[] = 'Le nom ne doit pas contenir de chiffres.'; }

    if ($prenom === '')               { $errors[] = 'Le prénom est requis.'; }
    elseif (preg_match('/\d/', $prenom)) { $errors[] = 'Le prénom ne doit pas contenir de chiffres.'; }

    if ($email === '')                               { $errors[] = "L'email est requis."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide.'; }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) { $role = 'utilisateur'; }

    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    // Vérifier si l'utilisateur existe
    $existingUser = $userController->getUserById($id);
    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé.']);
        exit();
    }

    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    $userWithEmail = $userController->getUserByEmail($email);
    if ($userWithEmail && $userWithEmail['id_utilisateur'] != $id) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre utilisateur.']);
        exit();
    }

    try {
        $userModel = new User();
        $userModel->setIdUtilisateur($id);
        $userModel->setNom($nom);
        $userModel->setPrenom($prenom);
        $userModel->setEmail($email);
        $userModel->setMdp($existingUser['mdp']);
        $userModel->setRole($role);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }

    if ($userController->updateUser($userModel)) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la mise à jour de l'utilisateur."]);
    }
    exit();
}

/* ════════════════════════════════════════════════
   Mode formulaire HTML (BackOffice)
════════════════════════════════════════════════ */

// Récupération des informations de l'utilisateur à modifier (GET)
if (isset($_GET['id'])) {
    $userData = $userController->getUserById((int)$_GET['id']);
    if (!$userData) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
            exit();
        }
        die("Utilisateur non trouvé");
    }
} else {
    header('Location: showUser.php');
    exit();
}

// Traitement POST pour le mode formulaire classique
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !str_contains($contentType, 'application/json')) {
    $id     = (int)($_POST['id'] ?? 0);
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $role   = trim($_POST['role']   ?? 'utilisateur');

    if ($id < 1) { $errors[] = 'ID utilisateur invalide.'; }

    if ($nom === '')               { $errors[] = 'Le nom est requis.'; }
    elseif (preg_match('/\d/', $nom)) { $errors[] = 'Le nom ne doit pas contenir de chiffres.'; }

    if ($prenom === '')               { $errors[] = 'Le prénom est requis.'; }
    elseif (preg_match('/\d/', $prenom)) { $errors[] = 'Le prénom ne doit pas contenir de chiffres.'; }

    if ($email === '')                               { $errors[] = "L'email est requis."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide.'; }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) { $role = 'utilisateur'; }

    if ($errors === []) {
        $existingUser = $userController->getUserById($id);
        if (!$existingUser) {
            $errors[] = 'Utilisateur non trouvé.';
        } else {
            $userWithEmail = $userController->getUserByEmail($email);
            if ($userWithEmail && $userWithEmail['id_utilisateur'] != $id) {
                $errors[] = 'Cet email est déjà utilisé par un autre utilisateur.';
            } else {
                try {
                    $userModel = new User();
                    $userModel->setIdUtilisateur($id);
                    $userModel->setNom($nom);
                    $userModel->setPrenom($prenom);
                    $userModel->setEmail($email);
                    $userModel->setMdp($existingUser['mdp']);
                    $userModel->setRole($role);
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                }

                if ($errors === [] && $userController->updateUser($userModel)) {
                    $success = true;
                } else if ($errors === []) {
                    $errors[] = "Erreur lors de la mise à jour.";
                }
            }
        }
    }
    
    // Si succès, rediriger
    if ($success) {
        header('Location: updateUser.php?id=' . $id . '&success=1');
        exit();
    }
}

// Mettre à jour $userData avec les valeurs POST en cas d'erreur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $userData['id_utilisateur'] = $_POST['id'] ?? $userData['id_utilisateur'];
    $userData['nom'] = $_POST['nom'] ?? $userData['nom'];
    $userData['prenom'] = $_POST['prenom'] ?? $userData['prenom'];
    $userData['email'] = $_POST['email'] ?? $userData['email'];
    $userData['role'] = $_POST['role'] ?? $userData['role'];
}

// Pour les requêtes AJAX en GET (chargement des données pour le modal)
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $userData['id_utilisateur'],
            'nom' => $userData['nom'],
            'prenom' => $userData['prenom'],
            'email' => $userData['email'],
            'role' => $userData['role']
        ]
    ]);
    exit();
}

// Pour les requêtes AJAX en POST
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #333; margin-bottom: 24px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .error-box ul { margin: 0; padding-left: 18px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; font-weight: bold; color: #555; margin-bottom: 5px; }
        input[type=text], input[type=email], input[type=password], select {
            width: 100%; padding: 10px 12px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 14px; box-sizing: border-box;
        }
        input:focus, select:focus { outline: none; border-color: #4CAF50; box-shadow: 0 0 0 2px rgba(76,175,80,.2); }
        button[type=submit] {
            background: #2196F3; color: white; border: none; padding: 11px 24px;
            border-radius: 6px; font-size: 15px; cursor: pointer; width: 100%;
        }
        button[type=submit]:hover { background: #1976D2; }
        .back-link { display: inline-block; margin-top: 16px; color: #666; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>✏️ Modifier l'utilisateur</h1>

    <?php if ($errors !== []): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
        <div class="success-box">✅ Utilisateur mis à jour avec succès !</div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <input type="hidden" name="id" value="<?= htmlspecialchars($userData['id_utilisateur'], ENT_QUOTES, 'UTF-8') ?>">
        
        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($userData['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Prénom</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($userData['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Rôle</label>
            <select name="role">
                <option value="utilisateur"    <?= ($userData['role'] === 'utilisateur')    ? 'selected' : '' ?>>Utilisateur</option>
                <option value="nutritionniste" <?= ($userData['role'] === 'nutritionniste') ? 'selected' : '' ?>>Nutritionniste</option>
                <option value="ecologiste"     <?= ($userData['role'] === 'ecologiste')     ? 'selected' : '' ?>>Écologiste</option>
                <option value="admin"          <?= ($userData['role'] === 'admin')          ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <button type="submit">Mettre à jour</button>
    </form>
    
    <a href="showUser.php" class="back-link">← Retour à la liste des utilisateurs</a>
</body>
</html>