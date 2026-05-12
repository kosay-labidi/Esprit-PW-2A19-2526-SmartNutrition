<?php
/**
 * Upload d'attachments chat (image/audio/video) pour être associés à un message.
 * POST multipart: file, challenge_id optionnel pour contrôler les vidéos de défi
 *
 * Réponse: { ok:true, attachment:{ type,url,mime,size_bytes,duration_ms? } }
 */
require_once(__DIR__ . '/_db.php');

if (session_status() === PHP_SESSION_NONE) session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    chat_json(['ok' => false, 'error' => 'POST only'], 405);
}

if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    chat_json(['ok' => false, 'error' => 'Fichier manquant'], 400);
}

$challengeId = (int)($_POST['challenge_id'] ?? $_GET['challenge_id'] ?? 0);

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['file']['tmp_name']) ?: '';
if ($mime === '' || $mime === 'application/octet-stream') {
    $fallback = strtolower((string)($_FILES['file']['type'] ?? ''));
    if ($fallback !== '') $mime = $fallback;
}

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
    'audio/mp3' => 'mp3',
    'audio/wav' => 'wav',
    'audio/x-wav' => 'wav',
];
$allowedVideo = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/ogg' => 'ogv',
    'video/quicktime' => 'mov',
    'video/x-m4v' => 'm4v',
];

$type = '';
$ext = '';
if (isset($allowedImages[$mime])) { $type = 'image'; $ext = $allowedImages[$mime]; }
elseif (isset($allowedAudio[$mime])) { $type = 'audio'; $ext = $allowedAudio[$mime]; }
elseif (isset($allowedVideo[$mime])) { $type = 'video'; $ext = $allowedVideo[$mime]; }
else {
    // Extension fallback for some MediaRecorder blobs reported as octet-stream.
    $nameRaw = strtolower((string)($_FILES['file']['name'] ?? ''));
    $mimeRaw = strtolower((string)$mime);
    $isVideoHint = str_contains($mimeRaw, 'video')
        || str_contains($nameRaw, 'video')
        || str_contains($nameRaw, 'camera')
        || str_contains($nameRaw, 'screen');
    if (str_ends_with($nameRaw, '.webm')) { $type = $isVideoHint ? 'video' : 'audio'; $ext = 'webm'; }
    elseif (str_ends_with($nameRaw, '.ogg')) { $type = 'audio'; $ext = 'ogg'; }
    elseif (str_ends_with($nameRaw, '.mp3')) { $type = 'audio'; $ext = 'mp3'; }
    elseif (str_ends_with($nameRaw, '.wav')) { $type = 'audio'; $ext = 'wav'; }
    elseif (str_ends_with($nameRaw, '.mp4')) { $type = 'video'; $ext = 'mp4'; }
    elseif (str_ends_with($nameRaw, '.mov')) { $type = 'video'; $ext = 'mov'; }
    elseif (str_ends_with($nameRaw, '.m4v')) { $type = 'video'; $ext = 'm4v'; }
}
if ($type === '' || $ext === '') {
    chat_json(['ok' => false, 'error' => 'Type non autorisé'], 400);
}

$db = chat_db();
$me = chat_get_me_from_session();
if ($type === 'video') {
    if ($challengeId > 0) {
        if (!chat_challenge_exists($db, $challengeId)) chat_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
        if (!chat_user_can_upload_challenge_media($challengeId, $me, $db)) {
            chat_json(['ok' => false, 'error' => 'Permission vidéo refusée'], 403);
        }
    }
}

if ($type === 'image' && @getimagesize($_FILES['file']['tmp_name']) === false) {
    chat_json(['ok' => false, 'error' => 'Image invalide'], 400);
}

$max = $type === 'video'
    ? (80 * 1024 * 1024)
    : ($type === 'audio' ? (8 * 1024 * 1024) : (3 * 1024 * 1024));
if ((int)$_FILES['file']['size'] > $max) {
    chat_json(['ok' => false, 'error' => $type === 'video' ? 'Vidéo trop volumineuse (max 80 Mo)' : 'Fichier trop volumineux'], 400);
}

$baseDir = __DIR__ . '/../uploads/chat';
$y = date('Y');
$m = date('m');
$sub = $type === 'video' ? 'videos' : ($type === 'audio' ? 'audio' : 'images');
$targetDir = $baseDir . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m;
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    chat_json(['ok' => false, 'error' => 'Impossible de créer le dossier'], 500);
}

$name = bin2hex(random_bytes(12)) . '.' . $ext;
$dest = $targetDir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    chat_json(['ok' => false, 'error' => 'Échec enregistrement'], 500);
}

// URL relative depuis view/frontend/*.html
$rel = '../backend/api/uploads/chat/' . $sub . '/' . $y . '/' . $m . '/' . $name;

chat_json([
    'ok' => true,
    'attachment' => [
        'type' => $type,
        'url' => $rel,
        'mime' => $mime,
        'size_bytes' => (int)$_FILES['file']['size'],
        'duration_ms' => null,
    ]
]);

