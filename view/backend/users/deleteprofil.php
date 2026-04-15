<?php
require_once(__DIR__ . '/../../../controller/user.controller.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit();
}

try {
    $db = config::getConnexion();
    
    // Vérifier si l'utilisateur existe
    $checkSql = "SELECT id_utilisateur FROM utilisateurs WHERE id_utilisateur = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':id' => $id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }
    
    // Grâce à ON DELETE CASCADE, une seule suppression suffit !
    // Toutes les tables liées seront automatiquement nettoyées :
    // - repas (et repas_aliment via CASCADE)
    // - participations (événements)
    // - dossier_medical (et dossier_regime via CASCADE)
    // - preferences
    // - participation (défis) (et action_defi via CASCADE)
    
    $sql = "DELETE FROM utilisateurs WHERE id_utilisateur = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Votre compte a été supprimé définitivement.'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur SQL deleteprofil: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
    ]);
}
?>