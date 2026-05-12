<?php
// view/backend/users/check_account_type.php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/user.controller.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

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
