<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── CORS dynamique (accepte localhost avec n'importe quel port) ──
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost', 'http://127.0.0.1'];
if (in_array($origin, $allowed) || preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

require_once __DIR__ . '/../../../Controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

/**
 * Récupère l'utilisateur complet depuis la BDD (avec photo et status)
 */
function getUserFullData($userId) {
    try {
        $db = config::getConnexion();
        $stmt = $db->prepare("
            SELECT id_utilisateur, nom, prenom, email, role, photo, avatar, status, date_creation
            FROM utilisateurs
            WHERE id_utilisateur = :id
        ");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Ajouter un libellé pour le statut
            $statusLabels = [
                'actif' => 'Actif',
                'inactif' => 'Inactif',
                'suspendu' => 'Suspendu'
            ];
            $user['status_label'] = $statusLabels[$user['status']] ?? $user['status'];

            // S'assurer que l'URL de la photo est complète si besoin
            if (!empty($user['photo']) && !preg_match('/^https?:\/\//', $user['photo'])) {
                $user['photo_url'] = 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/' . $user['photo'];
            } else {
                $user['photo_url'] = $user['photo'] ?? null;
            }
        }
        return $user;
    } catch (PDOException $e) {
        error_log("auto_login.php - Erreur BDD: " . $e->getMessage());
        return null;
    }
}

// Si déjà connecté via session → OK
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['id_utilisateur'])) {
    // Recharger les données fraîches depuis la BDD (pour avoir photo et status à jour)
    $freshUser = getUserFullData($_SESSION['user']['id_utilisateur']);

    if ($freshUser) {
        // Vérifier si le compte est inactif ou suspendu
        if ($freshUser['status'] === 'inactif') {
            // Détruire la session et rediriger vers login
            session_destroy();
            echo json_encode([
                'success' => false,
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.',
                'status' => 'inactif'
            ]);
            exit();
        }

        // NOUVEAU: Vérifier si le compte est suspendu
        if ($freshUser['status'] === 'suspendu') {
            session_destroy();
            echo json_encode([
                'success' => false,
                'message' => 'suspended',
                'status' => 'suspendu',
                'contact_email' => 'gaiaalumen@gmail.com'
            ]);
            exit();
        }

        // Mettre à jour la session avec les données fraîches
        $_SESSION['user'] = $freshUser;
        echo json_encode(['success' => true, 'data' => $freshUser]);
    } else {
        // Utilisateur introuvable en BDD
        session_destroy();
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    }
    exit();
}

// Sinon, vérifier le cookie remember_token
$token = $_COOKIE['remember_token'] ?? '';

if ($token === '') {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit();
}

$userC = new UserController();
$userBasic = $userC->getUserByRememberToken($token);

if ($userBasic && !empty($userBasic['id_utilisateur'])) {
    // Récupérer les données complètes depuis la BDD
    $fullUser = getUserFullData($userBasic['id_utilisateur']);

    if ($fullUser) {
        // Vérifier si le compte est inactif
        if ($fullUser['status'] === 'inactif') {
            setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
            echo json_encode([
                'success' => false,
                'message' => 'Votre compte a été désactivé.',
                'status' => 'inactif'
            ]);
            exit();
        }

        // NOUVEAU: Vérifier si le compte est suspendu
        if ($fullUser['status'] === 'suspendu') {
            setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
            echo json_encode([
                'success' => false,
                'message' => 'suspended',
                'status' => 'suspendu',
                'contact_email' => 'gaiaalumen@gmail.com'
            ]);
            exit();
        }

        $_SESSION['user'] = $fullUser;
        echo json_encode(['success' => true, 'data' => $fullUser]);
    } else {
        setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    }
} else {
    setcookie('remember_token', '', ['expires' => time() - 3600, 'path' => '/']);
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
}
?>