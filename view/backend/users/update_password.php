<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/Passwordreset.controller.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$body = json_decode(file_get_contents('php://input'), true);
$token = trim($body['token'] ?? '');
$mdp = $body['mdp'] ?? '';

if ($token === '') {
    echo json_encode(['success' => false, 'message' => 'Token invalide']);
    exit();
}

if (strlen($mdp) < 8) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
    exit();
}

$resetCtrl = new PasswordResetController();
$email = $resetCtrl->validateToken($token);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
    exit();
}

if ($resetCtrl->updatePassword($email, $mdp)) {
    $resetCtrl->consumeToken($token);
    echo json_encode(['success' => true, 'message' => 'Mot de passe réinitialisé avec succès']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
}
?>
