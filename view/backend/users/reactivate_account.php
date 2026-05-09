<?php
// view/backend/users/reactivate_account.php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../Controller/user.controller.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email requis']);
    exit();
}

$userC = new UserController();
$user = $userC->getUserByEmail($email);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé avec cet email']);
    exit();
}

// Vérifier si c'est un compte OAuth
$isOAuth = !empty($user['provider']) || empty($user['mdp']);

if (!$isOAuth && empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe requis']);
    exit();
}

// Vérifier le mot de passe pour les comptes normaux
if (!$isOAuth && !password_verify($password, $user['mdp'])) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
    exit();
}

// Vérifier si le compte est déjà actif
if ($user['status'] === 'actif') {
    echo json_encode(['success' => false, 'message' => 'Votre compte est déjà actif.']);
    exit();
}

// Réactiver le compte
try {
    $db = config::getConnexion();
    $stmt = $db->prepare("UPDATE utilisateurs SET status = 'actif', date_mise_a_jour = NOW() WHERE id_utilisateur = :id");
    $result = $stmt->execute([':id' => $user['id_utilisateur']]);
    
    if ($result) {
        // Détruire l'ancienne session si elle existe
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Votre compte a été réactivé avec succès ! Vous pouvez maintenant vous connecter.',
            'redirect' => 'login.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la réactivation']);
    }
} catch (PDOException $e) {
    error_log("Erreur reactivation: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique']);
}
?>