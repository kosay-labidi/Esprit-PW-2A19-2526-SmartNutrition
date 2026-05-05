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

// Charger les variables depuis twitter.env (fichier local non commité)
$envFile = __DIR__ . '/../../../twitter.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value);
            if ($key === 'TWITTER_CLIENT_ID') define('TWITTER_CLIENT_ID', $value);
            if ($key === 'TWITTER_CLIENT_SECRET') define('TWITTER_CLIENT_SECRET', $value);
        }
    }
}

// Vérifier que les variables sont définies
if (!defined('TWITTER_CLIENT_ID') || empty(TWITTER_CLIENT_ID)) {
    die('Configuration Twitter OAuth manquante. Fichier twitter.env introuvable ou mal configuré.');
}

define('TWITTER_REDIRECT_URI', 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/auth/twitter-callback.php');
// Vérifier l'état (state) pour éviter les attaques CSRF
if (isset($_GET['state']) && isset($_SESSION['oauth_state']) && $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: ../../frontend/login.html?error=invalid_state');
    exit();
}

// Régénérer l'ID de session pour la sécurité
session_regenerate_id(true);

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $code_verifier = $_SESSION['code_verifier'];
    
    // Échanger le code contre un token d'accès
    $token_url = 'https://api.twitter.com/2/oauth2/token';
    
    // Créer les credentials pour Basic Auth
    $credentials = base64_encode(TWITTER_CLIENT_ID . ':' . TWITTER_CLIENT_SECRET);
    
    $post_data = [
        'code' => $code,
        'grant_type' => 'authorization_code',
        'client_id' => TWITTER_CLIENT_ID,
        'redirect_uri' => TWITTER_REDIRECT_URI,
        'code_verifier' => $code_verifier
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    
    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];
        
        // Récupérer les informations utilisateur
        $userinfo_url = 'https://api.twitter.com/2/users/me?user.fields=id,name,username,profile_image_url';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userinfo_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        $user_response = curl_exec($ch);
        curl_close($ch);
        
        $user_info = json_decode($user_response, true);
        
        if (isset($user_info['data'])) {
            $twitterUser = $user_info['data'];
            
            // Twitter ne fournit pas d'email, on crée un email fictif unique
            $email = $twitterUser['username'] . '@twitter.gaialumen.com';
            $nom = $twitterUser['name'] ?? 'Utilisateur';
            $prenom = $twitterUser['username'] ?? '';
            $photo = $twitterUser['profile_image_url'] ?? null;
            $provider_id = $twitterUser['id'];
            
            $db = config::getConnexion();
            
            // Vérifier si l'utilisateur existe déjà
            $checkStmt = $db->prepare("SELECT * FROM utilisateurs WHERE provider_id = :provider_id OR email = :email");
            $checkStmt->execute([
                'provider_id' => $provider_id,
                'email' => $email
            ]);
            $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingUser) {
                $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                
                $insertStmt = $db->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mdp, role, provider, provider_id, photo) 
                    VALUES (:nom, :prenom, :email, :mdp, 'utilisateur', 'twitter', :provider_id, :photo)
                ");
                $insertStmt->execute([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'mdp' => $randomPassword,
                    'provider_id' => $provider_id,
                    'photo' => $photo
                ]);
                
                $checkStmt->execute([
                    'provider_id' => $provider_id,
                    'email' => $email
                ]);
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
                
                // Nettoyer les variables de session OAuth
                unset($_SESSION['oauth_state']);
                unset($_SESSION['code_verifier']);
                
                $redirect = ($existingUser['role'] === 'admin') ? '../admin.html' : '../../frontend/dashboard.html';
                header('Location: ' . $redirect);
                exit();
            }
        }
    }
}

// Nettoyer les variables de session OAuth
unset($_SESSION['oauth_state']);
unset($_SESSION['code_verifier']);

header('Location: ../../frontend/login.html?error=twitter_auth_failed');
exit();
?>