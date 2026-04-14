<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../Controller/user.controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$mdp   = $body['mdp'] ?? '';

if ($email === '' || $mdp === '') {
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
    exit();
}

$userC  = new UserController();
$result = $userC->login($email, $mdp);

switch ($result['status']) {
    case 'ok':
        echo json_encode(['success' => true, 'data' => $result['data']]);
        break;
    case 'account_not_found':
        echo json_encode(['success' => false, 'message' => 'Aucun compte associé à cet email']);
        break;
    case 'wrong_password':
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}