<?php
// Forcer les paramètres de session AVANT de démarrer
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/user.controller.php';

// Charger les variables depuis gaia.env (fichier local non commité)
$envFile = __DIR__ . '/../../../gaia.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value);
            if ($key === 'GOOGLE_CLIENT_ID') define('GOOGLE_CLIENT_ID', $value);
            if ($key === 'GOOGLE_CLIENT_SECRET') define('GOOGLE_CLIENT_SECRET', $value);
        }
    }
}

// Vérifier que les variables sont définies
if (!defined('GOOGLE_CLIENT_ID') || empty(GOOGLE_CLIENT_ID)) {
    die('Configuration Google OAuth manquante. Fichier gaia.env introuvable ou mal configuré.');
}

define('GOOGLE_REDIRECT_URI', 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/auth/google-callback.php');

// Régénérer l'ID de session pour la sécurité
session_regenerate_id(true);

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (isset($token_data['access_token'])) {
        $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfo_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $user_response = curl_exec($ch);
        curl_close($ch);
        
        $user_info = json_decode($user_response, true);
        
        if (isset($user_info['email'])) {
            $db = config::getConnexion();
            
            $checkStmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
            $checkStmt->execute(['email' => $user_info['email']]);
            $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingUser) {
                $prenom = $user_info['given_name'] ?? '';
                $nom = $user_info['family_name'] ?? '';
                
                if (empty($prenom) && empty($nom) && isset($user_info['name'])) {
                    $parts = explode(' ', $user_info['name'], 2);
                    $prenom = $parts[0];
                    $nom = $parts[1] ?? '';
                }
                
                if (empty($prenom)) $prenom = substr($user_info['email'], 0, strpos($user_info['email'], '@'));
                if (empty($nom)) $nom = 'Utilisateur';
                
                $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $insertStmt = $db->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mdp, role, provider, provider_id, photo) 
                    VALUES (:nom, :prenom, :email, :mdp, 'utilisateur', 'google', :provider_id, :photo)
                ");
                $insertStmt->execute([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $user_info['email'],
                    'mdp' => $randomPassword,
                    'provider_id' => $user_info['id'],
                    'photo' => $user_info['picture'] ?? null
                ]);
                
                $checkStmt->execute(['email' => $user_info['email']]);
                $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($existingUser) {
                $_SESSION['user'] = [
                    'id_utilisateur' => $existingUser['id_utilisateur'],
                    'nom' => $existingUser['nom'] ?: 'Utilisateur',
                    'prenom' => $existingUser['prenom'] ?: '',
                    'email' => $existingUser['email'],
                    'role' => $existingUser['role'] ?: 'utilisateur'
                ];
                
                $redirect = ($existingUser['role'] === 'admin') ? '../admin.html' : '../../frontend/dashboard.html';
                header('Location: ' . $redirect);
                exit();
            }
        }
    }
}

header('Location: ../../frontend/login.html?error=google_auth_failed');
exit();
?>