<?php
session_start();

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

// Vérifier si les variables sont chargées
if (!defined('TWITTER_CLIENT_ID') || empty(TWITTER_CLIENT_ID)) {
    die('Fichier twitter.env non trouvé ou TWITTER_CLIENT_ID manquant. Chemin cherché: ' . $envFile);
}

define('TWITTER_REDIRECT_URI', 'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/auth/twitter-callback.php');
// Générer un code de vérification d'état (state) pour la sécurité
$_SESSION['oauth_state'] = bin2hex(random_bytes(32));
$_SESSION['code_verifier'] = bin2hex(random_bytes(32));

function getTwitterAuthUrl() {
    // Générer le code challenge (PKCE)
    $code_verifier = $_SESSION['code_verifier'];
    $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');

    $params = array(
        'response_type' => 'code',
        'client_id' => TWITTER_CLIENT_ID,
        'redirect_uri' => TWITTER_REDIRECT_URI,
        'scope' => 'users.read tweet.read offline.access',
        'state' => $_SESSION['oauth_state'],
        'code_challenge' => $code_challenge,
        'code_challenge_method' => 'S256'
    );
    return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
}

if (isset($_GET['action']) && $_GET['action'] === 'login') {
    header('Location: ' . getTwitterAuthUrl());
    exit();
}
?>