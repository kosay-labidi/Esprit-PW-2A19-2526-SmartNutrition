<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';
$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../admin.html?error=invalid_id');
    exit;
}
try {
    $ctrl->delete($id);
    header('Location: ../admin.html?success=regime_deleted');
} catch (Exception $e) {
    header('Location: ../admin.html?error=' . urlencode($e->getMessage()));
}
exit;
?>
