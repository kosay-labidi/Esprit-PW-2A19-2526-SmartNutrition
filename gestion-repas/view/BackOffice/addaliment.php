<?php
// view/BackOffice/addaliment.php
// Ce fichier est appelé par le formulaire du modal
require_once __DIR__ . '/../../controller/alimentcontroller.php';
// Le traitement est déjà fait dans le controller
// Ce fichier peut rester vide ou rediriger
header("Location: alimentlist.php");
exit;
?>