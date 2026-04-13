<?php
session_start();
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

// Get user ID from session (fallback to 1 for testing)
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

echo "User ID: " . $userId . "<br><br>";

$demandeC = new DemandeplanningController();

try {
    $demandes = $demandeC->listDemandesByUser($userId);
    echo "Nombre de demandes: " . count($demandes) . "<br><br>";
    echo "<pre>";
    print_r($demandes);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
