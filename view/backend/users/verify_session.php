<?php
// view/backend/users/verify_session.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id_utilisateur'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit();
}

$db = config::getConnexion();
$stmt = $db->prepare("SELECT id_utilisateur, nom, prenom, email, role, photo, status FROM utilisateurs WHERE id_utilisateur = :id");
$stmt->execute([':id' => $_SESSION['user']['id_utilisateur']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    exit();
}

// Vérifier si le compte est inactif
if ($user['status'] === 'inactif') {
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Votre compte a été désactivé', 'status' => 'inactif']);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => [
        'id_utilisateur' => $user['id_utilisateur'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'email' => $user['email'],
        'role' => $user['role'],
        'photo' => $user['photo'],
        'status' => $user['status']
    ]
]);
?>