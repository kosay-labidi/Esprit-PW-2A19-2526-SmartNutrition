<?php
// view/backend/users/generate_avatar.php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

requireAuth();
$sessionUser = getSessionUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$prompt = isset($input['prompt']) ? trim($input['prompt']) : '';

if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
    exit();
}

if (empty($prompt)) {
    echo json_encode(['success' => false, 'message' => 'Un prompt est requis']);
    exit();
}

// Récupérer les infos utilisateur
$db = config::getConnexion();
$stmt = $db->prepare("SELECT prenom FROM utilisateurs WHERE id_utilisateur = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    exit();
}

$firstName = $user['prenom'];

// ============================================
// GÉNÉRATION D'AVATAR PAR PROMPT
// API: Pollinations.ai (gratuite, sans clé API)
// ============================================

// Encoder le prompt avec le prénom de l'utilisateur
$fullPrompt = urlencode("$prompt, avatar for $firstName, profile picture, cartoon style, clean background, cute");

// URL de l'API Pollinations (génération d'image gratuite)
$imageUrl = "https://image.pollinations.ai/prompt/$fullPrompt?width=512&height=512&nologo=true";

// Télécharger l'image générée
$imageContent = @file_get_contents($imageUrl);

if ($imageContent === false) {
    // Fallback vers DiceBear si l'API échoue
    $fallbackSeed = urlencode($firstName . '_' . $userId);
    $imageUrl = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $fallbackSeed . "&backgroundColor=1F3D2B";
    $imageContent = @file_get_contents($imageUrl);

    if ($imageContent === false) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération de l\'avatar']);
        exit();
    }
}

// Sauvegarder l'avatar
$uploadDir = __DIR__ . '/../../../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Déterminer le type de fichier (PNG pour Pollinations, SVG pour fallback)
if (strpos($imageUrl, '.svg') !== false) {
    $extension = 'svg';
    $mime = 'image/svg+xml';
} else {
    $extension = 'png';
    $mime = 'image/png';
}

$filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;
file_put_contents($filepath, $imageContent);

$relativePath = 'uploads/avatars/' . $filename;

// Mettre à jour l'avatar dans la BDD
$stmt = $db->prepare("UPDATE utilisateurs SET avatar = :avatar WHERE id_utilisateur = :id");
$stmt->execute([':avatar' => $relativePath, ':id' => $userId]);

echo json_encode([
    'success' => true,
    'message' => 'Avatar généré avec succès !',
    'prompt' => $prompt,
    'avatar_url' => 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/' . $relativePath
]);
?>