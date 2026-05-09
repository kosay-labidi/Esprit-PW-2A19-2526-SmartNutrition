<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

// CORRECTION : supprime et redirige vers la liste (pas de JSON)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listDemandeplanning.php');
    exit;
}

$id         = (int) $_GET['id'];
$controller = new DemandeplanningController();

// deleteDemande() supprime en cascade planning + sportsommeil + demande
$deleted = $controller->deleteDemande($id);

if ($deleted) {
    header('Location: listDemandeplanning.php?deleted=1');
} else {
    header('Location: listDemandeplanning.php?error=1');
}
exit;