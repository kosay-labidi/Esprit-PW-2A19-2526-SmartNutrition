<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../../Controller/user.controller.php';

// Supprimer le token DB
if (!empty($_COOKIE['remember_token'])) {
    $userC = new UserController();
    $userC->deleteRememberToken($_COOKIE['remember_token']);
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
}

session_destroy();
echo json_encode(['success' => true]);