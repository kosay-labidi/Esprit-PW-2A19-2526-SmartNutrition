<?php
// view/backend/users/verify_session.php

// Démarrer la session avant toute chose
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../auth.php');
require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS (pre-flight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false];

// Vérifier si l'utilisateur est connecté en session
if (isset($_SESSION['user']) && isset($_SESSION['user']['id_utilisateur'])) {
    try {
        $db = config::getConnexion();
        
        // Récupérer les informations complètes de l'utilisateur
        $stmt = $db->prepare("SELECT id_utilisateur, nom, prenom, email, role, photo, status FROM utilisateurs WHERE id_utilisateur = :id");
        $stmt->execute([':id' => $_SESSION['user']['id_utilisateur']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Utilisateur non trouvé en base
            session_destroy();
            $response = ['success' => false, 'message' => 'Utilisateur non trouvé'];
        } 
        // Vérifier si le compte est inactif (non activé)
        elseif ($user['status'] === 'inactif') {
            session_destroy();
            $response = [
                'success' => false, 
                'message' => 'Votre compte n\'a pas été activé. Veuillez vérifier vos emails.',
                'status' => 'inactif'
            ];
        }
        // Vérifier si le compte est suspendu
        elseif ($user['status'] === 'suspendu') {
            session_destroy();
            $response = [
                'success' => false, 
                'message' => 'suspended', 
                'status' => 'suspendu',
                'contact_email' => 'gaiaalumen@gmail.com'
            ];
        }
        // Compte actif
        else {
            $response = [
                'success' => true,
                'data' => [
                    'id_utilisateur' => $user['id_utilisateur'],
                    'nom' => $user['nom'],
                    'prenom' => $user['prenom'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'photo' => $user['photo'],
                    'status' => $user['status']
                ]
            ];
        }
    } catch (PDOException $e) {
        error_log('Erreur verify_session: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Erreur serveur'];
    }
} 
// Vérifier le cookie remember_token (session persistante)
elseif (isset($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $db = config::getConnexion();
        
        // Vérifier le token dans la base
        $stmt = $db->prepare("
            SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.role, u.photo, u.status, rt.expires_at 
            FROM utilisateurs u 
            INNER JOIN remember_tokens rt ON u.id_utilisateur = rt.id_utilisateur 
            WHERE rt.token = :token AND rt.expires_at > NOW()
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Vérifier le statut du compte
            if ($user['status'] === 'suspendu') {
                // Supprimer le token et le cookie
                $delStmt = $db->prepare("DELETE FROM remember_tokens WHERE token = :token");
                $delStmt->execute([':token' => $token]);
                setcookie('remember_token', '', time() - 3600, '/');
                
                $response = [
                    'success' => false, 
                    'message' => 'suspended', 
                    'status' => 'suspendu',
                    'contact_email' => 'gaiaalumen@gmail.com'
                ];
            } 
            elseif ($user['status'] === 'inactif') {
                $delStmt = $db->prepare("DELETE FROM remember_tokens WHERE token = :token");
                $delStmt->execute([':token' => $token]);
                setcookie('remember_token', '', time() - 3600, '/');
                
                $response = [
                    'success' => false, 
                    'message' => 'Votre compte n\'a pas été activé',
                    'status' => 'inactif'
                ];
            }
            else {
                // Recréer la session
                $_SESSION['user'] = [
                    'id_utilisateur' => $user['id_utilisateur'],
                    'nom' => $user['nom'],
                    'prenom' => $user['prenom'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                
                $response = [
                    'success' => true,
                    'data' => [
                        'id_utilisateur' => $user['id_utilisateur'],
                        'nom' => $user['nom'],
                        'prenom' => $user['prenom'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'photo' => $user['photo'],
                        'status' => $user['status']
                    ]
                ];
            }
        } else {
            // Token invalide ou expiré
            setcookie('remember_token', '', time() - 3600, '/');
            $response = ['success' => false, 'message' => 'Session expirée'];
        }
    } catch (PDOException $e) {
        error_log('Erreur verify_session (remember): ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Erreur serveur'];
    }
} 
else {
    $response = ['success' => false, 'message' => 'Non connecté'];
}

echo json_encode($response);
exit();
