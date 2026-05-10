<?php
/**
 * Live streaming des défis.
 * GET    ?challenge_id=1       Lives visibles du défi
 * POST   JSON                  Demande de live: challenge_id,title,stream_url,description,provider
 * PATCH  JSON {id,action}      approve|reject|start|end (admin/créateur pour approve/reject, demandeur/modérateur pour start/end)
 */
require_once(__DIR__ . '/_db.php');

function chat_live_row_to_api(array $row, bool $moderator, bool $canControl = false): array {
    $out = [
        'id' => $row['id'],
        'challenge_id' => (int)$row['challenge_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'stream_url' => $row['stream_url'],
        'provider' => $row['provider'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'approved_at' => $row['approved_at'],
        'started_at' => $row['started_at'],
        'ended_at' => $row['ended_at'],
        'can_control' => $canControl,
    ];
    if ($moderator) {
        $out['requested_by_user_id'] = $row['requested_by_user_id'] !== null ? (int)$row['requested_by_user_id'] : null;
        $out['requested_by_participant_id'] = $row['requested_by_participant_id'] !== null ? (int)$row['requested_by_participant_id'] : null;
        $out['approved_by_user_id'] = $row['approved_by_user_id'] !== null ? (int)$row['approved_by_user_id'] : null;
    }
    return $out;
}

function chat_valid_stream_url(string $url): bool {
    if (strlen($url) > 700) return false;
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return false;
    return in_array(strtolower($parts['scheme']), ['https', 'http', 'rtmp', 'rtmps'], true);
}

$db = chat_db();
$me = chat_get_me_from_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $challengeId = (int)($_GET['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);
    if (!chat_challenge_exists($db, $challengeId)) chat_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
    if (!chat_user_can_watch_challenge($challengeId, $me, $db)) {
        chat_json(['ok' => false, 'error' => 'Permission lecture live refusée'], 403);
    }

    $isModerator = chat_user_can_moderate_challenge($challengeId, $me, $db);
    $participant = chat_current_participant($challengeId, $me);
    $params = ['cid' => $challengeId];
    $where = "challenge_id = :cid AND status IN ('approved','live','ended')";
    if ($isModerator) {
        $where = "challenge_id = :cid";
    } elseif ($participant) {
        $where = "challenge_id = :cid AND (status IN ('approved','live','ended') OR requested_by_participant_id = :pid)";
        $params['pid'] = (int)$participant['id'];
    }

    $q = $db->prepare("
        SELECT *
        FROM challenge_live_streams
        WHERE $where
        ORDER BY FIELD(status, 'live','approved','pending','ended','rejected'), created_at DESC
        LIMIT 50
    ");
    $q->execute($params);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $uid = (int)($me['id'] ?? 0);
    $currentParticipantId = $participant ? (int)$participant['id'] : 0;
    $lives = array_map(function ($r) use ($isModerator, $uid, $currentParticipantId) {
        $isRequester = ($uid > 0 && (int)($r['requested_by_user_id'] ?? 0) === $uid)
            || ($currentParticipantId > 0 && (int)($r['requested_by_participant_id'] ?? 0) === $currentParticipantId);
        return chat_live_row_to_api($r, $isModerator, $isModerator || $isRequester);
    }, $rows);

    chat_json([
        'ok' => true,
        'can_moderate' => $isModerator,
        'can_request_live' => chat_user_can_upload_challenge_media($challengeId, $me, $db),
        'lives' => $lives,
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $body = chat_read_json_body();
    $challengeId = (int)($body['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);
    if (!chat_challenge_exists($db, $challengeId)) chat_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
    if (!chat_user_can_upload_challenge_media($challengeId, $me, $db)) {
        chat_json(['ok' => false, 'error' => 'Permission demande live refusée'], 403);
    }

    $title = trim((string)($body['title'] ?? ''));
    $streamUrl = trim((string)($body['stream_url'] ?? ''));
    $description = trim((string)($body['description'] ?? ''));
    $provider = trim((string)($body['provider'] ?? 'external'));
    if ($title === '') chat_json(['ok' => false, 'error' => 'title required'], 400);
    if (!chat_valid_stream_url($streamUrl)) chat_json(['ok' => false, 'error' => 'stream_url invalide'], 400);
    $title = substr(preg_replace('/\s+/', ' ', $title) ?: $title, 0, 180);
    $provider = preg_replace('/[^a-z0-9_-]/i', '', $provider) ?: 'external';
    $provider = substr($provider, 0, 60);

    $participant = chat_current_participant($challengeId, $me);
    $isModerator = chat_user_can_moderate_challenge($challengeId, $me, $db);
    $status = $isModerator ? 'approved' : 'pending';
    $uid = (int)($me['id'] ?? 0);
    $id = chat_uuid_v4();

    $ins = $db->prepare("
        INSERT INTO challenge_live_streams
          (id, challenge_id, requested_by_user_id, requested_by_participant_id, title, description,
           stream_url, provider, status, approved_by_user_id, approved_at)
        VALUES
          (:id, :cid, :uid, :pid, :title, :description, :url, :provider, :status, :approved_uid, :approved_at)
    ");
    $ins->execute([
        'id' => $id,
        'cid' => $challengeId,
        'uid' => $uid > 0 ? $uid : null,
        'pid' => $participant ? (int)$participant['id'] : null,
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'url' => $streamUrl,
        'provider' => $provider,
        'status' => $status,
        'approved_uid' => $isModerator && $uid > 0 ? $uid : null,
        'approved_at' => $isModerator ? date('Y-m-d H:i:s') : null,
    ]);

    chat_json([
        'ok' => true,
        'live' => [
            'id' => $id,
            'challenge_id' => $challengeId,
            'title' => $title,
            'description' => $description,
            'stream_url' => $streamUrl,
            'provider' => $provider,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ], 201);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'PATCH') {
    $body = chat_read_json_body();
    $id = trim((string)($body['id'] ?? ''));
    $action = trim((string)($body['action'] ?? ''));
    if ($id === '' || !in_array($action, ['approve', 'reject', 'start', 'end'], true)) {
        chat_json(['ok' => false, 'error' => 'id/action invalides'], 400);
    }

    $q = $db->prepare("SELECT * FROM challenge_live_streams WHERE id = :id LIMIT 1");
    $q->execute(['id' => $id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) chat_json(['ok' => false, 'error' => 'Live introuvable'], 404);

    $challengeId = (int)$row['challenge_id'];
    $isModerator = chat_user_can_moderate_challenge($challengeId, $me, $db);
    $uid = (int)($me['id'] ?? 0);
    $participant = chat_current_participant($challengeId, $me);
    $isRequester = ($uid > 0 && (int)($row['requested_by_user_id'] ?? 0) === $uid)
        || ($participant && (int)($row['requested_by_participant_id'] ?? 0) === (int)$participant['id']);

    if (($action === 'approve' || $action === 'reject') && !$isModerator) {
        chat_json(['ok' => false, 'error' => 'Validation réservée admin/créateur'], 403);
    }
    if (($action === 'start' || $action === 'end') && !$isModerator && !$isRequester) {
        chat_json(['ok' => false, 'error' => 'Action live refusée'], 403);
    }

    $current = (string)$row['status'];
    if ($action === 'start' && !in_array($current, ['approved', 'live'], true)) {
        chat_json(['ok' => false, 'error' => 'Le live doit être approuvé avant lancement'], 409);
    }

    $sql = '';
    $params = ['id' => $id];
    if ($action === 'approve') {
        $sql = "UPDATE challenge_live_streams SET status='approved', approved_by_user_id=:uid, approved_at=NOW() WHERE id=:id";
        $params['uid'] = $uid > 0 ? $uid : null;
    } elseif ($action === 'reject') {
        $sql = "UPDATE challenge_live_streams SET status='rejected', approved_by_user_id=:uid, approved_at=NOW() WHERE id=:id";
        $params['uid'] = $uid > 0 ? $uid : null;
    } elseif ($action === 'start') {
        $sql = "UPDATE challenge_live_streams SET status='live', started_at=COALESCE(started_at, NOW()) WHERE id=:id";
    } else {
        $sql = "UPDATE challenge_live_streams SET status='ended', ended_at=COALESCE(ended_at, NOW()) WHERE id=:id";
    }
    $u = $db->prepare($sql);
    $u->execute($params);

    $newStatus = ['approve' => 'approved', 'reject' => 'rejected', 'start' => 'live', 'end' => 'ended'][$action];
    chat_json(['ok' => true, 'id' => $id, 'status' => $newStatus]);
}

chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
