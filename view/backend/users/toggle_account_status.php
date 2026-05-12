<?php
// view/backend/users/toggle_account_status.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

requireAuth();
$sessionUser = getSessionUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON invalide']);
        exit();
    }
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

if ($userId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utilisateur invalide']);
    exit();
}

$sessionUserId = (int)($sessionUser['id_utilisateur'] ?? 0);
$sessionRole = $sessionUser['role'] ?? '';
$isOwnAccount = $sessionUserId === $userId;
$isAdmin = $sessionRole === 'admin';

// Autoriser un admin à gérer les comptes, ou un utilisateur à désactiver son propre compte.
if (!$isAdmin && !$isOwnAccount) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
    exit();
}

// Empêcher un admin de se désactiver lui-même
if ($isAdmin && $isOwnAccount) {
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

$stmt = $db->prepare("UPDATE utilisateurs SET status = :status, date_mise_a_jour = NOW() WHERE id_utilisateur = :id");
$result = $stmt->execute([':status' => $newStatus, ':id' => $userId]);

if ($result) {
    // Si l'utilisateur désactive son propre compte, détruire la session
    if ($isOwnAccount && $newStatus === 'inactif') {
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
