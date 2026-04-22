<?php

require_once __DIR__ . '/../model/aliment.php';

$alimentModel = new Aliment();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $alimentModel->create($data);
            header("Location: ../view/BackOffice/bo_alimentlist.php?success=created");
        } elseif ($_POST['action'] === 'update') {
            $alimentModel->update($_POST['id_aliment'], $data);
            header("Location: ../view/BackOffice/bo_alimentlist.php?success=updated");
        }
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $alimentModel->delete($_GET['id']);
    header("Location: ../view/BackOffice/bo_alimentlist.php?success=deleted");
    exit;
}
?>