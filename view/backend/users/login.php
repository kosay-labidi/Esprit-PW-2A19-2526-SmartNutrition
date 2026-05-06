<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../Controller/user.controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$body = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$mdp = $body['mdp'] ?? '';
$rememberMe = !empty($body['remember_me']);
$recaptchaToken = $body['recaptcha_token'] ?? '';

// ========== VÉRIFICATION RECAPTCHA ==========
if (empty($recaptchaToken)) {
    echo json_encode(['success' => false, 'message' => 'CAPTCHA requis']);
    exit();
}

// VOTRE NOUVELLE CLÉ SECRÈTE
$secretKey = '6LeZW9wsAAAAAD70RL10eJdvwNFGrWpgwoioZ8ER';
$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

// Utiliser cURL pour plus de fiabilité
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => $secretKey,
    'response' => $recaptchaToken
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver pour localhost uniquement
$verifyResponse = curl_exec($ch);
curl_close($ch);

$verifyData = json_decode($verifyResponse, true);

if (!$verifyData['success']) {
    // Log l'erreur pour debug
    error_log('reCAPTCHA échec: ' . print_r($verifyData, true));
    echo json_encode(['success' => false, 'message' => 'CAPTCHA invalide, veuillez réessayer']);
    exit();
}
// ========== FIN VÉRIFICATION RECAPTCHA ==========

if ($email === '' || $mdp === '') {
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
    exit();
}

$userC = new UserController();
$result = $userC->login($email, $mdp);

// Ajout de la vérification du statut "suspendu"
if ($result['status'] === 'ok' && isset($result['data']['status'])) {
    if ($result['data']['status'] === 'suspendu') {
        echo json_encode([
            'success' => false, 
            'message' => 'suspended',
            'status' => 'suspendu',
            'contact_email' => 'gaiaalumen@gmail.com'
        ]);
        exit();
    }
}

switch ($result['status']) {
    case 'ok':
        $_SESSION['user'] = [
            'id_utilisateur' => $result['data']['id_utilisateur'],
            'nom' => $result['data']['nom'],
            'prenom' => $result['data']['prenom'],
            'email' => $result['data']['email'],
            'role' => $result['data']['role']
        ];

        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            $userId = $result['data']['id_utilisateur'];

            $userC->saveRememberToken($userId, $token, $expires);

            setcookie(
                'remember_token',
                $token,
                [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        echo json_encode(['success' => true, 'data' => $result['data']]);
        break;

    case 'wrong_password':
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
        break;

    case 'account_not_found':
        echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé']);
        break;
        
    case 'account_inactive':
        echo json_encode(['success' => false, 'message' => 'account_inactive']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>