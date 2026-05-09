<?php
require_once(__DIR__ . '/_db.php');

$id = trim((string)($_GET['id'] ?? ''));
if ($id === '') chat_json(['ok' => false, 'error' => 'id required'], 400);

$me = chat_get_me_from_session();
$uid = (int)($me['id'] ?? 0);
$email = strtolower(trim((string)($me['email'] ?? '')));

function can_edit_row(array $row, int $uid, string $email): bool {
    if (!empty($row['deleted_at'])) return false;
    if ($uid > 0 && (int)($row['user_id'] ?? 0) === $uid) return true;
    // fallback via participant email on same challenge
    if ($email !== '' && !empty($row['participant_id']) && !empty($row['challenge_id'])) {
        $p = chat_find_participant_for_user((int)$row['challenge_id'], $email);
        if ($p && (int)$p['id'] === (int)$row['participant_id']) return true;
    }
    return false;
}

// Load row with challenge_id (via thread)
$db = chat_db();
$q = $db->prepare("
    SELECT m.id, m.thread_id, m.participant_id, m.user_id, m.author_name, m.body, m.created_at, m.updated_at, m.deleted_at,
           t.challenge_id
    FROM chat_messages m
    JOIN chat_threads t ON t.id = m.thread_id
    WHERE m.id = :id
    LIMIT 1
");
$q->execute(['id' => $id]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row) chat_json(['ok' => false, 'error' => 'not found'], 404);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'PATCH') {
    $body = chat_read_json_body();
    $newBody = trim((string)($body['body'] ?? ''));
    if ($newBody === '') chat_json(['ok' => false, 'error' => 'body required'], 400);
    if (!can_edit_row($row, $uid, $email)) chat_json(['ok' => false, 'error' => 'forbidden'], 403);

    $u = $db->prepare("UPDATE chat_messages SET body=:b, updated_at=NOW() WHERE id=:id");
    $u->execute(['b' => $newBody, 'id' => $id]);
    chat_json(['ok' => true, 'id' => $id, 'body' => $newBody]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'DELETE') {
    if (!can_edit_row($row, $uid, $email)) chat_json(['ok' => false, 'error' => 'forbidden'], 403);
    $u = $db->prepare("UPDATE chat_messages SET deleted_at=NOW(), body=NULL WHERE id=:id");
    $u->execute(['id' => $id]);
    chat_json(['ok' => true, 'id' => $id]);
}

chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);

