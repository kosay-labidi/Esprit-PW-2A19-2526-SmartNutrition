<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

// Vérification des droits admin
requireAdmin();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

// Détecter si c'est une requête AJAX
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || 
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id'] ?? 0);
    
    if ($id > 0) {
        $userController = new UserController();
        $result = $userController->deleteUser($id);
        
        if ($isAjax) {
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
        } else {
            if ($result) {
                header('Location: showUser.php?deleted=1');
            } else {
                header('Location: showUser.php?error=1');
            }
        }
        exit();
    }
}

if ($isAjax) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
} else {
    header('Location: showUser.php');
}
exit();
?>