<?php
// view/backend/users/check_account_type.php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../Controller/user.controller.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'is_oauth' => false]);
    exit();
}

$userC = new UserController();
$user = $userC->getUserByEmail($email);

if (!$user) {
    echo json_encode(['success' => false, 'is_oauth' => false]);
    exit();
}

$isOAuth = !empty($user['provider']) || empty($user['mdp']);
$provider = $user['provider'] ?? '';

echo json_encode([
    'success' => true,
    'is_oauth' => $isOAuth,
    'provider' => $provider
]);
?>