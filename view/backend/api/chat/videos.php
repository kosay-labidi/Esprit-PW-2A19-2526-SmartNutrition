<?php
/**
 * Vidéos de défis.
 * GET  ?challenge_id=1              Liste les vidéos visibles du défi
 * POST multipart file, challenge_id Upload sécurisé et mise en attente
 * PATCH JSON {id,status}            Modération admin/créateur: approved|rejected
 */
require_once(__DIR__ . '/_db.php');

function chat_clean_video_title(string $title, string $fallback): string {
    $title = trim($title);
    if ($title === '') $title = $fallback;
    $title = preg_replace('/\s+/', ' ', $title) ?: $fallback;
    return substr($title, 0, 180);
}

function chat_video_row_to_api(array $row, bool $moderator): array {
    $out = [
        'id' => $row['id'],
        'challenge_id' => (int)$row['challenge_id'],
        'title' => $row['title'] ?: $row['original_name'],
        'url' => $row['url'],
        'mime' => $row['mime'],
        'size_bytes' => (int)$row['size_bytes'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
    ];
    if ($moderator) {
        $out['original_name'] = $row['original_name'];
        $out['uploader_user_id'] = $row['uploader_user_id'] !== null ? (int)$row['uploader_user_id'] : null;
        $out['uploader_participant_id'] = $row['uploader_participant_id'] !== null ? (int)$row['uploader_participant_id'] : null;
        $out['sha256'] = $row['sha256'];
    }
    return $out;
}

$db = chat_db();
$me = chat_get_me_from_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $challengeId = (int)($_GET['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);
    if (!chat_challenge_exists($db, $challengeId)) chat_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
    if (!chat_user_can_watch_challenge($challengeId, $me, $db)) {
        chat_json(['ok' => false, 'error' => 'Permission refusée'], 403);
    }

    $isModerator = chat_user_can_moderate_challenge($challengeId, $me, $db);
    $participant = chat_current_participant($challengeId, $me);
    $params = ['cid' => $challengeId];
    $where = "challenge_id = :cid AND status = 'approved'";
    if ($isModerator) {
        $where = "challenge_id = :cid";
    } elseif ($participant) {
        $where = "challenge_id = :cid AND (status = 'approved' OR uploader_participant_id = :pid)";
        $params['pid'] = (int)$participant['id'];
    }

    $q = $db->prepare("
        SELECT *
        FROM challenge_videos
        WHERE $where
        ORDER BY created_at DESC
        LIMIT 80
    ");
    $q->execute($params);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    chat_json([
        'ok' => true,
        'can_moderate' => $isModerator,
        'videos' => array_map(fn($r) => chat_video_row_to_api($r, $isModerator), $rows),
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $challengeId = (int)($_POST['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);
    if (!chat_challenge_exists($db, $challengeId)) chat_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
    if (!chat_user_can_upload_challenge_media($challengeId, $me, $db)) {
        chat_json(['ok' => false, 'error' => 'Permission upload refusée'], 403);
    }
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        chat_json(['ok' => false, 'error' => 'Fichier manquant'], 400);
    }

    $tmp = $_FILES['file']['tmp_name'];
    $size = (int)($_FILES['file']['size'] ?? 0);
    if ($size <= 0) chat_json(['ok' => false, 'error' => 'Fichier vide'], 400);
    if ($size > 120 * 1024 * 1024) chat_json(['ok' => false, 'error' => 'Vidéo trop volumineuse (max 120 Mo)'], 400);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
    ];
    $ext = $allowed[$mime] ?? '';
    $original = basename((string)($_FILES['file']['name'] ?? 'video'));
    $lower = strtolower($original);
    if ($ext === '' && str_ends_with($lower, '.mp4')) $ext = 'mp4';
    if ($ext === '' && str_ends_with($lower, '.webm')) $ext = 'webm';
    if ($ext === '' && str_ends_with($lower, '.mov')) $ext = 'mov';
    if ($ext === '') chat_json(['ok' => false, 'error' => 'Format vidéo non autorisé'], 400);

    $baseDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
    $targetRoot = $baseDir . DIRECTORY_SEPARATOR . 'chat' . DIRECTORY_SEPARATOR . 'videos';
    $y = date('Y');
    $m = date('m');
    $targetDir = $targetRoot . DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        chat_json(['ok' => false, 'error' => 'Impossible de créer le dossier vidéo'], 500);
    }

    $id = chat_uuid_v4();
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        chat_json(['ok' => false, 'error' => 'Échec enregistrement vidéo'], 500);
    }

    $participant = chat_current_participant($challengeId, $me);
    $isModerator = chat_user_can_moderate_challenge($challengeId, $me, $db);
    $status = $isModerator ? 'approved' : 'pending';
    $rel = '../backend/api/uploads/chat/videos/' . $y . '/' . $m . '/' . $name;
    $title = chat_clean_video_title((string)($_POST['title'] ?? ''), pathinfo($original, PATHINFO_FILENAME) ?: 'Vidéo du défi');
    $hash = hash_file('sha256', $dest);

    $ins = $db->prepare("
        INSERT INTO challenge_videos
          (id, challenge_id, uploader_user_id, uploader_participant_id, title, original_name,
           url, storage_path, mime, size_bytes, sha256, status, moderated_by_user_id, moderated_at)
        VALUES
          (:id, :cid, :uid, :pid, :title, :original, :url, :path, :mime, :size, :sha, :status, :mod_uid, :mod_at)
    ");
    $uid = (int)($me['id'] ?? 0);
    $ins->execute([
        'id' => $id,
        'cid' => $challengeId,
        'uid' => $uid > 0 ? $uid : null,
        'pid' => $participant ? (int)$participant['id'] : null,
        'title' => $title,
        'original' => substr($original, 0, 255),
        'url' => $rel,
        'path' => $dest,
        'mime' => $mime ?: 'video/' . $ext,
        'size' => $size,
        'sha' => $hash,
        'status' => $status,
        'mod_uid' => $isModerator && $uid > 0 ? $uid : null,
        'mod_at' => $isModerator ? date('Y-m-d H:i:s') : null,
    ]);

    chat_json([
        'ok' => true,
        'video' => [
            'id' => $id,
            'challenge_id' => $challengeId,
            'title' => $title,
            'url' => $rel,
            'mime' => $mime ?: 'video/' . $ext,
            'size_bytes' => $size,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ], 201);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'PATCH') {
    $body = chat_read_json_body();
    $id = trim((string)($body['id'] ?? ''));
    $status = trim((string)($body['status'] ?? ''));
    if ($id === '' || !in_array($status, ['approved', 'rejected'], true)) {
        chat_json(['ok' => false, 'error' => 'id/status invalides'], 400);
    }

    $q = $db->prepare("SELECT id, challenge_id FROM challenge_videos WHERE id = :id LIMIT 1");
    $q->execute(['id' => $id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) chat_json(['ok' => false, 'error' => 'Vidéo introuvable'], 404);
    $challengeId = (int)$row['challenge_id'];
    if (!chat_user_can_moderate_challenge($challengeId, $me, $db)) {
        chat_json(['ok' => false, 'error' => 'Permission modération refusée'], 403);
    }
    $uid = (int)($me['id'] ?? 0);
    $u = $db->prepare("
        UPDATE challenge_videos
        SET status = :status, moderated_by_user_id = :uid, moderated_at = NOW()
        WHERE id = :id
    ");
    $u->execute(['status' => $status, 'uid' => $uid > 0 ? $uid : null, 'id' => $id]);
    chat_json(['ok' => true, 'id' => $id, 'status' => $status]);
}

chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
