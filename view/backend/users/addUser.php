<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../config.php';

$userController = new UserController();

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit();
}

// Détecter si la requête vient de l'admin (via le flag is_admin)
$isAdminRequest = false;

// Lire le corps de la requête pour détecter le flag is_admin
$inputData = [];
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $inputData = json_decode($rawInput, true) ?? [];
    if (isset($inputData['is_admin']) && $inputData['is_admin'] === true) {
        $isAdminRequest = true;
    }
}

// Vérifier aussi via le referer si la requête vient de l'admin
if (!$isAdminRequest && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin.html') !== false) {
    $isAdminRequest = true;
}

// ==================== REQUÊTE JSON (API) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($contentType, 'application/json')) {

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Credentials: true');

    $nom = trim($inputData['nom'] ?? '');
    $prenom = trim($inputData['prenom'] ?? '');
    $email = trim($inputData['email'] ?? '');
    $mdp = $inputData['mdp'] ?? '';
    $role = trim($inputData['role'] ?? 'utilisateur');
    $recaptchaToken = $inputData['recaptcha_token'] ?? '';

    $errors = [];

    // ========== VÉRIFICATION RECAPTCHA ==========
    // Le CAPTCHA est requis UNIQUEMENT pour les inscriptions publiques (non-admin)
    if (!$isAdminRequest) {
        if (empty($recaptchaToken)) {
            echo json_encode(['success' => false, 'message' => 'CAPTCHA requis. Veuillez cocher la case.']);
            exit();
        }

        $secretKey = '6LeZW9wsAAAAAD70RL10eJdvwNFGrWpgwoioZ8ER';
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $secretKey,
            'response' => $recaptchaToken
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $verifyResponse = curl_exec($ch);
        curl_close($ch);

        $verifyData = json_decode($verifyResponse, true);

        if (!$verifyData['success']) {
            error_log('reCAPTCHA échec addUser: ' . print_r($verifyData, true));
            echo json_encode(['success' => false, 'message' => 'CAPTCHA invalide. Veuillez réessayer.']);
            exit();
        }
    }
    // ========== FIN VÉRIFICATION RECAPTCHA ==========

    // Validation des champs
    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom ne doit pas contenir de chiffres.';
    }

    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    } elseif (preg_match('/\d/', $prenom)) {
        $errors[] = 'Le prénom ne doit pas contenir de chiffres.';
    }

    if ($email === '') {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if ($mdp === '') {
        $errors[] = 'Le mot de passe est requis.';
    } elseif (strlen($mdp) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) {
        $role = 'utilisateur';
    }

    if ($errors) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit();
    }

    // Vérifier si l'email existe déjà
    if ($userController->emailExists($email)) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
        exit();
    }

    // Créer l'utilisateur
    try {
        $userModel = new User();
        $userModel->setNom($nom);
        $userModel->setPrenom($prenom);
        $userModel->setEmail($email);
        $userModel->setMdp($mdp);
        $userModel->setRole($role);
    } catch (InvalidArgumentException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }

    // Ajouter l'utilisateur
    if ($userController->addUser($userModel)) {
        echo json_encode(['success' => true, 'message' => 'Compte créé avec succès !']);
    } else {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la création du compte (email peut-être déjà utilisé)."]);
    }
    exit();
}

// ==================== TRAITEMENT DU FORMULAIRE HTML STANDARD ====================
$errors = [];
$success = isset($_GET['success']) && $_GET['success'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $role = trim($_POST['role'] ?? 'utilisateur');
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';

    // ========== VÉRIFICATION RECAPTCHA POUR FORMULAIRE HTML ==========
    // Pour le formulaire HTML public, le CAPTCHA est TOUJOURS requis
    if (empty($recaptchaToken)) {
        $errors[] = 'CAPTCHA requis. Veuillez cocher la case.';
    } else {
        $secretKey = '6LeZW9wsAAAAAD70RL10eJdvwNFGrWpgwoioZ8ER';
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $secretKey,
            'response' => $recaptchaToken
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $verifyResponse = curl_exec($ch);
        curl_close($ch);

        $verifyData = json_decode($verifyResponse, true);

        if (!$verifyData['success']) {
            $errors[] = 'CAPTCHA invalide. Veuillez réessayer.';
        }
    }
    // ========== FIN VÉRIFICATION RECAPTCHA ==========

    // Validation des champs
    if ($nom === '') {
        $errors[] = 'Le nom est requis.';
    } elseif (preg_match('/\d/', $nom)) {
        $errors[] = 'Le nom ne doit pas contenir de chiffres.';
    }

    if ($prenom === '') {
        $errors[] = 'Le prénom est requis.';
    } elseif (preg_match('/\d/', $prenom)) {
        $errors[] = 'Le prénom ne doit pas contenir de chiffres.';
    }

    if ($email === '') {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if ($mdp === '') {
        $errors[] = 'Le mot de passe est requis.';
    } elseif (strlen($mdp) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }

    $rolesValides = ['utilisateur', 'nutritionniste', 'ecologiste', 'admin'];
    if (!in_array($role, $rolesValides, true)) {
        $role = 'utilisateur';
    }

    if ($errors === []) {
        if ($userController->emailExists($email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            try {
                $userModel = new User();
                $userModel->setNom($nom);
                $userModel->setPrenom($prenom);
                $userModel->setEmail($email);
                $userModel->setMdp($mdp);
                $userModel->setRole($role);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }

            if ($errors === [] && $userController->addUser($userModel)) {
                header('Location: addUser.php?success=1');
                exit();
            }
            if ($errors === []) {
                $errors[] = "Erreur lors de l'ajout.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un utilisateur - GaiaLumen</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0A120E 0%, #1A2E22 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 550px;
            width: 100%;
            background: rgba(15, 30, 20, 0.92);
            backdrop-filter: blur(24px);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(107, 78, 158, 0.3);
        }
        
        h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            background: linear-gradient(135deg, #F2E8CF 0%, #D4AF37 50%, #4A9FD8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #8A9A82;
            font-size: 0.85rem;
            margin-bottom: 32px;
        }
        
        .error-box {
            background: rgba(231, 76, 60, 0.12);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        
        .error-box ul {
            margin: 0;
            padding-left: 20px;
            color: #e74c3c;
            font-size: 0.85rem;
        }
        
        .error-box li {
            margin: 4px 0;
        }
        
        .success-box {
            background: rgba(46, 204, 113, 0.12);
            border: 1px solid rgba(46, 204, 113, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            color: #2ecc71;
            text-align: center;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #F2E8CF;
            opacity: 0.85;
            margin-bottom: 8px;
        }
        
        input, select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(31, 61, 43, 0.4);
            border: 1.5px solid rgba(107, 78, 158, 0.3);
            border-radius: 16px;
            color: #F2E8CF;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #6B4E9E;
            background: rgba(31, 61, 43, 0.6);
            box-shadow: 0 0 20px rgba(107, 78, 158, 0.25);
        }
        
        input::placeholder {
            color: rgba(242, 232, 207, 0.35);
        }
        
        .recaptcha-container {
            display: flex;
            justify-content: center;
            margin: 24px 0;
            transform: scale(0.95);
            transform-origin: center;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6B4E9E 0%, #4A9FD8 100%);
            border: none;
            border-radius: 16px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(107, 78, 158, 0.5);
        }
        
        button:disabled {
            opacity: 0.7;
            transform: none;
            cursor: not-allowed;
        }
        
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .back-link a {
            color: #4A9FD8;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #8B6FC9;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 28px 20px;
            }
            .recaptcha-container {
                transform: scale(0.85);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>➕ Ajouter un utilisateur</h1>
        <div class="subtitle">Créer un nouveau compte GaiaLumen</div>

        <?php if ($errors !== []): ?>
            <div class="error-box">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">✅ Utilisateur ajouté avec succès !</div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Farhani">
            </div>
            
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ahmed">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="exemple@email.com">
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="mdp" placeholder="••••••••">
            </div>
            
            <div class="form-group">
                <label>Rôle</label>
                <select name="role">
                    <option value="utilisateur"    <?= (($_POST['role'] ?? '') === 'utilisateur')    ? 'selected' : '' ?>>👤 Utilisateur</option>
                    <option value="nutritionniste" <?= (($_POST['role'] ?? '') === 'nutritionniste') ? 'selected' : '' ?>>🥗 Nutritionniste</option>
                    <option value="ecologiste"     <?= (($_POST['role'] ?? '') === 'ecologiste')     ? 'selected' : '' ?>>🌍 Écologiste</option>
                    <option value="admin"          <?= (($_POST['role'] ?? '') === 'admin')          ? 'selected' : '' ?>>👑 Administrateur</option>
                </select>
            </div>
            
            <!-- Google reCAPTCHA v2 - Uniquement pour les inscriptions publiques -->
            <div class="recaptcha-container">
                <div class="g-recaptcha" data-sitekey="6LeZW9wsAAAAAKELGv3xyzmXROZZGcVxcbGxoiPg" data-theme="dark"></div>
            </div>
            
            <button type="submit">✨ Ajouter l'utilisateur</button>
        </form>
        
        <div class="back-link">
            <a href="../users/list_users.php">📋 Voir tous les utilisateurs</a>
        </div>
    </div>
</body>
</html>