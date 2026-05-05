<?php
session_start();

// Configuration Google OAuth - Utiliser des variables d'environnement
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/auth/google-callback.php');

function getGoogleAuthUrl() {
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
    header('Location: ' . getGoogleAuthUrl());
    exit();
}
?>