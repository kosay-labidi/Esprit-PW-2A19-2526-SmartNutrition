<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';


if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $userController = new UserController();
        $result = $userController->deleteUser($id);
        if ($result) {
            header('Location: showUser.php?deleted=1');
        } else {
            header('Location: showUser.php?error=1');
        }
        exit();
    }
}

header('Location: showUser.php');
exit();
?>