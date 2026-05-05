<?php
// view/backend/users/update_profile_picture.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/user.controller.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

requireAuth();
$sessionUser = getSessionUser();

$uploadDir = __DIR__ . '/../../../uploads/profiles/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    // Vérification des droits
    if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
        exit();
    }
    
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Aucune image reçue ou erreur d\'upload']);
        exit();
    }
    
    $file = $_FILES['profile_image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format non accepté. Utilisez JPG, PNG ou WEBP']);
        exit();
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image trop volumineuse (max 5MB)']);
        exit();
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Supprimer l'ancienne photo si elle existe
    $userC = new UserController();
    $existingUser = $userC->getUserById($userId);
    if ($existingUser && !empty($existingUser['photo'])) {
        $oldPhoto = $uploadDir . basename($existingUser['photo']);
        if (file_exists($oldPhoto)) {
            unlink($oldPhoto);
        }
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativePath = 'uploads/profiles/' . $filename;
        
        $db = config::getConnexion();
        $stmt = $db->prepare("UPDATE utilisateurs SET photo = :photo WHERE id_utilisateur = :id");
        $stmt->execute([':photo' => $relativePath, ':id' => $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Photo de profil mise à jour',
            'photo_url' => 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/' . $relativePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>