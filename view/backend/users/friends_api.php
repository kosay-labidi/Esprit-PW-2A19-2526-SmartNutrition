<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once __DIR__ . '/../../../config.php';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

$sessionUserId = $_SESSION['user']['id_utilisateur']
    ?? $_SESSION['user']['id']
    ?? $_SESSION['id_utilisateur']
    ?? $_SERVER['HTTP_X_USER_ID']
    ?? null;

if (empty($sessionUserId)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifie']);
    exit();
}

$userId = (int) $sessionUserId;
$action = $_GET['action'] ?? '';
$rawBody = file_get_contents('php://input');
$payload = $rawBody !== '' ? json_decode($rawBody, true) : [];
if ($rawBody !== '' && !is_array($payload)) {
    jsonResponse(['success' => false, 'message' => 'JSON invalide'], 400);
}
function avatarUrl(array $row): ?string
{
    $raw = $row['avatar'] ?: ($row['photo'] ?? null);
    if (empty($raw)) {
        return null;
    }
    if (preg_match('/^https?:\/\//', $raw)) {
        return $raw;
    }
    return 'http://localhost/Mainn/' . ltrim($raw, '/');
}

function getUserRole(PDO $db, int $uid): ?string
{
    $stmt = $db->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :id LIMIT 1");
    $stmt->execute(['id' => $uid]);
    $role = $stmt->fetchColumn();
    return $role ? (string) $role : null;
}

function getConversationId(PDO $db, int $me, int $friend): int
{
    $conversationSql = "SELECT c.id
        FROM conversations c
        JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = :me
        JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = :friend
        WHERE c.type = 'private'
        LIMIT 1";
    $stmt = $db->prepare($conversationSql);
    $stmt->execute(['me' => $me, 'friend' => $friend]);
    return (int) $stmt->fetchColumn();
}

function getOrCreateConversationId(PDO $db, int $me, int $friend): int
{
    $conversationId = getConversationId($db, $me, $friend);
    if ($conversationId > 0) {
        return $conversationId;
    }

    $db->prepare("INSERT INTO conversations(type, created_at, updated_at) VALUES('private', NOW(), NOW())")->execute();
    $conversationId = (int) $db->lastInsertId();
    $cp = $db->prepare("INSERT INTO conversation_participants(conversation_id, user_id, joined_at) VALUES(:cid, :uid, NOW())");
    $cp->execute(['cid' => $conversationId, 'uid' => $me]);
    $cp->execute(['cid' => $conversationId, 'uid' => $friend]);
    return $conversationId;
}

try {
    $db = config::getConnexion();

    if ($action === 'users') {
        $sql = "SELECT u.id_utilisateur, u.nom, u.prenom, u.avatar, u.photo,
                       EXISTS(
                           SELECT 1 FROM friend_requests fr
                           WHERE ((fr.sender_id = :me1 AND fr.receiver_id = u.id_utilisateur)
                               OR (fr.receiver_id = :me2 AND fr.sender_id = u.id_utilisateur))
                             AND fr.status = 'accepted'
                       ) AS is_friend
                FROM utilisateurs u
                WHERE u.id_utilisateur != :me3
                  AND u.role != 'admin'
                ORDER BY u.prenom, u.nom";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'me1' => $userId,
            'me2' => $userId,
            'me3' => $userId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['avatar_url'] = avatarUrl($row);
            $row['is_friend'] = (int) $row['is_friend'] === 1;
        }
        echo json_encode(['success' => true, 'users' => $rows]);
        exit();
    }

    if ($action === 'friends') {
        $sql = "SELECT u.id_utilisateur, u.nom, u.prenom, u.avatar, u.photo
                FROM friend_requests fr
                JOIN utilisateurs u ON u.id_utilisateur = IF(fr.sender_id = :me1, fr.receiver_id, fr.sender_id)
                WHERE (fr.sender_id = :me2 OR fr.receiver_id = :me3)
                  AND fr.status = 'accepted'
                  AND u.role != 'admin'
                ORDER BY u.prenom, u.nom";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'me1' => $userId,
            'me2' => $userId,
            'me3' => $userId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['avatar_url'] = avatarUrl($row);
        }
        echo json_encode(['success' => true, 'friends' => $rows]);
        exit();
    }

    if ($action === 'add_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendId = (int) ($payload['friend_id'] ?? 0);
        if ($friendId < 1 || $friendId === $userId) {
            jsonResponse(['success' => false, 'message' => 'friend_id invalide'], 400);
        }
        $friendRole = getUserRole($db, $friendId);
        if ($friendRole === null) {
            jsonResponse(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }
        if ($friendRole === 'admin') {
            jsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas etre ami avec un admin'], 403);
        }

        $check = $db->prepare("SELECT id FROM friend_requests
            WHERE ((sender_id = :me1 AND receiver_id = :friend1) OR (sender_id = :friend2 AND receiver_id = :me2))
            LIMIT 1");
        $check->execute([
            'me1' => $userId,
            'friend1' => $friendId,
            'friend2' => $friendId,
            'me2' => $userId
        ]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $db->prepare("UPDATE friend_requests SET status = 'accepted', updated_at = NOW() WHERE id = :id");
            $upd->execute(['id' => $existing['id']]);
        } else {
            $ins = $db->prepare("INSERT INTO friend_requests(sender_id, receiver_id, status, created_at, updated_at)
                                 VALUES(:me1, :friend1, 'accepted', NOW(), NOW())");
            $ins->execute(['me1' => $userId, 'friend1' => $friendId]);
        }
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'remove_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendId = (int) ($payload['friend_id'] ?? 0);
        if ($friendId < 1 || $friendId === $userId) {
            jsonResponse(['success' => false, 'message' => 'friend_id invalide'], 400);
        }
        $del = $db->prepare("DELETE FROM friend_requests
            WHERE ((sender_id = :me1 AND receiver_id = :friend1) OR (sender_id = :friend2 AND receiver_id = :me2))");
        $del->execute([
            'me1' => $userId,
            'friend1' => $friendId,
            'friend2' => $friendId,
            'me2' => $userId
        ]);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'messages') {
        $friendId = (int) ($_GET['friend_id'] ?? 0);
        if ($friendId < 1) {
            jsonResponse(['success' => false, 'message' => 'friend_id manquant'], 400);
        }

        $conversationId = getConversationId($db, $userId, $friendId);

        if (!$conversationId) {
            echo json_encode(['success' => true, 'messages' => []]);
            exit();
        }

        $read = $db->prepare("UPDATE messages
                              SET is_read = 1
                              WHERE conversation_id = :cid
                                AND sender_id != :me
                                AND is_read = 0");
        $read->execute(['cid' => $conversationId, 'me' => $userId]);

        $msg = $db->prepare("SELECT id, sender_id, message, type, file_url, created_at
                             FROM messages WHERE conversation_id = :cid ORDER BY created_at ASC");
        $msg->execute(['cid' => $conversationId]);
        echo json_encode(['success' => true, 'messages' => $msg->fetchAll(PDO::FETCH_ASSOC)]);
        exit();
    }

    if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendId = (int) ($payload['friend_id'] ?? 0);
        $message = trim((string) ($payload['message'] ?? ''));
        if ($friendId < 1 || $message === '') {
            jsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }
        if ($friendId === $userId) {
            jsonResponse(['success' => false, 'message' => 'Destinataire invalide'], 400);
        }
        if (getUserRole($db, $friendId) === 'admin') {
            jsonResponse(['success' => false, 'message' => 'Messaging admin interdit'], 403);
        }

        $conversationId = getOrCreateConversationId($db, $userId, $friendId);

        $isLink = preg_match('/^https?:\/\/\S+$/i', $message) === 1;
        $msgType = $isLink ? 'link' : 'text';

        $ins = $db->prepare("INSERT INTO messages(conversation_id, sender_id, message, type, created_at)
                             VALUES(:cid, :sid, :message, :type, NOW())");
        $ins->execute([
            'cid' => $conversationId,
            'sid' => $userId,
            'message' => $message,
            'type' => $msgType
        ]);
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'send_attachment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $friendId = (int) ($_POST['friend_id'] ?? 0);
        if ($friendId < 1 || empty($_FILES['attachment'])) {
            echo json_encode(['success' => false, 'message' => 'Fichier ou ami manquant']);
            exit();
        }
        if (getUserRole($db, $friendId) === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Messaging admin interdit']);
            exit();
        }

        $file = $_FILES['attachment'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Erreur upload']);
            exit();
        }
        if (($file['size'] ?? 0) > 40 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 40MB)']);
            exit();
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        $kind = '';
        $ext = '';
        if (str_starts_with($mime, 'image/')) {
            $kind = 'image';
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        } elseif (str_starts_with($mime, 'video/')) {
            $kind = 'video';
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'mp4';
        } else {
            echo json_encode(['success' => false, 'message' => 'Type non supporte (image/video uniquement)']);
            exit();
        }

        $safeName = 'chat_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $targetDir = __DIR__ . '/../../../uploads/chat/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        $target = $targetDir . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['success' => false, 'message' => 'Impossible de sauvegarder le fichier']);
            exit();
        }

        $conversationId = getOrCreateConversationId($db, $userId, $friendId);
        $publicUrl = 'http://localhost/Mainn/uploads/chat/' . $safeName;
        $ins = $db->prepare("INSERT INTO messages(conversation_id, sender_id, message, type, file_url, created_at)
                             VALUES(:cid, :sid, :message, :type, :file_url, NOW())");
        $ins->execute([
            'cid' => $conversationId,
            'sid' => $userId,
            'message' => $file['name'],
            'type' => $kind,
            'file_url' => $publicUrl
        ]);
        echo json_encode(['success' => true, 'file_url' => $publicUrl, 'type' => $kind]);
        exit();
    }

    if ($action === 'notifications') {
        $sql = "SELECT
                    m.sender_id AS friend_id,
                    CONCAT(u.prenom, ' ', u.nom) AS friend_name,
                    m.message AS last_message,
                    m.type AS last_type,
                    m.created_at AS last_at,
                    (
                        SELECT COUNT(*)
                        FROM messages m2
                        WHERE m2.conversation_id = m.conversation_id
                          AND m2.sender_id = m.sender_id
                          AND m2.is_read = 0
                    ) AS unread_count
                FROM messages m
                JOIN conversations c ON c.id = m.conversation_id AND c.type = 'private'
                JOIN conversation_participants me_cp
                    ON me_cp.conversation_id = c.id AND me_cp.user_id = :me1
                JOIN conversation_participants other_cp
                    ON other_cp.conversation_id = c.id AND other_cp.user_id = m.sender_id
                JOIN utilisateurs u ON u.id_utilisateur = m.sender_id
                WHERE m.sender_id != :me2
                  AND m.is_read = 0
                  AND u.role != 'admin'
                  AND m.id = (
                      SELECT MAX(m3.id)
                      FROM messages m3
                      WHERE m3.conversation_id = m.conversation_id
                        AND m3.sender_id = m.sender_id
                        AND m3.is_read = 0
                  )
                ORDER BY m.created_at DESC
                LIMIT 8";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'me1' => $userId,
            'me2' => $userId
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;
        foreach ($rows as $row) {
            $total += (int) $row['unread_count'];
        }
        echo json_encode([
            'success' => true,
            'unread_count' => $total,
            'notifications' => $rows
        ]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
