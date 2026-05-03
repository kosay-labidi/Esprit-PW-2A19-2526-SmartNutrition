<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── CORS dynamique (accepte localhost avec n'importe quel port) ──
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost', 'http://127.0.0.1'];
if (in_array($origin, $allowed) || preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

require_once __DIR__ . '/../../../Controller/user.controller.php';

// Si déjà connecté via session → OK
if (!empty($_SESSION['user'])) {
    echo json_encode(['success' => true, 'data' => $_SESSION['user']]);
    exit();
}

// Sinon, vérifier le cookie remember_token
$token = $_COOKIE['remember_token'] ?? '';

if ($token === '') {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit();
}

$userC = new UserController();
$user  = $userC->getUserByRememberToken($token);

if ($user) {
    $_SESSION['user'] = $user;
    echo json_encode(['success' => true, 'data' => $user]);
} else {
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
}