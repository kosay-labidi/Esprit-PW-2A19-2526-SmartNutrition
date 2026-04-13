<?php
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        $userController = new UserController();
        $result = $userController->deleteUser($id);
        
        if ($result) {
            // Redirection vers showUser.php avec message de succès
            header('Location: showUser.php?deleted=1');
            exit();
        } else {
            header('Location: showUser.php?error=1');
            exit();
        }
    }
}

// Si ID invalide
header('Location: showUser.php');
exit();
?>