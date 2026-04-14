<?php
// Fichier de test pour déboguer l'AJAX
header('Content-Type: application/json');

try {
    require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
    
    $userId = 1; // Test avec l'utilisateur 1
    $demandeC = new DemandeplanningController();
    $demandes = $demandeC->listDemandesByUser($userId);
    
    echo json_encode([
        'success' => true,
        'count' => count($demandes),
        'data' => $demandes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
