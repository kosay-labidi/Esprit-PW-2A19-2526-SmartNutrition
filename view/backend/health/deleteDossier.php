<?php
require_once '../../../config.php';
require_once '../../../Controller/dossierMedical.controller.php';
$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;
if ($id) $ctrl->delete($id);
header('Location: health-admin.html?msg=dossier_deleted');
exit;
?>