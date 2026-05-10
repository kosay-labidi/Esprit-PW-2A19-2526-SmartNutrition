<?php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../controller/user.controller.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

requireAuth();
$sessionUser = getSessionUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit();
}

// ✅ Un utilisateur ne peut supprimer que son propre compte (sauf admin)
if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Action non autorisée.']);
    exit();
}

try {
    $db = config::getConnexion();

    $checkStmt = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE id_utilisateur = :id");
    $checkStmt->execute([':id' => $id]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }

    $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Votre compte a été supprimé définitivement.']);

    // ✅ Détruire la session si l'utilisateur a supprimé son propre compte
    if ($sessionUser['id_utilisateur'] == $id) {
        $_SESSION = [];
        session_destroy();
    }

} catch (PDOException $e) {
    error_log("Erreur SQL deleteprofil: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
}
?>