<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../Model/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$userController = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (str_contains($contentType, 'application/json')) {
        $inputData = json_decode(file_get_contents('php://input'), true);
    } else {
        $inputData = $_POST;
    }

    $nom = trim($inputData['nom'] ?? '');
    $prenom = trim($inputData['prenom'] ?? '');
    $email = trim($inputData['email'] ?? '');
    $mdp = $inputData['mdp'] ?? '';
    $role = 'utilisateur';

    $errors = [];

    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom ne doit pas contenir de chiffres.';
    }

    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    } elseif (preg_match('/\d/', $prenom)) {
        $errors[] = 'Le prénom ne doit pas contenir de chiffres.';
    }

    if ($email === '') {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if ($mdp === '') {
        $errors[] = 'Le mot de passe est requis.';
    } elseif (strlen($mdp) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }

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
        echo json_encode(['success' => false, 'message' => "Erreur lors de la création du compte."]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>
