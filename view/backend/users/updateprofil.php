<?php
require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../controller/user.controller.php');
require_once(__DIR__ . '/../../../Model/User.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000'); // Mettez votre URL
header('Access-Control-Allow-Credentials: true');

requireAuth();
$sessionUser = getSessionUser();

$userC = new UserController();

// ✅ NOUVEAU: Récupérer les informations du profil (soit l'utilisateur connecté, soit un ID spécifique)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Cas 1: Récupérer l'utilisateur connecté (dashboard.js)
    if (isset($_GET['action']) && $_GET['action'] === 'get' && (!isset($_GET['id']) || $_GET['id'] == 0)) {
        if ($sessionUser) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $sessionUser['id_utilisateur'],
                    'nom' => $sessionUser['nom'],
                    'prenom' => $sessionUser['prenom'],
                    'email' => $sessionUser['email'],
                    'role' => $sessionUser['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
        exit();
    }
    
    // Cas 2: Récupérer un utilisateur spécifique par ID (pour édition)
    if (isset($_GET['id']) && $_GET['id'] > 0) {
        $id = (int)$_GET['id'];
        
        // Vérifier les droits (admin ou son propre profil)
        if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit();
        }
        
        $userData = $userC->getUserById($id);
        
        if ($userData) {
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $userData['id_utilisateur'],
                    'nom' => $userData['nom'],
                    'prenom' => $userData['prenom'],
                    'email' => $userData['email'],
                    'role' => $userData['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
        exit();
    }
    
    // Cas 3: Aucun paramètre valide
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// POST: Mettre à jour le profil (reste identique)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lire les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback pour les données POST classiques
    if (!$input) {
        $input = $_POST;
    }
    
    error_log("updateprofil.php - Données reçues: " . print_r($input, true));
    
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $nom = trim($input['nom'] ?? '');
    $prenom = trim($input['prenom'] ?? '');
    $email = trim($input['email'] ?? '');
    $old_password = $input['old_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    
    // Validations de base
    if ($id < 1) {
        echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
        exit();
    }
    
    // Vérifier les droits (admin ou son propre profil)
    if ($sessionUser['role'] !== 'admin' && $sessionUser['id_utilisateur'] != $id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
        exit();
    }
    
    if (empty($nom) || empty($prenom) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format d\'email invalide']);
        exit();
    }
    
    // Récupérer l'utilisateur existant via le controller
    $existingUser = $userC->getUserById($id);
    
    if (!$existingUser) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit();
    }
    
    // Vérifier l'ancien mot de passe si un nouveau mot de passe est fourni
    if (!empty($new_password)) {
        if (empty($old_password)) {
            echo json_encode(['success' => false, 'message' => 'L\'ancien mot de passe est requis pour modifier le mot de passe']);
            exit();
        }
        
        // Vérifier l'ancien mot de passe
        if (!password_verify($old_password, $existingUser['mdp'])) {
            echo json_encode(['success' => false, 'message' => 'Ancien mot de passe incorrect']);
            exit();
        }
        
        // Valider la longueur du nouveau mot de passe
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères']);
            exit();
        }
        
        // Hasher le nouveau mot de passe
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    }
    
    // Construction de la requête SQL
    try {
        $db = config::getConnexion();
        
        if (!empty($new_password)) {
            // Mise à jour avec mot de passe
            $sql = "UPDATE utilisateurs 
                    SET nom = :nom, 
                        prenom = :prenom, 
                        email = :email, 
                        mdp = :mdp,
                        date_mise_a_jour = NOW()
                    WHERE id_utilisateur = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':mdp' => $hashedPassword
            ]);
        } else {
            // Mise à jour sans mot de passe
            $sql = "UPDATE utilisateurs 
                    SET nom = :nom, 
                        prenom = :prenom, 
                        email = :email,
                        date_mise_a_jour = NOW()
                    WHERE id_utilisateur = :id";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email
            ]);
        }
        
        if ($result) {
            // Mettre à jour la session si l'utilisateur modifie son propre profil
            if ($sessionUser['id_utilisateur'] == $id) {
                $_SESSION['user']['nom'] = $nom;
                $_SESSION['user']['prenom'] = $prenom;
                $_SESSION['user']['email'] = $email;
            }
            
            $message = !empty($new_password) ? 'Profil et mot de passe mis à jour avec succès' : 'Profil mis à jour avec succès';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        
    } catch (PDOException $e) {
        error_log("Erreur SQL updateprofil: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
    }
    exit();
}

// Méthode non autorisée
echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
?>