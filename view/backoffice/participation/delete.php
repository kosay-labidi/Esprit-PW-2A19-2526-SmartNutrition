<?php
require_once __DIR__ . '/../../../config.php';
include __DIR__ . '/../../../controller/ParticipationController.php';

if (isset($_GET['id'])) {
    $participationC = new ParticipationController();
    $participationC->deleteParticipation($_GET['id']);
    header("Location: list.php?success=deleted");
    exit();
} else {
    header("Location: list.php?error=missing_id");
    exit();
}
?>