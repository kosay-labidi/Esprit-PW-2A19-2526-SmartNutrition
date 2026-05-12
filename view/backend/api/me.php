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

$sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];

$uid = (int)(
    $_SESSION['user_id']
    ?? $sessionUser['id_utilisateur']
    ?? $sessionUser['id']
    ?? 0
);
$nom = trim((string)(
    $_SESSION['nom']
    ?? $_SESSION['name']
    ?? trim(($sessionUser['prenom'] ?? '') . ' ' . ($sessionUser['nom'] ?? ''))
));
$pseudo = trim((string)($_SESSION['pseudo'] ?? $sessionUser['pseudo'] ?? ''));
$email = trim((string)($_SESSION['email'] ?? $sessionUser['email'] ?? ''));

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
