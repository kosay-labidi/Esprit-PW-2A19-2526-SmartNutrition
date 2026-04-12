<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../Model/Challenge.php');

$challengeController = new ChallengeController();

if (
    isset($_POST["titre"]) &&
    isset($_POST["description"]) &&
    isset($_POST["type"]) &&
    isset($_POST["objectif"]) &&
    isset($_POST["valeur_cible"]) &&
    isset($_POST["date_debut"]) &&
    isset($_POST["date_fin"]) &&
    isset($_POST["statut"]) &&
    isset($_POST["streak_icon"])
) {
    // Création d'un nouvel objet Challenge
    $challenge = new Challenge(
        null,
        $_POST['titre'],
        $_POST['description'],
        $_POST['type'],
        $_POST['objectif'],
        (int)$_POST['valeur_cible'],
        $_POST['date_debut'],
        $_POST['date_fin'],
        $_POST['statut'],
        $_POST['streak_icon'],
        $_POST['image'] ?? ""
    );

    // Gérer l'ajout ou la mise à jour
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $challengeController->updateChallenge($challenge, (int)$_POST['id']);
    } else {
        $challengeController->addChallenge($challenge);
    }
    
    // Redirection vers le tableau d'affichage
    header('Location: ../admin.html#challenges');
}
?>
