<?php
require_once(__DIR__ . '/../../../../config.php');

function chat_db(): PDO {
    $db = Config::getConnexion();
    chat_install_schema($db);
    return $db;
}

function chat_json($data, int $code = 200): void {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function chat_require_method(string $method): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
    }
}

function chat_read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function chat_uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function chat_column_exists(PDO $db, string $table, string $column): bool {
    $q = $db->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $q->execute(['table' => $table, 'column' => $column]);
    return (int)$q->fetchColumn() > 0;
}

function chat_install_schema(PDO $db): void {
    static $done = false;
    if ($done) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_threads` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `challenge_id` INT NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_chat_threads_challenge` (`challenge_id`),
          CONSTRAINT `fk_chat_threads_challenge`
            FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_messages` (
          `id` CHAR(36) NOT NULL,
          `thread_id` INT NOT NULL,
          `parent_id` CHAR(36) DEFAULT NULL,
          `participant_id` INT DEFAULT NULL,
          `user_id` INT DEFAULT NULL,
          `author_name` VARCHAR(150) NOT NULL,
          `body` TEXT DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NULL DEFAULT NULL,
          `deleted_at` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_chat_messages_thread_time` (`thread_id`, `created_at`),
          KEY `idx_chat_messages_parent` (`parent_id`),
          KEY `idx_chat_messages_participant` (`participant_id`),
          KEY `idx_chat_messages_user` (`user_id`),
          CONSTRAINT `fk_chat_messages_thread`
            FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_chat_messages_parent`
            FOREIGN KEY (`parent_id`) REFERENCES `chat_messages` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE,
          CONSTRAINT `fk_chat_messages_participant`
            FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_attachments` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `message_id` CHAR(36) NOT NULL,
          `type` ENUM('image','audio','video') NOT NULL,
          `url` VARCHAR(600) NOT NULL,
          `mime` VARCHAR(100) NOT NULL,
          `size_bytes` INT NOT NULL DEFAULT 0,
          `duration_ms` INT DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_chat_attachments_message` (`message_id`),
          CONSTRAINT `fk_chat_attachments_message`
            FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $db->exec("ALTER TABLE `chat_attachments` MODIFY `type` ENUM('image','audio','video') NOT NULL");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `chat_notifications` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `participant_id` INT NOT NULL,
          `message_id` CHAR(36) NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `read_at` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_chat_notif_part_msg` (`participant_id`, `message_id`),
          KEY `idx_chat_notif_part_read` (`participant_id`, `read_at`),
          CONSTRAINT `fk_chat_notif_participant`
            FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_chat_notif_message`
            FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `challenge_videos` (
          `id` CHAR(36) NOT NULL,
          `challenge_id` INT NOT NULL,
          `uploader_user_id` INT DEFAULT NULL,
          `uploader_participant_id` INT DEFAULT NULL,
          `title` VARCHAR(180) DEFAULT NULL,
          `original_name` VARCHAR(255) NOT NULL,
          `url` VARCHAR(600) NOT NULL,
          `storage_path` VARCHAR(900) NOT NULL,
          `mime` VARCHAR(100) NOT NULL,
          `size_bytes` INT NOT NULL DEFAULT 0,
          `sha256` CHAR(64) NOT NULL,
          `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
          `moderated_by_user_id` INT DEFAULT NULL,
          `moderated_at` TIMESTAMP NULL DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_challenge_videos_challenge_status` (`challenge_id`, `status`, `created_at`),
          KEY `idx_challenge_videos_participant` (`uploader_participant_id`),
          CONSTRAINT `fk_challenge_videos_challenge`
            FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_challenge_videos_participant`
            FOREIGN KEY (`uploader_participant_id`) REFERENCES `participant` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `challenge_live_streams` (
          `id` CHAR(36) NOT NULL,
          `challenge_id` INT NOT NULL,
          `requested_by_user_id` INT DEFAULT NULL,
          `requested_by_participant_id` INT DEFAULT NULL,
          `title` VARCHAR(180) NOT NULL,
          `description` TEXT DEFAULT NULL,
          `stream_url` VARCHAR(700) NOT NULL,
          `provider` VARCHAR(60) NOT NULL DEFAULT 'external',
          `status` ENUM('pending','approved','rejected','live','ended') NOT NULL DEFAULT 'pending',
          `approved_by_user_id` INT DEFAULT NULL,
          `approved_at` TIMESTAMP NULL DEFAULT NULL,
          `started_at` TIMESTAMP NULL DEFAULT NULL,
          `ended_at` TIMESTAMP NULL DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_challenge_live_challenge_status` (`challenge_id`, `status`, `created_at`),
          KEY `idx_challenge_live_participant` (`requested_by_participant_id`),
          CONSTRAINT `fk_challenge_live_challenge`
            FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `fk_challenge_live_participant`
            FOREIGN KEY (`requested_by_participant_id`) REFERENCES `participant` (`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $done = true;
}

function chat_get_me_from_session(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $sessionUser = is_array($_SESSION['user'] ?? null) ? $_SESSION['user'] : [];
    $uid = (int)(
        $_SESSION['user_id']
        ?? $sessionUser['id_utilisateur']
        ?? $sessionUser['id']
        ?? 0
    );
    $nom = trim((string)(
        $_SESSION['nom']
        ?? $_SESSION['name']
        ?? trim(($sessionUser['prenom'] ?? '') . ' ' . ($sessionUser['nom'] ?? ''))
    ));
    $pseudo = trim((string)($_SESSION['pseudo'] ?? $sessionUser['pseudo'] ?? ''));
    $email = trim((string)($_SESSION['email'] ?? $sessionUser['email'] ?? ''));
    if ($nom === '' && $pseudo !== '') $nom = $pseudo;
    if ($nom === '' && $uid > 0) $nom = 'Utilisateur #' . $uid;
    return [
        'id' => $uid,
        'nom' => $nom !== '' ? $nom : 'Invité',
        'pseudo' => $pseudo,
        'email' => $email,
    ];
}

function chat_find_participant_for_user(int $challengeId, string $email): ?array {
    $email = strtolower(trim($email));
    if ($challengeId <= 0 || $email === '') return null;
    $db = chat_db();
    $q = $db->prepare("SELECT id, nom, email FROM participant WHERE id_challenge=:c AND LOWER(email)=:e LIMIT 1");
    $q->execute(['c' => $challengeId, 'e' => $email]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function chat_is_admin(array $me): bool {
    $role = strtolower(trim((string)($_SESSION['role'] ?? $_SESSION['user_role'] ?? $_SESSION['type'] ?? '')));
    if (in_array($role, ['admin', 'administrateur', 'super_admin'], true)) return true;
    return !empty($_SESSION['is_admin']) || !empty($_SESSION['admin']);
}

function chat_challenge_creator_user_id(PDO $db, int $challengeId): ?int {
    foreach (['created_by', 'creator_id', 'user_id', 'id_user'] as $column) {
        if (!chat_column_exists($db, 'challenge', $column)) continue;
        $q = $db->prepare("SELECT `$column` FROM `challenge` WHERE id = :id LIMIT 1");
        $q->execute(['id' => $challengeId]);
        $value = (int)($q->fetchColumn() ?: 0);
        return $value > 0 ? $value : null;
    }
    return null;
}

function chat_challenge_exists(PDO $db, int $challengeId): bool {
    $q = $db->prepare("SELECT COUNT(*) FROM challenge WHERE id = :id");
    $q->execute(['id' => $challengeId]);
    return (int)$q->fetchColumn() > 0;
}

function chat_current_participant(int $challengeId, array $me): ?array {
    return chat_find_participant_for_user($challengeId, (string)($me['email'] ?? ''));
}

function chat_user_can_moderate_challenge(int $challengeId, array $me, ?PDO $db = null): bool {
    $db = $db ?: chat_db();
    $uid = (int)($me['id'] ?? 0);
    if (chat_is_admin($me)) return true;
    $creatorId = chat_challenge_creator_user_id($db, $challengeId);
    return $uid > 0 && $creatorId !== null && $creatorId === $uid;
}

function chat_user_can_upload_challenge_media(int $challengeId, array $me, ?PDO $db = null): bool {
    $db = $db ?: chat_db();
    if (chat_user_can_moderate_challenge($challengeId, $me, $db)) return true;
    return chat_current_participant($challengeId, $me) !== null;
}

function chat_user_can_watch_challenge(int $challengeId, array $me, ?PDO $db = null): bool {
    return chat_user_can_upload_challenge_media($challengeId, $me, $db);
}

function chat_ensure_thread(int $challengeId): int {
    $db = chat_db();
    $q = $db->prepare("SELECT id FROM chat_threads WHERE challenge_id=:c LIMIT 1");
    $q->execute(['c' => $challengeId]);
    $id = (int)($q->fetchColumn() ?: 0);
    if ($id > 0) return $id;
    $ins = $db->prepare("INSERT INTO chat_threads (challenge_id) VALUES (:c)");
    $ins->execute(['c' => $challengeId]);
    return (int)$db->lastInsertId();
}

set_exception_handler(function (Throwable $e): void {
    error_log('Erreur chat API: ' . $e->getMessage());
    if (ob_get_length()) ob_clean();
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur chat']);
    exit;
});
