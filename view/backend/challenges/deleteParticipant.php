<?php
require_once(__DIR__ . '/../../../controller/participant.controller.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_challenge = 0;
if (isset($_GET['id_challenge'])) {
    $id_challenge = (int)$_GET['id_challenge'];
} elseif (isset($_GET['challenge_id'])) {
    $id_challenge = (int)$_GET['challenge_id'];
}

$participantC = new ParticipantController();
$success = false;

if ($id > 0) {
    $success = $participantC->deleteParticipant($id);
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

if ($id_challenge > 0) {
    header('Location: showParticipant.php?id=' . $id_challenge);
    exit;
}

header('Location: showParticipant.php');
exit;
?>
