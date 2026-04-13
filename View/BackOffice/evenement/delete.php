<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Controller/EvenementController.php';

if (isset($_GET['id'])) {
    $evenementC = new EvenementController();
    $evenementC->deleteEvenement($_GET['id']);
}

header("Location: list.php");
exit();
?>