<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";
?>
