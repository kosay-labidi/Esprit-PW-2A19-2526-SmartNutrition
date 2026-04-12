<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');

$challengeController = new ChallengeController();
$challenges = $challengeController->listChallenges();

header('Content-Type: application/json');
echo json_encode($challenges);
?>
