<?php
/**
 * Active une session admin pour les tests locaux XAMPP uniquement.
 * À supprimer ou garder inaccessible en production.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
$addr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$isLocal = str_starts_with($host, 'localhost')
    || str_starts_with($host, '127.0.0.1')
    || $addr === '127.0.0.1'
    || $addr === '::1';

header('Content-Type: application/json; charset=utf-8');

if (!$isLocal) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Dev admin disponible uniquement en local']);
    exit;
}

$_SESSION['user_id'] = (int)($_SESSION['user_id'] ?? 1);
$_SESSION['nom'] = $_SESSION['nom'] ?? 'Admin Test';
$_SESSION['pseudo'] = $_SESSION['pseudo'] ?? 'admin_test';
$_SESSION['email'] = $_SESSION['email'] ?? 'admin@test.local';
$_SESSION['role'] = 'admin';
$_SESSION['is_admin'] = 1;

echo json_encode([
    'ok' => true,
    'message' => 'Session admin locale activée',
    'user' => [
        'id' => (int)$_SESSION['user_id'],
        'nom' => (string)$_SESSION['nom'],
        'email' => (string)$_SESSION['email'],
        'role' => (string)$_SESSION['role'],
    ],
]);
