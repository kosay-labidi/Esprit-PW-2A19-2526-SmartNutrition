-- ═══════════════════════════════════════════════════════════════════
--  BASE DE DONNÉES : dor_defi
--  Projet       : GaiaLumen — Gestion des Défis (GS_defis)
--  Généré le    : 2026-04-30
--  Encodage     : UTF-8
-- ═══════════════════════════════════════════════════════════════════

-- ---------------------------------------------------------------
-- 0. CRÉATION & SÉLECTION DE LA BASE
-- ---------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `dor_defi`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `dor_defi`;

-- ---------------------------------------------------------------
-- 1. TABLE : challenge
--    (modèle Challenge.php + colonnes Métier Simple)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `challenge` (
    `id`           INT            NOT NULL AUTO_INCREMENT,
    `titre`        VARCHAR(255)   NOT NULL,
    `description`  TEXT           NOT NULL,
    `type`         VARCHAR(100)   NOT NULL
                   COMMENT 'Ex: fitness, nutrition, bien-être, sport, mental',
    `objectif`     VARCHAR(255)   NOT NULL
                   COMMENT 'Description textuelle de l objectif',
    `valeur_cible` INT            NOT NULL DEFAULT 0
                   COMMENT 'Valeur numérique à atteindre (ex: 10 000 pas)',
    `date_debut`   DATE           NOT NULL,
    `date_fin`     DATE           NOT NULL,
    `statut`       ENUM(
                       'en_attente',
                       'actif',
                       'termine',
                       'accepte',
                       'refuse'
                   )              NOT NULL DEFAULT 'en_attente',
    `streak_icon`  VARCHAR(10)    DEFAULT '🔥'
                   COMMENT 'Emoji icône du défi',
    `image`        VARCHAR(500)   DEFAULT NULL
                   COMMENT 'URL ou chemin de l image du défi',

    -- ── Métier Simple : compteurs ──────────────────────────────
    `nb_vues`      INT            NOT NULL DEFAULT 0
                   COMMENT 'Nombre de fois que le défi a été consulté',
    `nb_likes`     INT            NOT NULL DEFAULT 0
                   COMMENT 'Nombre total de likes (mis à jour par trigger ou code)',
    `ordre`        INT            NOT NULL DEFAULT 0
                   COMMENT 'Ordre d affichage pour le drag-and-drop admin',

    `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
                   ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_statut`     (`statut`),
    INDEX `idx_type`       (`type`),
    INDEX `idx_date_debut` (`date_debut`),
    INDEX `idx_ordre`      (`ordre`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Défis collaboratifs de la plateforme GaiaLumen';


-- ---------------------------------------------------------------
-- 2. TABLE : participant
--    (modèle Participant.php + date_inscription)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant` (
    `id`               INT          NOT NULL AUTO_INCREMENT,
    `id_challenge`     INT          NOT NULL,
    `nom`              VARCHAR(150) NOT NULL,
    `email`            VARCHAR(255) NOT NULL,
    `objectif`         INT          NOT NULL DEFAULT 0
                       COMMENT 'Objectif personnel du participant (valeur numérique)',
    `motivation`       TEXT         DEFAULT NULL
                       COMMENT 'Message de motivation à rejoindre le défi',
    `action`           VARCHAR(255) DEFAULT NULL
                       COMMENT 'Action concrète que le participant s engage à faire',
    `engagement`       INT          NOT NULL DEFAULT 0
                       COMMENT 'Niveau d engagement de 0 à 100',
    `notifications`    TINYINT(1)   NOT NULL DEFAULT 1
                       COMMENT '1 = accepte les notifications email, 0 = non',
    `date_inscription` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_id_challenge` (`id_challenge`),
    INDEX `idx_email`        (`email`),
    INDEX `idx_engagement`   (`engagement`),
    CONSTRAINT `fk_participant_challenge`
        FOREIGN KEY (`id_challenge`)
        REFERENCES `challenge` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Participants inscrits aux défis';


-- ---------------------------------------------------------------
-- 3. TABLE : challenge_likes
--    (Fonctionnalité Métier Simple — toggle like unique par user)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `challenge_likes` (
    `id`           INT       NOT NULL AUTO_INCREMENT,
    `id_challenge` INT       NOT NULL,
    `id_user`      INT       NOT NULL DEFAULT 1
                   COMMENT 'ID utilisateur connecté (session)',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_like_user_challenge` (`id_challenge`, `id_user`)
                   COMMENT 'Un utilisateur ne peut liker un défi qu une seule fois',
    CONSTRAINT `fk_like_challenge`
        FOREIGN KEY (`id_challenge`)
        REFERENCES `challenge` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Table de liaison pour les likes de défis (un par user)';


-- ---------------------------------------------------------------
-- 4. DONNÉES D'EXEMPLE — table challenge
--    (10 défis couvrant tous les statuts et types)
-- ---------------------------------------------------------------
INSERT INTO `challenge`
    (`titre`, `description`, `type`, `objectif`, `valeur_cible`,
     `date_debut`, `date_fin`, `statut`, `streak_icon`, `image`,
     `nb_vues`, `nb_likes`, `ordre`)
VALUES
(
    '10 000 pas par jour',
    'Marchez au moins 10 000 pas chaque jour pendant 30 jours. '
    'Un défi simple mais puissant pour améliorer votre santé cardiovasculaire.',
    'fitness',
    'Atteindre 10 000 pas quotidiennement pendant 30 jours',
    10000,
    '2026-05-01', '2026-05-31',
    'actif', '🚶', NULL,
    245, 38, 1
),
(
    'Sans sucre 21 jours',
    'Éliminez totalement le sucre ajouté de votre alimentation pendant '
    '3 semaines. Ressentez la différence sur votre énergie et votre peau.',
    'nutrition',
    'Zéro sucre ajouté pendant 21 jours consécutifs',
    21,
    '2026-05-05', '2026-05-26',
    'actif', '🍬', NULL,
    183, 27, 2
),
(
    'Méditation 10 min quotidienne',
    'Consacrez 10 minutes chaque matin à la méditation. '
    'Développez votre pleine conscience et réduisez votre stress.',
    'mental',
    'Méditer 10 minutes sans interruption chaque jour',
    10,
    '2026-04-15', '2026-05-15',
    'actif', '🧘', NULL,
    312, 55, 3
),
(
    'Hydratation : 2L d''eau par jour',
    'Boire au moins 2 litres d''eau par jour pendant 4 semaines. '
    'Améliorez votre concentration, votre peau et votre métabolisme.',
    'bien-etre',
    'Consommer 2000 ml d''eau quotidiennement',
    2000,
    '2026-05-01', '2026-05-28',
    'accepte', '💧', NULL,
    97, 14, 4
),
(
    '30 jours de yoga',
    'Pratiquez le yoga tous les jours pendant un mois entier. '
    'Gagnez en flexibilité, en force et en équilibre mental.',
    'sport',
    'Compléter une session de yoga chaque jour pendant 30 jours',
    30,
    '2026-06-01', '2026-06-30',
    'en_attente', '🧎', NULL,
    42, 9, 5
),
(
    'Lecture 20 pages par jour',
    'Lisez au moins 20 pages d''un livre chaque jour. '
    'Stimulez votre imagination et développez vos connaissances.',
    'mental',
    'Lire 20 pages par jour sans exception',
    20,
    '2026-04-01', '2026-04-30',
    'termine', '📚', NULL,
    521, 89, 6
),
(
    'Défi gainage 30 jours',
    'Réalisez chaque jour un exercice de gainage (planche) en augmentant '
    'progressivement la durée. De 30 secondes à 5 minutes.',
    'fitness',
    'Tenir une planche de 5 minutes à la fin des 30 jours',
    300,
    '2026-03-01', '2026-03-31',
    'termine', '💪', NULL,
    448, 72, 7
),
(
    'Jeûne intermittent 16/8',
    'Pratiquez le jeûne 16h / fenêtre alimentaire 8h pendant 4 semaines. '
    'Favorisez la perte de poids et la régénération cellulaire.',
    'nutrition',
    'Respecter le protocole 16/8 pendant 28 jours',
    28,
    '2026-07-01', '2026-07-28',
    'en_attente', '⏰', NULL,
    18, 4, 8
),
(
    'Courir 5 km sans s''arrêter',
    'Entraînez-vous progressivement pour courir 5 km en continu '
    'en moins de 35 minutes. Parfait pour les débutants.',
    'sport',
    'Courir 5 km en moins de 35 minutes',
    5,
    '2026-05-10', '2026-06-10',
    'refuse', '🏃', NULL,
    63, 7, 9
),
(
    'Détox numérique weekend',
    'Déconnectez-vous totalement des réseaux sociaux chaque weekend '
    'pendant 1 mois. Retrouvez le calme et la vraie connexion humaine.',
    'bien-etre',
    'Aucun réseau social du vendredi 20h au lundi 8h pendant 4 weekends',
    4,
    '2026-05-01', '2026-05-31',
    'actif', '📵', NULL,
    156, 31, 10
),
(
    'Zéro Déchet : 1 semaine',
    'Réduisez vos déchets au maximum pendant une semaine complète. '
    'Privilégiez le vrac et évitez les emballages plastiques.',
    'ecologie',
    'Remplir moins d''un petit sac poubelle par semaine',
    7,
    '2026-05-15', '2026-05-22',
    'actif', '♻️', 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800',
    89, 22, 11
),
(
    'Énergie Solaire & Économie',
    'Réduisez votre consommation d''électricité de 15% en optimisant '
    'l''usage des appareils ménagers et de l''éclairage.',
    'ecologie',
    'Réduction de 15% sur la facture d''électricité',
    15,
    '2026-05-01', '2026-05-31',
    'actif', '☀️', 'https://images.unsplash.com/photo-1509391366360-fe5bb6578506?w=800',
    45, 12, 12
),
(
    'Gratitude quotidienne',
    'Écrivez chaque soir 3 choses pour lesquelles vous êtes reconnaissant. '
    'Améliorez votre bonheur et votre santé mentale.',
    'bien-etre',
    '3 notes de gratitude par jour pendant 21 jours',
    21,
    '2026-05-10', '2026-05-31',
    'actif', '🙏', 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800',
    124, 67, 13
),
(
    'Entraide de voisinage',
    'Rendez un petit service à un voisin chaque semaine (courses, jardinage, '
    'ou simple discussion). Renforcez les liens sociaux.',
    'social',
    '1 service rendu par semaine pendant 1 mois',
    4,
    '2026-05-01', '2026-05-31',
    'actif', '🤝', 'https://images.unsplash.com/photo-1521791136364-798a730bb361?w=800',
    76, 28, 14
);


-- ---------------------------------------------------------------
-- 5. DONNÉES D'EXEMPLE — table participant
--    (participants répartis sur plusieurs défis)
-- ---------------------------------------------------------------
INSERT INTO `participant`
    (`id_challenge`, `nom`, `email`, `objectif`, `motivation`,
     `action`, `engagement`, `notifications`)
VALUES
-- Défi 1 : 10 000 pas
(1, 'Amine Ben Salah',   'amine.bensalah@esprit.tn',   9000,
 'Je veux améliorer ma santé et perdre 5 kg',
 'Marcher jusqu au bureau tous les matins', 78, 1),

(1, 'Sarra Meddeb',      'sarra.meddeb@gmail.com',     10000,
 'Me motiver à bouger davantage',
 'Utiliser un podomètre et marcher après le dîner', 90, 1),

(1, 'Mohamed Trabelsi',  'med.trabelsi@outlook.fr',    8000,
 'Reprendre une activité physique régulière',
 'Pause déjeuner active chaque jour', 65, 0),

-- Défi 2 : Sans sucre
(2, 'Yasmine Karray',    'yasmine.karray@esprit.tn',   21,
 'Améliorer mon teint et réduire les fringales',
 'Remplacer les sodas par de l eau aromatisée', 85, 1),

(2, 'Firas Hamdi',       'firas.hamdi@gmail.com',      21,
 'Prendre de meilleures habitudes alimentaires',
 'Cuisiner maison et lire les étiquettes', 72, 1),

-- Défi 3 : Méditation
(3, 'Rania Bouazizi',    'rania.bouazizi@yahoo.fr',    10,
 'Réduire mon anxiété avant les examens',
 'Méditer avec l app Calm chaque matin à 7h', 95, 1),

(3, 'Khalil Ferchichi',  'khalil.ferchi@gmail.com',    10,
 'Développer ma concentration au travail',
 'Session de méditation guidée sur YouTube', 60, 1),

(3, 'Amal Sfar',         'amal.sfar@esprit.tn',        10,
 'Trouver la sérénité au quotidien',
 'Méditation pleine conscience le soir', 88, 0),

-- Défi 4 : Hydratation
(4, 'Nour Jebali',       'nour.jebali@gmail.com',      2000,
 'Arrêter les maux de tête liés à la déshydratation',
 'Garder une bouteille de 500ml sur mon bureau', 76, 1),

(4, 'Wael Ben Amor',     'wael.benamor@outlook.fr',    2000,
 'Améliorer ma peau et mon énergie',
 'Application rappel toutes les heures', 82, 1),

-- Défi 6 : Lecture (terminé)
(6, 'Salma Gharbi',      'salma.gharbi@esprit.tn',     600,
 'Lire 3 livres ce mois-ci',
 'Lire dans le métro et avant de dormir', 91, 1),

(6, 'Bilel Mansouri',    'bilel.mansouri@gmail.com',   400,
 'Développer ma culture générale',
 'Un chapitre au café chaque matin', 70, 0),

-- Défi 10 : Détox numérique
(10, 'Hajer Zouari',     'hajer.zouari@gmail.com',     4,
 'Passer plus de temps avec ma famille',
 'Éteindre le téléphone et jouer aux jeux de société', 87, 1),

(10, 'Tarek Jelassi',    'tarek.jelassi@esprit.tn',    4,
 'Être plus productif et moins distrait',
 'Lire un livre et faire du sport le weekend', 73, 1);


-- ---------------------------------------------------------------
-- 6. INITIALISER l'ordre des défis (après INSERT)
-- ---------------------------------------------------------------
UPDATE `challenge` SET `ordre` = `id`;


-- ---------------------------------------------------------------
-- 7. TRIGGER — maintenir nb_likes cohérent avec challenge_likes
-- ---------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `trg_like_insert`
AFTER INSERT ON `challenge_likes`
FOR EACH ROW
BEGIN
    UPDATE `challenge`
    SET `nb_likes` = `nb_likes` + 1
    WHERE `id` = NEW.id_challenge;
END$$

CREATE TRIGGER IF NOT EXISTS `trg_like_delete`
AFTER DELETE ON `challenge_likes`
FOR EACH ROW
BEGIN
    UPDATE `challenge`
    SET `nb_likes` = GREATEST(`nb_likes` - 1, 0)
    WHERE `id` = OLD.id_challenge;
END$$

DELIMITER ;


-- ---------------------------------------------------------------
-- 8. VUE utilitaire — statistiques globales (pratique pour le dashboard)
-- ---------------------------------------------------------------
CREATE OR REPLACE VIEW `v_challenge_stats` AS
SELECT
    c.id,
    c.titre,
    c.type,
    c.statut,
    c.valeur_cible,
    c.date_debut,
    c.date_fin,
    c.nb_vues,
    c.nb_likes,
    c.ordre,
    COUNT(p.id)                         AS nb_participants,
    COALESCE(AVG(p.engagement), 0)      AS avg_engagement,
    DATEDIFF(c.date_fin, CURDATE())     AS jours_restants
FROM `challenge` c
LEFT JOIN `participant` p ON p.id_challenge = c.id
GROUP BY
    c.id, c.titre, c.type, c.statut, c.valeur_cible,
    c.date_debut, c.date_fin, c.nb_vues, c.nb_likes, c.ordre;


-- ---------------------------------------------------------------
-- 9. REQUÊTES UTILES (à copier dans getStatistiques() PHP)
-- ---------------------------------------------------------------

-- Total par statut :
-- SELECT statut, COUNT(*) AS total FROM challenge GROUP BY statut;

-- Top 3 défis populaires :
-- SELECT titre, streak_icon, nb_participants, statut
-- FROM v_challenge_stats ORDER BY nb_participants DESC LIMIT 3;

-- Top 5 participants engagés :
-- SELECT p.nom, p.email, c.titre, p.engagement
-- FROM participant p
-- JOIN challenge c ON c.id = p.id_challenge
-- ORDER BY p.engagement DESC LIMIT 5;

-- Défis actifs avec compte à rebours :
-- SELECT id, titre, streak_icon, date_fin,
--        DATEDIFF(date_fin, CURDATE()) AS jours_restants
-- FROM challenge WHERE statut = 'actif' ORDER BY date_fin ASC;


-- ═══════════════════════════════════════════════════════════════════
--  FIN DU SCRIPT — Base dor_defi prête à l'emploi
-- ═══════════════════════════════════════════════════════════════════
