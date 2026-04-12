<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');

if (isset($_GET['id'])) {
    $challengeController = new ChallengeController();
    $challengeController->deleteChallenge((int)$_GET['id']);
    
    // Redirection vers le tableau d'administration
    header('Location: ../admin.html#challenges');
} else {
    echo "ID du défi manquant.";
}
?>
