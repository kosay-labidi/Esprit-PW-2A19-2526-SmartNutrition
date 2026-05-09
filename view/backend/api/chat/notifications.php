<?php
require_once(__DIR__ . '/_db.php');

$me = chat_get_me_from_session();
$email = strtolower(trim((string)($me['email'] ?? '')));

function chat_current_participant_ids(string $email, ?int $challengeId = null): array {
    if ($email === '') return [];
    $db = chat_db();
    $sql = "SELECT id FROM participant WHERE LOWER(email) = :email";
    $params = ['email' => $email];
    if ($challengeId !== null && $challengeId > 0) {
        $sql .= " AND id_challenge = :cid";
        $params['cid'] = $challengeId;
    }
    $q = $db->prepare($sql);
    $q->execute($params);
    return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $ids = chat_current_participant_ids($email);
    if (!$ids) {
        chat_json(['ok' => true, 'counts' => [], 'total' => 0, 'participant_required' => true]);
    }

    $in = implode(',', array_fill(0, count($ids), '?'));
    $db = chat_db();
    $q = $db->prepare("
        SELECT t.challenge_id, COUNT(*) AS unread
        FROM chat_notifications n
        JOIN chat_messages m ON m.id = n.message_id
        JOIN chat_threads t ON t.id = m.thread_id
        WHERE n.participant_id IN ($in)
          AND n.read_at IS NULL
          AND m.deleted_at IS NULL
        GROUP BY t.challenge_id
    ");
    $q->execute($ids);

    $counts = [];
    $total = 0;
    foreach (($q->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $cid = (int)$row['challenge_id'];
        $n = (int)$row['unread'];
        $counts[(string)$cid] = $n;
        $total += $n;
    }

    chat_json(['ok' => true, 'counts' => $counts, 'total' => $total]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $body = chat_read_json_body();
    $challengeId = (int)($body['challenge_id'] ?? 0);
    $ids = chat_current_participant_ids($email, $challengeId > 0 ? $challengeId : null);
    if (!$ids) chat_json(['ok' => true, 'updated' => 0]);

    $in = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $whereChallenge = '';
    if ($challengeId > 0) {
        $whereChallenge = ' AND t.challenge_id = ? ';
        $params[] = $challengeId;
    }

    $db = chat_db();
    $q = $db->prepare("
        UPDATE chat_notifications n
        JOIN chat_messages m ON m.id = n.message_id
        JOIN chat_threads t ON t.id = m.thread_id
        SET n.read_at = NOW()
        WHERE n.participant_id IN ($in)
          AND n.read_at IS NULL
          $whereChallenge
    ");
    $q->execute($params);
    chat_json(['ok' => true, 'updated' => $q->rowCount()]);
}

chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
