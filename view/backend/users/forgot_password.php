<?php
/**
 * forgot_password.php  —  view/frontend/users/
 * Reçoit l'email, génère le token, envoie le mail de réinitialisation.
 * POST { "email": "user@example.com" }
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../controller/Passwordreset.controller.php';
$body  = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
    exit();
}

$userCtrl  = new UserController();
$resetCtrl = new PasswordResetController();

$user = $userCtrl->getUserByEmail($email);

// ✅ OPTION 1 : Vérification stricte - email doit exister
if (!$user) {
    echo json_encode(['success' => false, 'message' => '❌ Aucun compte trouvé avec cette adresse email.']);
    exit();
}



$token = $resetCtrl->createResetToken($email);
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Erreur interne. Veuillez réessayer.']);
    exit();
}

$name = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$resetCtrl->sendResetEmail($email, $token, $name);

echo json_encode(['success' => true, 'message' => '✓ Email envoyé ! Vérifiez votre boîte de réception.']);
?>