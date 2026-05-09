<?php
/**
 * Utilisateur courant (session) — pour afficher le bon nom dans le chat.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
if (ob_get_length()) {
    ob_clean();
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$nom = trim((string)($_SESSION['nom'] ?? $_SESSION['name'] ?? ''));
$pseudo = trim((string)($_SESSION['pseudo'] ?? ''));
$email = trim((string)($_SESSION['email'] ?? ''));

if ($nom === '' && $pseudo !== '') {
    $nom = $pseudo;
}
if ($nom === '' && $uid > 0) {
    $nom = 'Utilisateur #' . $uid;
}

echo json_encode([
    'id' => $uid,
    'nom' => $nom !== '' ? $nom : 'Invité',
    'pseudo' => $pseudo,
    'email' => $email,
]);
