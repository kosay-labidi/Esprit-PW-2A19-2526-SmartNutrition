<?php
// view/backend/users/update_avatar.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

requireAuth();
$sessionUser = getSessionUser();

$uploadDir = __DIR__ . '/../../../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
        exit();
    }
    
    if (!isset($_FILES['avatar_image']) || $_FILES['avatar_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
        exit();
    }
    
    $file = $_FILES['avatar_image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/svg+xml'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format non accepté']);
        exit();
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativePath = 'uploads/avatars/' . $filename;
        
        $db = config::getConnexion();
        $stmt = $db->prepare("UPDATE utilisateurs SET avatar = :avatar WHERE id_utilisateur = :id");
        $stmt->execute([':avatar' => $relativePath, ':id' => $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Avatar mis à jour',
            'avatar_url' => 'http://localhost/Mainn/' . $relativePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>