<?php
/**
 * Upload de média pour le chat (image/audio) (client → stockage serveur).
 * POST multipart champ: file
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
if (ob_get_length()) {
    ob_clean();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Fichier manquant']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['file']['tmp_name']) ?: '';
$allowedImages = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
$allowedAudio = [
    'audio/webm' => 'webm',
    'audio/ogg' => 'ogg',
    'audio/mpeg' => 'mp3',
    'audio/wav' => 'wav',
];

$type = '';
$ext = '';
if (isset($allowedImages[$mime])) {
    $type = 'image';
    $ext = $allowedImages[$mime];
} elseif (isset($allowedAudio[$mime])) {
    $type = 'audio';
    $ext = $allowedAudio[$mime];
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Type non autorisé (image: jpg/png/webp/gif, audio: webm/ogg/mp3/wav)']);
    exit;
}

$max = $type === 'audio' ? (8 * 1024 * 1024) : (3 * 1024 * 1024);
if ((int)$_FILES['file']['size'] > $max) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $type === 'audio' ? 'Fichier trop volumineux (max 8 Mo)' : 'Fichier trop volumineux (max 3 Mo)']);
    exit;
}

$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'chat';
$y = date('Y');
$m = date('m');
$sub = $type === 'audio' ? 'audio' : 'images';
$targetDir = $baseDir . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m;
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Impossible de créer le dossier']);
    exit;
}

$name = bin2hex(random_bytes(12)) . '.' . $ext;
$dest = $targetDir . DIRECTORY_SEPARATOR . $name;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Échec enregistrement']);
    exit;
}

// URL relative depuis view/frontend/*.html
$rel = '../backend/api/uploads/chat/' . $sub . '/' . $y . '/' . $m . '/' . $name;

echo json_encode(['ok' => true, 'type' => $type, 'url' => $rel, 'mime' => $mime, 'size_bytes' => (int)$_FILES['file']['size']]);
