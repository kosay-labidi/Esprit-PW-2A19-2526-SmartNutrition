-- Chat Défis (GaiaLumen) — schéma persistant
-- À exécuter sur la base `dor_defi` (MySQL).
-- Généré: 2026-05-02

USE `dor_defi`;

-- 1) Un thread par défi
CREATE TABLE IF NOT EXISTS `chat_threads` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `challenge_id` INT NOT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_threads_challenge` (`challenge_id`),
  CONSTRAINT `fk_chat_threads_challenge`
    FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 2) Messages
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id`             CHAR(36) NOT NULL,
  `thread_id`      INT NOT NULL,
  `parent_id`      CHAR(36) DEFAULT NULL,
  `participant_id` INT DEFAULT NULL,
  `user_id`        INT DEFAULT NULL,
  `author_name`    VARCHAR(150) NOT NULL,
  `body`           TEXT DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT NULL,
  `deleted_at`     TIMESTAMP NULL DEFAULT NULL,
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
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 3) Pièces jointes (images + audio + video)
CREATE TABLE IF NOT EXISTS `chat_attachments` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `message_id`  CHAR(36) NOT NULL,
  `type`        ENUM('image','audio','video') NOT NULL,
  `url`         VARCHAR(600) NOT NULL,
  `mime`        VARCHAR(100) NOT NULL,
  `size_bytes`  INT NOT NULL DEFAULT 0,
  `duration_ms` INT DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_attachments_message` (`message_id`),
  CONSTRAINT `fk_chat_attachments_message`
    FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Pour les installations déjà créées avant l'ajout vidéo.
ALTER TABLE `chat_attachments`
  MODIFY `type` ENUM('image','audio','video') NOT NULL;

-- 4) Notifications (unread par participant)
CREATE TABLE IF NOT EXISTS `chat_notifications` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `participant_id` INT NOT NULL,
  `message_id`    CHAR(36) NOT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at`       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_notif_part_msg` (`participant_id`, `message_id`),
  KEY `idx_chat_notif_part_read` (`participant_id`, `read_at`),
  CONSTRAINT `fk_chat_notif_participant`
    FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_notif_message`
    FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 5) Vidéos liées aux défis (upload serveur + validation modération)
CREATE TABLE IF NOT EXISTS `challenge_videos` (
  `id`                       CHAR(36) NOT NULL,
  `challenge_id`             INT NOT NULL,
  `uploader_user_id`         INT DEFAULT NULL,
  `uploader_participant_id`  INT DEFAULT NULL,
  `title`                    VARCHAR(180) DEFAULT NULL,
  `original_name`            VARCHAR(255) NOT NULL,
  `url`                      VARCHAR(600) NOT NULL,
  `storage_path`             VARCHAR(900) NOT NULL,
  `mime`                     VARCHAR(100) NOT NULL,
  `size_bytes`               INT NOT NULL DEFAULT 0,
  `sha256`                   CHAR(64) NOT NULL,
  `status`                   ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `moderated_by_user_id`     INT DEFAULT NULL,
  `moderated_at`             TIMESTAMP NULL DEFAULT NULL,
  `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_challenge_videos_challenge_status` (`challenge_id`, `status`, `created_at`),
  KEY `idx_challenge_videos_participant` (`uploader_participant_id`),
  CONSTRAINT `fk_challenge_videos_challenge`
    FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_challenge_videos_participant`
    FOREIGN KEY (`uploader_participant_id`) REFERENCES `participant` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- 6) Demandes et sessions live streaming de défis
CREATE TABLE IF NOT EXISTS `challenge_live_streams` (
  `id`                         CHAR(36) NOT NULL,
  `challenge_id`               INT NOT NULL,
  `requested_by_user_id`       INT DEFAULT NULL,
  `requested_by_participant_id` INT DEFAULT NULL,
  `title`                      VARCHAR(180) NOT NULL,
  `description`                TEXT DEFAULT NULL,
  `stream_url`                 VARCHAR(700) NOT NULL,
  `provider`                   VARCHAR(60) NOT NULL DEFAULT 'external',
  `status`                     ENUM('pending','approved','rejected','live','ended') NOT NULL DEFAULT 'pending',
  `approved_by_user_id`        INT DEFAULT NULL,
  `approved_at`                TIMESTAMP NULL DEFAULT NULL,
  `started_at`                 TIMESTAMP NULL DEFAULT NULL,
  `ended_at`                   TIMESTAMP NULL DEFAULT NULL,
  `created_at`                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_challenge_live_challenge_status` (`challenge_id`, `status`, `created_at`),
  KEY `idx_challenge_live_participant` (`requested_by_participant_id`),
  CONSTRAINT `fk_challenge_live_challenge`
    FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_challenge_live_participant`
    FOREIGN KEY (`requested_by_participant_id`) REFERENCES `participant` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
