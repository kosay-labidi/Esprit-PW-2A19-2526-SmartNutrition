<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

    // Accepte DELETE (fetch depuis le frontend) ou GET (fallback)
    $id = null;
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
    }

    if (!$id) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'ID manquant ou invalide']);
        exit;
    }

    $controller = new DemandeplanningController();
    $deleted    = $controller->deleteDemande($id);

    ob_end_clean();
    echo json_encode(['success' => (bool)$deleted]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
