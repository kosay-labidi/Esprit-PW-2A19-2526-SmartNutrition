<?php
require_once(__DIR__ . '/../../../controller/user.controller.php');
require_once(__DIR__ . '/../../../Model/User.php');
require_once(__DIR__ . '/../../../config.php');

$userC = new UserController();

// Traitement du formulaire de modification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Données POST reçues: " . print_r($_POST, true));
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id < 1) {
        echo '<html><body><script>window.parent.showToast("Erreur", "ID invalide", "error");</script></body></html>';
        exit();
    }
    
    $existingUser = $userC->getUserById($id);
    error_log("Utilisateur trouvé: " . print_r($existingUser, true));
    
    if ($existingUser) {
        $user = new User(
            $id,
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['email'],
            $existingUser['mdp'],
            $_POST['role']
        );
        
        error_log("User object: id=" . $user->getIdUtilisateur() . " nom=" . $user->getNom());
        
        $result = $userC->updateUser($user);
        
        if ($result) {
            echo '<html><body><script>window.parent.handleUpdateResponse();</script></body></html>';
        } else {
            echo '<html><body><script>window.parent.showToast("Erreur", "SQL échoué", "error");</script></body></html>';
        }
    } else {
        echo '<html><body><script>window.parent.showToast("Erreur", "Utilisateur non trouvé", "error");</script></body></html>';
    }
    exit();
}

// Récupération des informations de l'utilisateur à modifier (GET)
if (isset($_GET['id'])) {
    $userData = $userC->getUserById((int)$_GET['id']);
    if (!$userData) {
        die("Utilisateur non trouvé");
    }
} else {
    header('Location: showUser.php');
    exit();
}
?>

<!-- Formulaire HTML pur avec les données -->
<form style="display:none;">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['id_utilisateur']); ?>">
    <input type="text" name="nom" value="<?php echo htmlspecialchars($userData['nom']); ?>">
    <input type="text" name="prenom" value="<?php echo htmlspecialchars($userData['prenom']); ?>">
    <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>">
    <select name="role">
        <option value="utilisateur" <?php echo ($userData['role'] == 'utilisateur') ? 'selected' : ''; ?>>Utilisateur</option>
        <option value="nutritionniste" <?php echo ($userData['role'] == 'nutritionniste') ? 'selected' : ''; ?>>Nutritionniste</option>
        <option value="ecologiste" <?php echo ($userData['role'] == 'ecologiste') ? 'selected' : ''; ?>>Écologiste</option>
        <option value="admin" <?php echo ($userData['role'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
    </select>
</form>