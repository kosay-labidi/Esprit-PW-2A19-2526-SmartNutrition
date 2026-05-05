<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';

$ctrl = new DossierMedicalController();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: /crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html?error=id_required');
    exit;
}

try {
    $ctrl->delete($id);
    header('Location: /crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html?success=dossier_deleted');
} catch (Exception $e) {
    header('Location: /crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html?error=' . urlencode($e->getMessage()));
}
exit;
?>
