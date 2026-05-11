<?php
// face_login.php - Version avec GD
session_start();
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

// Vérifier GD
if (!extension_loaded('gd')) {
    echo json_encode(['success' => false, 'message' => 'Extension GD non disponible. Veuillez contacter l\'administrateur.']);
    exit();
}

// Connexion BDD
try {
    $pdo = new PDO("mysql:host=localhost;dbname=dsgaialumen;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()]);
    exit();
}

// Dossier temp
$tempDir = __DIR__ . '/temp/';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

// Récupérer l'image
$imageData = null;

if (isset($_FILES['face_image']) && $_FILES['face_image']['error'] === UPLOAD_ERR_OK) {
    $imageData = file_get_contents($_FILES['face_image']['tmp_name']);
} elseif (isset($_POST['image_base64'])) {
    $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $_POST['image_base64']);
    $imageData = base64_decode($base64);
} else {
    echo json_encode(['success' => false, 'message' => 'Aucune image reçue']);
    exit();
}

if (!$imageData) {
    echo json_encode(['success' => false, 'message' => 'Image invalide']);
    exit();
}

$tempFile = $tempDir . 'face_' . uniqid() . '.jpg';
file_put_contents($tempFile, $imageData);

$result = findUserByFace($pdo, $tempFile);

@unlink($tempFile);

echo json_encode($result);
exit();

function findUserByFace($pdo, $uploadedPath) {
    try {
        // Récupérer les utilisateurs avec photo
        $stmt = $pdo->prepare("
            SELECT id_utilisateur, nom, prenom, email, role, photo 
            FROM utilisateurs 
            WHERE photo IS NOT NULL AND photo != '' AND status = 'actif'
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            return ['success' => false, 'message' => 'Aucun utilisateur avec photo de profil'];
        }
        
        $bestMatch = null;
        $bestScore = 0;
        $threshold = 0.6; // Seuil plus élevé pour GD
        
        foreach ($users as $user) {
            $storedPath = findPhotoPath($user['photo']);
            
            if (!$storedPath || !file_exists($storedPath)) {
                continue;
            }
            
            // Comparer avec GD
            $score = compareImagesGD($uploadedPath, $storedPath);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $user;
            }
        }
        
        if ($bestMatch && $bestScore >= $threshold) {
            $_SESSION['user'] = [
                'id_utilisateur' => $bestMatch['id_utilisateur'],
                'nom' => $bestMatch['nom'],
                'prenom' => $bestMatch['prenom'],
                'email' => $bestMatch['email'],
                'role' => $bestMatch['role']
            ];
            
            return [
                'success' => true,
                'data' => [
                    'id_utilisateur' => $bestMatch['id_utilisateur'],
                    'nom' => $bestMatch['nom'],
                    'prenom' => $bestMatch['prenom'],
                    'email' => $bestMatch['email'],
                    'role' => $bestMatch['role']
                ],
                'confidence' => round($bestScore, 2),
                'message' => 'Visage reconnu avec ' . round($bestScore * 100) . '%'
            ];
        }
        
        return [
            'success' => false, 
            'message' => 'Visage non reconnu. Score maximum: ' . round($bestScore * 100) . '%'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

function compareImagesGD($img1, $img2) {
    try {
        // Redimensionner et convertir en niveaux de gris
        $im1 = imagecreatefromjpeg($img1);
        $im2 = imagecreatefromjpeg($img2);
        
        if (!$im1 || !$im2) {
            // Essayer PNG
            $im1 = $im1 ?: imagecreatefrompng($img1);
            $im2 = $im2 ?: imagecreatefrompng($img2);
        }
        
        if (!$im1 || !$im2) return 0;
        
        // Redimensionner à 32x32 pour comparaison
        $size = 32;
        $small1 = imagescale($im1, $size, $size);
        $small2 = imagescale($im2, $size, $size);
        
        // Convertir en niveaux de gris et calculer la différence
        $diff = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb1 = imagecolorat($small1, $x, $y);
                $rgb2 = imagecolorat($small2, $x, $y);
                
                $r1 = ($rgb1 >> 16) & 0xFF;
                $g1 = ($rgb1 >> 8) & 0xFF;
                $b1 = $rgb1 & 0xFF;
                $gray1 = ($r1 + $g1 + $b1) / 3;
                
                $r2 = ($rgb2 >> 16) & 0xFF;
                $g2 = ($rgb2 >> 8) & 0xFF;
                $b2 = $rgb2 & 0xFF;
                $gray2 = ($r2 + $g2 + $b2) / 3;
                
                $diff += abs($gray1 - $gray2);
            }
        }
        
        // Nettoyer
        imagedestroy($im1);
        imagedestroy($im2);
        imagedestroy($small1);
        imagedestroy($small2);
        
        // Convertir en score de similarité (0-1)
        $maxDiff = $size * $size * 255;
        $similarity = 1 - ($diff / $maxDiff);
        
        return $similarity;
        
    } catch (Exception $e) {
        return 0;
    }
}

function findPhotoPath($relativePath) {
    $filename = basename($relativePath);
    
    $paths = [
        __DIR__ . '/../../uploads/profiles/' . $filename,
        'C:/xampp/htdocs/Mainn/uploads/profiles/' . $filename,
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}
?>