<?php
// view/backend/users/toggle_account_status.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

requireAdmin();
$sessionUser = getSessionUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Vérification des droits (admin ou son propre profil)
if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
    exit();
}

// Empêcher un admin de se désactiver lui-même
if ($sessionUser['role'] === 'admin' && $sessionUser['id_utilisateur'] == $userId) {
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas désactiver votre propre compte administrateur']);
    exit();
}

$db = config::getConnexion();

// Récupérer le statut actuel
$stmt = $db->prepare("SELECT status FROM utilisateurs WHERE id_utilisateur = :id");
$stmt->execute([':id' => $userId]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    exit();
}

$newStatus = $current['status'] === 'actif' ? 'inactif' : 'actif';
$message = $newStatus === 'actif' ? 'Compte réactivé avec succès' : 'Compte désactivé avec succès';

$stmt = $db->prepare("UPDATE utilisateurs SET status = :status WHERE id_utilisateur = :id");
$result = $stmt->execute([':status' => $newStatus, ':id' => $userId]);

if ($result) {
    // Si l'utilisateur désactive son propre compte, détruire la session
    if ($sessionUser['id_utilisateur'] == $userId && $newStatus === 'inactif') {
        session_destroy();
        echo json_encode([
            'success' => true, 
            'message' => $message, 
            'redirect' => true,
            'status' => $newStatus
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => $message, 
            'redirect' => false,
            'status' => $newStatus
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
}
?>