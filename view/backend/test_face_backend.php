<?php
// test_face_backend.php
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Backend fonctionne', 'method' => $_SERVER['REQUEST_METHOD']]);
?>