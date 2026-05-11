<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

// Vérification des droits admin
requireAdmin();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Détecter si c'est une requête AJAX
$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || 
          (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

$input = [];
if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $id = isset($input['id'])
        ? intval($input['id'])
        : (isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id'] ?? 0));
    
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
