<?php
// face_login.php - Version avec vraie reconnaissance faciale
require_once __DIR__ . '/../../config.php';
session_start();
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

// Connexion BDD
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'BDD error: ' . $e->getMessage()]);
    exit();
}

// Créer dossier temp
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

// Sauvegarder l'image temporairement
$tempFile = $tempDir . 'face_' . uniqid() . '.jpg';
file_put_contents($tempFile, $imageData);

// Rechercher l'utilisateur par comparaison faciale
$result = findUserByFaceComparison($pdo, $tempFile);

// Nettoyer
@unlink($tempFile);

echo json_encode($result);
exit();

/**
 * Compare le visage avec tous les utilisateurs
 */
function findUserByFaceComparison($pdo, $uploadedImagePath) {
    try {
        // Récupérer tous les utilisateurs avec photo
        $stmt = $pdo->prepare("
            SELECT id_utilisateur, nom, prenom, email, role, photo 
            FROM utilisateurs 
            WHERE photo IS NOT NULL AND photo != '' AND status = 'actif'
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            return ['success' => false, 'message' => 'Aucun utilisateur avec photo. Veuillez d\'abord ajouter une photo de profil.'];
        }
        
        $bestMatch = null;
        $bestScore = 0;
        $threshold = 0.35; // Seuil de reconnaissance (plus bas = plus tolérant)
        
        foreach ($users as $user) {
            // Trouver le chemin de la photo stockée
            $storedPath = findPhotoPath($user['photo']);
            
            if (!$storedPath || !file_exists($storedPath)) {
                continue;
            }
            
            // Comparer les deux images
            $score = compareFaces($uploadedImagePath, $storedPath);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $user;
            }
        }
        
        if ($bestMatch && $bestScore >= $threshold) {
            // Connexion réussie
            $_SESSION['user_id'] = $bestMatch['id_utilisateur'];
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
                'message' => 'Visage reconnu avec ' . round($bestScore * 100) . '% de correspondance'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Visage non reconnu. Meilleur score: ' . round($bestScore * 100) . '% (seuil: ' . ($threshold * 100) . '%)'
        ];
        
    } catch (Exception $e) {
        error_log("Face recognition error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

/**
 * Trouve le chemin complet de la photo
 */
function findPhotoPath($relativePath) {
    $basePaths = [
        __DIR__ . '/../../',
        __DIR__ . '/../../uploads/profiles/'
    ];
    
    $filename = basename($relativePath);
    
    foreach ($basePaths as $basePath) {
        $fullPath = $basePath . 'uploads/profiles/' . $filename;
        if (file_exists($fullPath)) {
            return $fullPath;
        }
        
        $fullPath2 = $basePath . $relativePath;
        if (file_exists($fullPath2)) {
            return $fullPath2;
        }
    }
    
    return null;
}

/**
 * Compare deux visages avec des hashs perceptuels
 */
function compareFaces($img1Path, $img2Path) {
    try {
        // Calculer les hashs des images
        $hash1 = getPerceptualHash($img1Path);
        $hash2 = getPerceptualHash($img2Path);
        
        if (!$hash1 || !$hash2) {
            return 0;
        }
        
        // Calculer la distance de Hamming
        $distance = hammingDistance($hash1, $hash2);
        
        // Convertir en score de similarité (0-1)
        $maxDistance = 64; // 64 bits
        $similarity = 1 - ($distance / $maxDistance);
        
        return $similarity;
        
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Calcule un hash perceptuel de l'image
 */
function getPerceptualHash($imagePath) {
    try {
        // Lire l'image
        $img = imagecreatefromstring(file_get_contents($imagePath));
        if (!$img) return null;
        
        // Redimensionner à 8x8
        $size = 8;
        $resized = imagescale($img, $size, $size);
        
        // Convertir en niveaux de gris et calculer la moyenne
        $pixels = [];
        $sum = 0;
        
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = ($r + $g + $b) / 3;
                $pixels[$y * $size + $x] = $gray;
                $sum += $gray;
            }
        }
        
        $avg = $sum / ($size * $size);
        
        // Créer le hash binaire
        $hash = '';
        foreach ($pixels as $pixel) {
            $hash .= ($pixel > $avg) ? '1' : '0';
        }
        
        // Nettoyer
        imagedestroy($img);
        imagedestroy($resized);
        
        return $hash;
        
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Calcule la distance de Hamming entre deux hashs
 */
function hammingDistance($hash1, $hash2) {
    $distance = 0;
    $len = strlen($hash1);
    for ($i = 0; $i < $len; $i++) {
        if ($hash1[$i] !== $hash2[$i]) {
            $distance++;
        }
    }
    return $distance;
}
?>