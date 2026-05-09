-- =============================================================
--  TABLES : evenement et participation
-- =============================================================

USE `dsgaialumen`;

CREATE TABLE IF NOT EXISTS `evenement` (
    `id_event` INT NOT NULL AUTO_INCREMENT,
    `titre` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `date` DATE NOT NULL,
    `heure` TIME NOT NULL,
    `type` ENUM('repas', 'sport', 'medical', 'atelier') NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id_event`),
    INDEX `idx_type` (`type`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `participation` (
    `id_participation` INT NOT NULL AUTO_INCREMENT,
    `id_event` INT NOT NULL,
    `nom_complet` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `telephone` VARCHAR(50) DEFAULT NULL,
    `centre_interet` VARCHAR(255) DEFAULT NULL,
    `statut` ENUM('en_attente', 'confirmée', 'annulée') NOT NULL DEFAULT 'en_attente',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id_participation`),
    INDEX `idx_id_event` (`id_event`),
    INDEX `idx_statut` (`statut`),
    CONSTRAINT `fk_participation_evenement`
        FOREIGN KEY (`id_event`)
        REFERENCES `evenement` (`id_event`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
