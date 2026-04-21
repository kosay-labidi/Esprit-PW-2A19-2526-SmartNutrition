<?php
require_once __DIR__ . '/../../../auth.php';
requireAdmin();    
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../config.php';


$userController = new UserController();

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($contentType, 'application/json')) {
    requireAdmin();

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $nom    = trim($input['nom']    ?? '');
    $prenom = trim($input['prenom'] ?? '');
    $email  = trim($input['email']  ?? '');
    $mdp    = $input['mdp']         ?? '';
    $role   = trim($input['role']   ?? 'utilisateur');

    $errors = [];

    if ($nom === '')               { $errors[] = 'Le nom est requis.'; }
    elseif (preg_match('/\d/', $nom)) { $errors[] = 'Le nom ne doit pas contenir de chiffres.'; }

    if ($prenom === '')               { $errors[] = 'Le prénom est requis.'; }
    elseif (preg_match('/\d/', $prenom)) { $errors[] = 'Le prénom ne doit pas contenir de chiffres.'; }

    if ($email === '')                               { $errors[] = "L'email est requis."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide.'; }

    if ($mdp === '')          { $errors[] = 'Le mot de passe est requis.'; }
    elseif (strlen($mdp) < 6) { $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.'; }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) { $role = 'utilisateur'; }

    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    if ($userController->emailExists($email)) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
        exit();
    }

    try {
        $userModel = new User();
        $userModel->setNom($nom);
        $userModel->setPrenom($prenom);
        $userModel->setEmail($email);
        $userModel->setMdp($mdp);
        $userModel->setRole($role);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }

    if ($userController->addUser($userModel)) {
        echo json_encode(['success' => true, 'message' => 'Compte créé avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la création du compte (email peut-être déjà utilisé)."]);
    }
    exit();
}

$errors  = [];
$success = isset($_GET['success']) && $_GET['success'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $mdp    = $_POST['mdp']         ?? '';
    $role   = trim($_POST['role']   ?? 'utilisateur');

    if ($nom === '')               { $errors[] = 'Le nom est requis.'; }
    elseif (preg_match('/\d/', $nom)) { $errors[] = 'Le nom ne doit pas contenir de chiffres.'; }

    if ($prenom === '')               { $errors[] = 'Le prénom est requis.'; }
    elseif (preg_match('/\d/', $prenom)) { $errors[] = 'Le prénom ne doit pas contenir de chiffres.'; }

    if ($email === '')                               { $errors[] = "L'email est requis."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email invalide.'; }

    if ($mdp === '')          { $errors[] = 'Le mot de passe est requis.'; }
    elseif (strlen($mdp) < 6) { $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.'; }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) { $role = 'utilisateur'; }

    if ($errors === []) {
        if ($userController->emailExists($email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            try {
                $userModel = new User();
                $userModel->setNom($nom);
                $userModel->setPrenom($prenom);
                $userModel->setEmail($email);
                $userModel->setMdp($mdp);
                $userModel->setRole($role);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }

            if ($errors === [] && $userController->addUser($userModel)) {
                header('Location: addUser.php?success=1');
                exit();
            }
            if ($errors === []) {
                $errors[] = "Erreur lors de l'ajout.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #333; margin-bottom: 24px; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .error-box ul { margin: 0; padding-left: 18px; }
        .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 14px; font-weight: bold; color: #555; margin-bottom: 5px; }
        input[type=text], input[type=password], select {
            width: 100%; padding: 10px 12px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 14px; box-sizing: border-box;
        }
        input:focus, select:focus { outline: none; border-color: #4CAF50; box-shadow: 0 0 0 2px rgba(76,175,80,.2); }
        button[type=submit] {
            background: #4CAF50; color: white; border: none; padding: 11px 24px;
            border-radius: 6px; font-size: 15px; cursor: pointer; width: 100%;
        }
        button[type=submit]:hover { background: #388E3C; }
    </style>
</head>
<body>
    <h1>➕ Ajouter un utilisateur</h1>

    <?php if ($errors !== []): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-box">✅ Utilisateur ajouté avec succès !</div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Prénom</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">   <!-- type="text" au lieu de email -->
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="mdp">
        </div>
        <div class="form-group">
            <label>Rôle</label>
            <select name="role">
                <option value="utilisateur"    <?= (($_POST['role'] ?? '') === 'utilisateur')    ? 'selected' : '' ?>>Utilisateur</option>
                <option value="nutritionniste" <?= (($_POST['role'] ?? '') === 'nutritionniste') ? 'selected' : '' ?>>Nutritionniste</option>
                <option value="ecologiste"     <?= (($_POST['role'] ?? '') === 'ecologiste')     ? 'selected' : '' ?>>Écologiste</option>
                <option value="admin"          <?= (($_POST['role'] ?? '') === 'admin')          ? 'selected' : '' ?>>Administrateur</option>
            </select>
        </div>
        <button type="submit">Ajouter l'utilisateur</button>
    </form>
</body>
</html>