<?php
require_once(__DIR__ . '/../../../controller/participant.controller.php');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$id           = isset($_GET['id'])           ? (int)$_GET['id']           : 0;
$id_challenge = isset($_GET['id_challenge']) ? (int)$_GET['id_challenge'] : 0;

if ($id > 0) {
    $participantC = new ParticipantController();
    $success      = $participantC->deleteParticipant($id);
} else {
    $success = false;
}

if ($isAjax) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Redirection HTML normale
$redirect = 'showParticipant.php';
if ($id_challenge > 0) $redirect .= '?id_challenge=' . $id_challenge;
header('Location: ' . $redirect);
exit;
?>
