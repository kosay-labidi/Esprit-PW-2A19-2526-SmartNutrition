<?php
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../config.php';

$userController = new UserController();
$errors = [];
$success = isset($_GET['success']) && $_GET['success'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $role = trim($_POST['role'] ?? 'utilisateur');

    if ($nom === '') {
        $errors[] = 'Le nom est requis';
    }
    if ($prenom === '') {
        $errors[] = 'Le prénom est requis';
    }
    if ($email === '') {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide';
    }
    if ($mdp === '') {
        $errors[] = 'Le mot de passe est requis';
    } elseif (strlen($mdp) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    $rolesValides = ['utilisateur', 'medecin', 'admin'];
    if (!in_array($role, $rolesValides, true)) {
        $role = 'utilisateur';
    }

    if ($errors === []) {
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
            exit;
        }
        if ($errors === []) {
            $errors[] = "Erreur lors de l'ajout (email peut-être déjà utilisé)";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur</title>
</head>
<body>
    <h1>Ajouter un utilisateur</h1>

    <?php if ($errors !== []): ?>
        <div style="color: red; border: 1px solid red; padding: 10px; margin: 10px 0;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color: green; border: 1px solid green; padding: 10px; margin: 10px 0;">
            Utilisateur ajouté avec succès !
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div>
            <label>Nom :</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label>Prénom :</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label>Email :</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div>
            <label>Mot de passe :</label>
            <input type="password" name="mdp" required>
        </div>

        <div>
            <label>Rôle :</label>
            <select name="role">
                <option value="utilisateur" <?= (($_POST['role'] ?? '') === 'utilisateur') ? 'selected' : '' ?>>Utilisateur</option>
                <option value="medecin" <?= (($_POST['role'] ?? '') === 'medecin') ? 'selected' : '' ?>>Médecin</option>
                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <button type="submit">Ajouter l'utilisateur</button>
    </form>
</body>
</html>
