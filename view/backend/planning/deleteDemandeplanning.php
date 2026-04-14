<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

header('Content-Type: application/json');

// Accepter GET (pour test direct) et POST (pour AJAX)
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

$controller = new DemandeplanningController();
$deleted = $controller->deleteDemande($id);

if ($deleted) {
    echo json_encode(['success' => true, 'message' => 'Demande supprimée']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Aucune demande trouvée']);
}