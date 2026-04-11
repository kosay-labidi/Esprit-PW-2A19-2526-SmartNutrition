<?php
require_once '../../../config.php';
require_once '../../../Controller/regime.controller.php';
$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;
if ($id) {
    $ctrl->delete($id);
}
header('Location: health-admin.html?msg=regime_deleted');
exit;
?>