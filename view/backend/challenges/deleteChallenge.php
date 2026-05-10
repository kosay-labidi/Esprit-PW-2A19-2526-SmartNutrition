<?php
require_once(__DIR__ . '/../../../controller/challenge.controller.php');

if (isset($_GET['id'])) {
    $challengeC = new ChallengeController();
    $challengeC->deleteChallenge($_GET['id']);
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

header('Location: listChallenges.php');
exit;
?>
