<?php
require_once(__DIR__ . '/_db.php');

// GET: list messages for a challenge (cursor pagination)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $challengeId = (int)($_GET['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);

    $limit = max(1, min(50, (int)($_GET['limit'] ?? 30)));
    $before = trim((string)($_GET['before'] ?? '')); // ISO or timestamp
    $beforeSql = '';
    $params = ['c' => $challengeId, 'limit' => $limit];
    if ($before !== '') {
        $beforeSql = " AND m.created_at < :before ";
        $params['before'] = $before;
    }

    $db = chat_db();
    $threadId = chat_ensure_thread($challengeId);

    $sql = "
        SELECT
            m.id, m.parent_id, m.participant_id, m.user_id, m.author_name,
            m.body, m.created_at, m.updated_at, m.deleted_at
        FROM chat_messages m
        WHERE m.thread_id = :t
          $beforeSql
        ORDER BY m.created_at DESC
        LIMIT :limit
    ";
    $q = $db->prepare($sql);
    $q->bindValue(':t', $threadId, PDO::PARAM_INT);
    if (isset($params['before'])) $q->bindValue(':before', $params['before']);
    $q->bindValue(':limit', $limit, PDO::PARAM_INT);
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Attachments
    $ids = array_map(fn($r) => $r['id'], $rows);
    $attachmentsByMsg = [];
    if (count($ids) > 0) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $qa = $db->prepare("SELECT message_id, type, url, mime, size_bytes, duration_ms FROM chat_attachments WHERE message_id IN ($in)");
        $qa->execute($ids);
        foreach (($qa->fetchAll(PDO::FETCH_ASSOC) ?: []) as $a) {
            $mid = $a['message_id'];
            if (!isset($attachmentsByMsg[$mid])) $attachmentsByMsg[$mid] = [];
            $attachmentsByMsg[$mid][] = [
                'type' => $a['type'],
                'url' => $a['url'],
                'mime' => $a['mime'],
                'size_bytes' => (int)($a['size_bytes'] ?? 0),
                'duration_ms' => $a['duration_ms'] !== null ? (int)$a['duration_ms'] : null,
            ];
        }
    }

    $out = array_map(function($r) use ($attachmentsByMsg) {
        $mid = $r['id'];
        return [
            'id' => $mid,
            'parent_id' => $r['parent_id'],
            'participant_id' => $r['participant_id'] !== null ? (int)$r['participant_id'] : null,
            'user_id' => $r['user_id'] !== null ? (int)$r['user_id'] : null,
            'author_name' => $r['author_name'],
            'body' => $r['deleted_at'] ? '' : ($r['body'] ?? ''),
            'created_at' => $r['created_at'],
            'updated_at' => $r['updated_at'],
            'deleted_at' => $r['deleted_at'],
            'attachments' => $attachmentsByMsg[$mid] ?? [],
        ];
    }, $rows);

    chat_json(['ok' => true, 'challenge_id' => $challengeId, 'messages' => $out]);
}

// POST: create message (text + optional parent_id + optional attachments[] already uploaded)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $body = chat_read_json_body();
    $challengeId = (int)($body['challenge_id'] ?? 0);
    if ($challengeId <= 0) chat_json(['ok' => false, 'error' => 'challenge_id required'], 400);
    $text = trim((string)($body['body'] ?? ''));
    $parentId = trim((string)($body['parent_id'] ?? ''));
    $attachments = $body['attachments'] ?? [];
    if ($text === '' && (!is_array($attachments) || count($attachments) === 0)) {
        chat_json(['ok' => false, 'error' => 'body or attachments required'], 400);
    }

    $me = chat_get_me_from_session();
    $participant = chat_find_participant_for_user($challengeId, (string)($me['email'] ?? ''));
    $participantId = $participant ? (int)$participant['id'] : null;
    $authorName = $participant ? (string)$participant['nom'] : (string)($me['nom'] ?? 'Invité');
    $userId = (int)($me['id'] ?? 0);
    if ($userId <= 0) $userId = null;

    $db = chat_db();
    $threadId = chat_ensure_thread($challengeId);
    $id = chat_uuid_v4();

    $ins = $db->prepare("INSERT INTO chat_messages (id, thread_id, parent_id, participant_id, user_id, author_name, body) VALUES (:id,:t,:p,:pid,:uid,:a,:b)");
    $ins->execute([
        'id' => $id,
        't' => $threadId,
        'p' => $parentId !== '' ? $parentId : null,
        'pid' => $participantId,
        'uid' => $userId,
        'a' => $authorName,
        'b' => $text !== '' ? $text : null,
    ]);

    // Insert attachments rows
    if (is_array($attachments) && count($attachments) > 0) {
        $ia = $db->prepare("INSERT INTO chat_attachments (message_id, type, url, mime, size_bytes, duration_ms) VALUES (:mid,:type,:url,:mime,:size,:dur)");
        foreach ($attachments as $a) {
            if (!is_array($a)) continue;
            $type = (string)($a['type'] ?? '');
            $url = (string)($a['url'] ?? '');
            $mime = (string)($a['mime'] ?? '');
            $size = (int)($a['size_bytes'] ?? 0);
            $dur = isset($a['duration_ms']) ? (int)$a['duration_ms'] : null;
            if (($type !== 'image' && $type !== 'audio' && $type !== 'video') || $url === '' || $mime === '') continue;
            $ia->execute([
                'mid' => $id,
                'type' => $type,
                'url' => $url,
                'mime' => $mime,
                'size' => $size,
                'dur' => $dur,
            ]);
        }
    }

    // Notifications unread pour les participants du défi, sauf l'auteur si reconnu.
    $qn = $db->prepare("
        INSERT IGNORE INTO chat_notifications (participant_id, message_id)
        SELECT p.id, :mid
        FROM participant p
        WHERE p.id_challenge = :cid
          AND (:author_pid IS NULL OR p.id <> :author_pid)
    ");
    $qn->execute([
        'mid' => $id,
        'cid' => $challengeId,
        'author_pid' => $participantId,
    ]);

    // --- INTEGRATION ML / AI ALERT (Phase 3) ---
    if ($participantId > 0) {
        // On pourrait appeler l'API coach de manière asynchrone ici.
        // Pour la démo, on simule l'analyse de sentiment immédiate si c'est un message critique.
        $lower = mb_strtolower($text);
        if (strpos($lower, 'abandon') !== false || strpos($lower, 'difficile') !== false || strpos($lower, 'marre') !== false) {
             // On log l'intention détectée
             error_log("ML Alert: Sentiment négatif détecté pour le participant $participantId dans le défi $challengeId");
        }
    }

    chat_json([
        'ok' => true,
        'message' => [
            'id' => $id,
            'challenge_id' => $challengeId,
            'parent_id' => $parentId !== '' ? $parentId : null,
            'participant_id' => $participantId,
            'user_id' => $userId,
            'author_name' => $authorName,
            'body' => $text,
            'created_at' => date('Y-m-d H:i:s'),
            'attachments' => is_array($attachments) ? $attachments : [],
        ]
    ], 201);
}

chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
