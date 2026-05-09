<?php
session_start();

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

// Définir des valeurs par défaut si gaia.env n'existe pas
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', '');

// Mettre à jour l'URL de redirection
define('GOOGLE_REDIRECT_URI', 'http://localhost/Mainn/view/backend/auth/google-callback.php');

function getGoogleAuthUrl() {
    if (empty(GOOGLE_CLIENT_ID)) {
        return 'javascript:alert("Google auth non configuré. Veuillez utiliser le formulaire de connexion standard.");';
    }
    $params = array(
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    );
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

if (isset($_GET['action']) && $_GET['action'] === 'login') {
    if (empty(GOOGLE_CLIENT_ID)) {
        header('Location: ../../../view/frontend/login.html?error=google_auth_not_configured');
        exit();
    }
    header('Location: ' . getGoogleAuthUrl());
    exit();
}
?>