<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../controller/ParticipationController.php';

$retour = isset($_GET['id_event']) ? 'list.php?id_event=' . (int)$_GET['id_event'] : 'list.php';

if (isset($_GET['id'])) {
    $participationC = new ParticipationController();
    $participationC->deleteParticipation($_GET['id']);
    $sep = strpos($retour, '?') !== false ? '&' : '?';
    header("Location: {$retour}{$sep}success=deleted");
    exit();
} else {
    $sep = strpos($retour, '?') !== false ? '&' : '?';
    header("Location: {$retour}{$sep}error=missing_id");
    exit();
}
?>