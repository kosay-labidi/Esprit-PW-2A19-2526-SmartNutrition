<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: ../modules/health-admin.html?error=id_required');
    exit;
}

try {
    $ctrl->delete($id);
    header('Location: ../modules/health-admin.html?success=dossier_deleted');
} catch (Exception $e) {
    header('Location: ../modules/health-admin.html?error=' . urlencode($e->getMessage()));
}
exit;
?>
