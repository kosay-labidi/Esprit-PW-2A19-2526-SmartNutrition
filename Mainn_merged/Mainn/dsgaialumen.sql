-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 01:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dsgaialumen`
--

-- --------------------------------------------------------

--
-- Table structure for table `aliments`
--

CREATE TABLE `aliments` (
  `id_aliment` int(10) UNSIGNED NOT NULL,
  `nom` varchar(200) NOT NULL,
  `type` enum('légume','fruit','céréale','protéines animales','légumineuse','produit laitier','huile','épice','autre') NOT NULL,
  `categorie` enum('frais','sec','transformé','ultra-transformé') NOT NULL,
  `calories` float NOT NULL,
  `proteines` float NOT NULL,
  `glucides` float NOT NULL,
  `lipides` float NOT NULL,
  `fibres` float DEFAULT 0,
  `sucre` float DEFAULT 0,
  `sodium` float DEFAULT 0,
  `vitamines` text DEFAULT NULL,
  `co2` float DEFAULT 0,
  `label_ecologique` varchar(100) DEFAULT NULL,
  `prix` float DEFAULT 0,
  `origine` varchar(100) DEFAULT NULL,
  `allergenes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenge`
--

CREATE TABLE `challenge` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` varchar(100) NOT NULL COMMENT 'collectif | individuel | fitness | nutrition | bien-etre | sport | mental',
  `objectif` varchar(255) NOT NULL COMMENT 'Catégorie ou description textuelle de l objectif',
  `valeur_cible` int(11) NOT NULL DEFAULT 50 COMMENT 'Valeur numérique cible (ex: 100 = 100%, 10000 = 10000 pas)',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` enum('en_attente','actif','termine','accepte','refuse') NOT NULL DEFAULT 'en_attente',
  `streak_icon` varchar(20) NOT NULL DEFAULT '?' COMMENT 'Emoji représentant le défi',
  `image` text DEFAULT NULL COMMENT 'URL ou chemin de l image du défi',
  `nb_vues` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de fois où la fiche défi a été ouverte',
  `nb_likes` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de likes (synchronisé via trigger challenge_likes)',
  `ordre` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage admin (drag-and-drop)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `est_payant` tinyint(1) DEFAULT 0,
  `prix` decimal(10,3) DEFAULT 0.000,
  `devise` varchar(3) DEFAULT 'TND',
  `mode_paiement` varchar(50) DEFAULT NULL,
  `stripe_price_id` varchar(100) DEFAULT NULL,
  `flouci_ref` varchar(100) DEFAULT NULL,
  `places_max` int(11) DEFAULT 0,
  `places_reservees` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Défis collaboratifs GaiaLumen';

--
-- Dumping data for table `challenge`
--

INSERT INTO `challenge` (`id`, `titre`, `description`, `type`, `objectif`, `valeur_cible`, `date_debut`, `date_fin`, `statut`, `streak_icon`, `image`, `nb_vues`, `nb_likes`, `ordre`, `created_at`, `updated_at`, `est_payant`, `prix`, `devise`, `mode_paiement`, `stripe_price_id`, `flouci_ref`, `places_max`, `places_reservees`) VALUES
(1, 'Sport Extrême', 'Relevez le défi de pratiquer un sport extrême pendant 30 jours pour améliorer votre condition physique et votre bien-être mental. Choisissez parmi des activités comme le parapente, le saut à l&#039;élastique ou le kayak pour vous défier et vous amuser.', 'individuel', 'co2', 50, '2026-05-10', '2026-06-09', 'actif', '👟', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 1, 2, 0, '2026-05-09 15:04:53', '2026-05-09 19:29:47', 0, 0.000, 'TND', NULL, NULL, NULL, 0, 0),
(2, 'Défi Sport', 'Faisons bouger notre planète en nous déplaçant de manière écologique et en pratiquant une activité physique régulière ! Marche, vélo, course, natation... choisis ton sport préféré et rejoins le défi pour améliorer ta santé et celle de la Terre.', 'individuel', 'transport', 30, '2026-05-09', '2026-06-09', 'actif', '🏃', 'https://example.com/image-sport-ecolo.jpg', 0, 0, 0, '2026-05-09 18:25:08', '2026-05-09 18:25:08', 0, 0.000, 'TND', NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `challenge_ai_summaries`
--

CREATE TABLE `challenge_ai_summaries` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`summary_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `challenge_ai_summaries`
--

INSERT INTO `challenge_ai_summaries` (`id`, `challenge_id`, `summary_json`, `created_at`) VALUES
(1, 1, '{\"score_sante\":60,\"synthese_participants\":\"Le défi compte actuellement un seul participant, ce qui peut indiquer un manque d\'intérêt ou de visibilité pour le défi. Le participant semble avoir une motivation moyenne, avec une progression de 50% mais un engagement très faible de 1%.\",\"tendances_engagement\":\"La progression moyenne est actuellement de 50%, ce qui suggère que le participant est sur la bonne voie pour atteindre l\'objectif cible. Cependant, l\'engagement moyen très faible de 1% indique un risque de désintérêt ou d\'abandon du défi.\",\"points_vigilance\":\"Le faible nombre de participants et l\'engagement très faible sont des points de vigilance majeurs pour la réussite de ce défi, car ils peuvent entraîner un manque de motivation et une baisse de la progression.\",\"recommandations\":[\"Augmenter la visibilité du défi pour attirer plus de participants et stimuler la concurrence\",\"Proposer des incitations ou des récompenses pour motiver le participant à augmenter son engagement et sa progression\",\"Fournir un suivi régulier et des conseils personnalisés pour aider le participant à atteindre son objectif cible\"]}', '2026-05-09 22:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `challenge_likes`
--

CREATE TABLE `challenge_likes` (
  `id` int(11) NOT NULL,
  `id_challenge` int(11) NOT NULL,
  `id_user` int(11) NOT NULL DEFAULT 1 COMMENT 'ID utilisateur (session PHP)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Likes des défis (un par utilisateur)';

--
-- Dumping data for table `challenge_likes`
--

INSERT INTO `challenge_likes` (`id`, `id_challenge`, `id_user`, `created_at`) VALUES
(1, 1, 1, '2026-05-09 19:29:47');

--
-- Triggers `challenge_likes`
--
DELIMITER $$
CREATE TRIGGER `trg_like_delete` AFTER DELETE ON `challenge_likes` FOR EACH ROW BEGIN
    UPDATE `challenge`
    SET `nb_likes` = GREATEST(`nb_likes` - 1, 0)
    WHERE `id` = OLD.id_challenge;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_like_insert` AFTER INSERT ON `challenge_likes` FOR EACH ROW BEGIN
    UPDATE `challenge`
    SET `nb_likes` = `nb_likes` + 1
    WHERE `id` = NEW.id_challenge;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_live_streams`
--

CREATE TABLE `challenge_live_streams` (
  `id` char(36) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `requested_by_user_id` int(11) DEFAULT NULL,
  `requested_by_participant_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `stream_url` varchar(700) NOT NULL,
  `provider` varchar(60) NOT NULL DEFAULT 'external',
  `status` enum('pending','approved','rejected','live','ended') NOT NULL DEFAULT 'pending',
  `approved_by_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_videos`
--

CREATE TABLE `challenge_videos` (
  `id` char(36) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `uploader_user_id` int(11) DEFAULT NULL,
  `uploader_participant_id` int(11) DEFAULT NULL,
  `title` varchar(180) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `url` varchar(600) NOT NULL,
  `storage_path` varchar(900) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size_bytes` int(11) NOT NULL DEFAULT 0,
  `sha256` char(64) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `moderated_by_user_id` int(11) DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_attachments`
--

CREATE TABLE `chat_attachments` (
  `id` int(11) NOT NULL,
  `message_id` char(36) NOT NULL,
  `type` enum('image','audio','video') NOT NULL,
  `url` varchar(600) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size_bytes` int(11) NOT NULL DEFAULT 0,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` char(36) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `author_name` varchar(150) NOT NULL,
  `body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_notifications`
--

CREATE TABLE `chat_notifications` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `message_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_threads`
--

INSERT INTO `chat_threads` (`id`, `challenge_id`, `created_at`) VALUES
(1, 1, '2026-05-09 15:04:53'),
(301, 2, '2026-05-09 18:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('private','group') DEFAULT 'private',
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `demandeplanning`
--

CREATE TABLE `demandeplanning` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `calories` int(11) NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `type_budget` varchar(20) NOT NULL COMMENT 'jour | semaine | mois',
  `duree` int(11) NOT NULL,
  `type_duree` varchar(20) NOT NULL COMMENT 'jours | semaines | mois',
  `statut` enum('en_attente','approuve','rejete') NOT NULL DEFAULT 'en_attente',
  `date_demande` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `demandeplanning`
--

INSERT INTO `demandeplanning` (`id`, `id_utilisateur`, `calories`, `budget`, `type_budget`, `duree`, `type_duree`, `statut`, `date_demande`) VALUES
(16, 1, 2000, 50.00, 'quotidien', 7, 'jours', 'approuve', '2026-05-09 20:17:13'),
(17, 1, 2000, 50.00, 'quotidien', 7, 'jours', 'approuve', '2026-05-09 20:20:52'),
(18, 1, 2000, 50.00, 'quotidien', 6, 'semaines', 'approuve', '2026-05-09 20:26:03');

-- --------------------------------------------------------

--
-- Table structure for table `dossier_medical`
--

CREATE TABLE `dossier_medical` (
  `id_dossier` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `id_regime` int(10) UNSIGNED DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `groupe_sanguin` varchar(10) DEFAULT NULL,
  `poids` float DEFAULT NULL,
  `taille` float DEFAULT NULL,
  `imc` float GENERATED ALWAYS AS (`poids` / (`taille` / 100 * (`taille` / 100))) STORED,
  `regime_special` varchar(100) DEFAULT NULL,
  `notes_medecin` text DEFAULT NULL,
  `allergie` text DEFAULT NULL,
  `gravite_allergie` enum('légère','modérée','sévère','anaphylactique') DEFAULT NULL,
  `maladies` text DEFAULT NULL,
  `traitement` text DEFAULT NULL,
  `medecin` varchar(150) DEFAULT NULL,
  `contact_en_cas_durgence` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evenement`
--

CREATE TABLE `evenement` (
  `id_event` int(10) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `heure` time NOT NULL,
  `type` enum('repas','sport','medical','atelier') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evenement`
--

INSERT INTO `evenement` (`id_event`, `titre`, `description`, `date`, `heure`, `type`) VALUES
(1, 'Défi Sport Extrême', 'azertyui', '2026-05-10', '11:22:00', 'medical'),
(2, 'Défi #12 : Bien-être Challenge', 'azertyuiop', '2026-05-01', '20:10:00', 'sport');

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','rejected','blocked') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `type` enum('text','image','file') DEFAULT 'text',
  `file_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('friend_request','friend_accept','message','system') DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paiement_defi`
--

CREATE TABLE `paiement_defi` (
  `id` int(11) NOT NULL,
  `id_challenge` int(11) NOT NULL,
  `id_participant` int(11) DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `montant` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statut` enum('en_attente','paye','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
  `methode` varchar(50) NOT NULL DEFAULT 'simulation',
  `reference_transaction` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `participant`
--

CREATE TABLE `participant` (
  `id` int(11) NOT NULL,
  `id_challenge` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `objectif` int(11) NOT NULL DEFAULT 0 COMMENT 'Objectif personnel du participant (0-100%)',
  `motivation` text DEFAULT NULL COMMENT 'Message de motivation',
  `action` varchar(500) DEFAULT NULL COMMENT 'Plan d action concret',
  `engagement` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = très engagé, 0 = passif',
  `notifications` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = accepte les emails, 0 = non',
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `points` int(11) DEFAULT 0,
  `days_active` int(11) DEFAULT 1,
  `smart_score` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Participants inscrits aux défis';

--
-- Dumping data for table `participant`
--

INSERT INTO `participant` (`id`, `id_challenge`, `nom`, `email`, `objectif`, `motivation`, `action`, `engagement`, `notifications`, `date_inscription`, `points`, `days_active`, `smart_score`) VALUES
(1, 1, 'kosay labidi', 'koussaylabidi9@gmail.com', 50, 'kosay labidi', 'kosay labidi', 1, 1, '2026-05-09 16:34:13', 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(10) NOT NULL,
  `id_event` int(11) NOT NULL,
  `nom_complet` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `centre_interet` varchar(255) DEFAULT NULL,
  `statut` enum('en_attente','confirmée','annulée') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participation`
--

INSERT INTO `participation` (`id_participation`, `id_event`, `nom_complet`, `email`, `telephone`, `centre_interet`, `statut`) VALUES
(1, 1, 'Kousay labidi', 'koussaylabidi9@gmail.com', '20953884', 'koussay labidi', 'annulée'),
(2, 2, 'Koussay Labidi', 'koussaylabidi9@gmail.com', '20953884', 'koussay labidi', 'confirmée');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`, `used`) VALUES
(1, 'koussaylabidi9@gmail.com', '273c68f3409e24b344871bf9619e73a454ee0eceadfd32f572ad381471307545', '2026-05-09 23:11:27', '2026-05-10 01:11:27', 1),
(2, 'koussaylabidi9@gmail.com', '74b3ac061b2f96fcfe1bb169cdfcf6a0f65f3e4a261bead8f74201c1e9bf8c1b', '2026-05-10 10:52:25', '2026-05-10 12:52:25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `planning`
--

CREATE TABLE `planning` (
  `id` int(10) NOT NULL,
  `id_demande` int(11) NOT NULL,
  `date` date NOT NULL,
  `type_activite` enum('repas','sport','sommeil') NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `planning`
--

INSERT INTO `planning` (`id`, `id_demande`, `date`, `type_activite`, `description`, `created_at`) VALUES
(1, 16, '2026-05-09', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:17:58'),
(2, 16, '2026-05-09', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(3, 16, '2026-05-09', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(4, 16, '2026-05-09', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(5, 16, '2026-05-09', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(6, 16, '2026-05-10', 'repas', 'Avoine + banane + miel | 450 kcal | 10 EUR', '2026-05-09 19:17:58'),
(7, 16, '2026-05-10', 'repas', 'Couscous poulet + légumes + pois chiches | 900 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(8, 16, '2026-05-10', 'repas', 'Gratin dauphinois + steak haché | 820 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(9, 16, '2026-05-10', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(10, 16, '2026-05-10', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(11, 16, '2026-05-11', 'repas', 'Granola + lait entier + fruits secs | 620 kcal | 10 EUR', '2026-05-09 19:17:58'),
(12, 16, '2026-05-11', 'repas', 'Bowl saumon + quinoa + avocat | 650 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(13, 16, '2026-05-11', 'repas', 'Risotto aux champignons | 500 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(14, 16, '2026-05-11', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(15, 16, '2026-05-11', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(16, 16, '2026-05-12', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:17:58'),
(17, 16, '2026-05-12', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(18, 16, '2026-05-12', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(19, 16, '2026-05-12', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(20, 16, '2026-05-12', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(21, 16, '2026-05-13', 'repas', 'Avoine + banane + miel | 450 kcal | 10 EUR', '2026-05-09 19:17:58'),
(22, 16, '2026-05-13', 'repas', 'Couscous poulet + légumes + pois chiches | 900 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(23, 16, '2026-05-13', 'repas', 'Gratin dauphinois + steak haché | 820 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(24, 16, '2026-05-13', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(25, 16, '2026-05-13', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(26, 16, '2026-05-14', 'repas', 'Granola + lait entier + fruits secs | 620 kcal | 10 EUR', '2026-05-09 19:17:58'),
(27, 16, '2026-05-14', 'repas', 'Bowl saumon + quinoa + avocat | 650 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(28, 16, '2026-05-14', 'repas', 'Risotto aux champignons | 500 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(29, 16, '2026-05-14', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(30, 16, '2026-05-14', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(31, 16, '2026-05-15', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:17:58'),
(32, 16, '2026-05-15', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:17:58'),
(33, 16, '2026-05-15', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:17:58'),
(34, 16, '2026-05-15', 'sport', 'cardio — 13min', '2026-05-09 19:17:58'),
(35, 16, '2026-05-15', 'sommeil', '23:00 - 06:40 (7.7h)', '2026-05-09 19:17:58'),
(36, 17, '2026-05-09', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:21:53'),
(37, 17, '2026-05-09', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(38, 17, '2026-05-09', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(39, 17, '2026-05-09', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(40, 17, '2026-05-09', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(41, 17, '2026-05-10', 'repas', 'Avoine + banane + miel | 450 kcal | 10 EUR', '2026-05-09 19:21:53'),
(42, 17, '2026-05-10', 'repas', 'Couscous poulet + légumes + pois chiches | 900 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(43, 17, '2026-05-10', 'repas', 'Gratin dauphinois + steak haché | 820 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(44, 17, '2026-05-10', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(45, 17, '2026-05-10', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(46, 17, '2026-05-11', 'repas', 'Granola + lait entier + fruits secs | 620 kcal | 10 EUR', '2026-05-09 19:21:53'),
(47, 17, '2026-05-11', 'repas', 'Bowl saumon + quinoa + avocat | 650 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(48, 17, '2026-05-11', 'repas', 'Risotto aux champignons | 500 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(49, 17, '2026-05-11', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(50, 17, '2026-05-11', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(51, 17, '2026-05-12', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:21:53'),
(52, 17, '2026-05-12', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(53, 17, '2026-05-12', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(54, 17, '2026-05-12', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(55, 17, '2026-05-12', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(56, 17, '2026-05-13', 'repas', 'Avoine + banane + miel | 450 kcal | 10 EUR', '2026-05-09 19:21:53'),
(57, 17, '2026-05-13', 'repas', 'Couscous poulet + légumes + pois chiches | 900 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(58, 17, '2026-05-13', 'repas', 'Gratin dauphinois + steak haché | 820 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(59, 17, '2026-05-13', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(60, 17, '2026-05-13', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(61, 17, '2026-05-14', 'repas', 'Granola + lait entier + fruits secs | 620 kcal | 10 EUR', '2026-05-09 19:21:53'),
(62, 17, '2026-05-14', 'repas', 'Bowl saumon + quinoa + avocat | 650 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(63, 17, '2026-05-14', 'repas', 'Risotto aux champignons | 500 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(64, 17, '2026-05-14', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(65, 17, '2026-05-14', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53'),
(66, 17, '2026-05-15', 'repas', 'Pain complet + beurre de cacahuète | 520 kcal | 10 EUR', '2026-05-09 19:21:53'),
(67, 17, '2026-05-15', 'repas', 'Pâtes bolognaise maison | 780 kcal | 22.5 EUR', '2026-05-09 19:21:53'),
(68, 17, '2026-05-15', 'repas', 'Wrap poulet + légumes rôtis | 630 kcal | 17.5 EUR', '2026-05-09 19:21:53'),
(69, 17, '2026-05-15', 'sport', 'cardio — 13min', '2026-05-09 19:21:53'),
(70, 17, '2026-05-15', 'sommeil', '20:20 - 06:09 (9.8h)', '2026-05-09 19:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `regimes`
--

CREATE TABLE `regimes` (
  `id_regime` int(10) UNSIGNED NOT NULL,
  `nom_regime` varchar(100) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type_regime` enum('alimentaire','medical','sportif','perte_de_poids','prise_de_masse','autre') NOT NULL,
  `niveau_difficulte` enum('facile','modere','avance') NOT NULL,
  `aliments_interdits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aliments_interdits`)),
  `aliments_recommandes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aliments_recommandes`)),
  `apport_calorique_moyen` float DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repas`
--

CREATE TABLE `repas` (
  `id_repas` int(10) UNSIGNED NOT NULL,
  `nom_repas` varchar(150) NOT NULL,
  `date_repas` datetime NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repas_aliments`
--

CREATE TABLE `repas_aliments` (
  `id_repas` int(10) UNSIGNED NOT NULL,
  `id_aliment` int(10) UNSIGNED NOT NULL,
  `quantite` float NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sportsommeil`
--

CREATE TABLE `sportsommeil` (
  `id` int(10) NOT NULL,
  `id_demande` int(11) NOT NULL,
  `activite_sportive` varchar(100) NOT NULL,
  `duree_sport_hebdo` int(11) NOT NULL COMMENT 'en minutes',
  `heure_coucher` time NOT NULL,
  `heure_reveil` time NOT NULL,
  `qualite_sommeil` enum('mauvaise','passable','bonne','excellente') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sportsommeil`
--

INSERT INTO `sportsommeil` (`id`, `id_demande`, `activite_sportive`, `duree_sport_hebdo`, `heure_coucher`, `heure_reveil`, `qualite_sommeil`, `created_at`) VALUES
(1, 16, 'cardio', 90, '23:00:00', '06:40:00', 'bonne', '2026-05-09 19:17:45'),
(2, 17, 'cardio', 90, '20:20:00', '06:09:00', 'passable', '2026-05-09 19:21:30');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mdp` varchar(255) NOT NULL,
  `role` enum('utilisateur','nutritionniste','ecologiste','admin') DEFAULT 'utilisateur',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `provider` varchar(50) DEFAULT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `status` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `created_at` datetime DEFAULT current_timestamp(),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = non vÃ©rifiÃ©, 1 = vÃ©rifiÃ©',
  `verification_code` varchar(6) DEFAULT NULL COMMENT 'Code Ã  6 chiffres envoyÃ© par email',
  `verification_expires` datetime DEFAULT NULL COMMENT 'Expiration du code (15 minutes)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `phone`, `mdp`, `role`, `date_creation`, `date_mise_a_jour`, `provider`, `provider_id`, `photo`, `avatar`, `status`, `created_at`, `is_verified`, `verification_code`, `verification_expires`) VALUES
(1, 'Doe', 'John', 'john.doe@example.com', NULL, '$2y$10$vhph4iOW5MEbMC4uDxXmpeZvtIp3.8TTu35H0ciFJC3DmUHeAcEEm', 'utilisateur', '2026-05-09 18:16:52', '2026-05-09 18:16:52', NULL, NULL, NULL, NULL, 'actif', '2026-05-09 19:16:52', 1, NULL, NULL),
(2, 'labidi', 'Kousay', 'koussaylabidi9@gmail.com', NULL, '$2y$10$M.9E6IwgvZZNE2ykDEF5E.KQ.NHY6PcKcs8B1Xao9yGi0zj8BPSQq', 'utilisateur', '2026-05-09 22:04:11', '2026-05-10 11:26:04', NULL, NULL, NULL, NULL, 'actif', '2026-05-09 23:04:11', 0, NULL, NULL),
(3, 'Admin', 'GaiaLumen', 'admin@gaialumen.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-05-10 10:27:48', '2026-05-10 11:13:52', NULL, NULL, 'uploads/profiles/user_3_1778411588.jpg', 'uploads/avatars/avatar_3_1778411632.png', 'actif', '2026-05-10 11:27:48', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_challenge_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_challenge_stats` (
`id` int(11)
,`titre` varchar(255)
,`type` varchar(100)
,`statut` enum('en_attente','actif','termine','accepte','refuse')
,`valeur_cible` int(11)
,`date_debut` date
,`date_fin` date
,`nb_vues` int(10) unsigned
,`nb_likes` int(10) unsigned
,`ordre` int(11)
,`nb_participants` bigint(21)
,`avg_engagement` decimal(7,4)
,`jours_restants` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_participants_details`
-- (See below for the actual view)
--
CREATE TABLE `v_participants_details` (
`id` int(11)
,`id_challenge` int(11)
,`nom` varchar(150)
,`email` varchar(255)
,`objectif` int(11)
,`motivation` text
,`action` varchar(500)
,`engagement` tinyint(1)
,`notifications` tinyint(1)
,`date_inscription` timestamp
,`challenge_titre` varchar(255)
,`challenge_icon` varchar(20)
,`challenge_statut` enum('en_attente','actif','termine','accepte','refuse')
,`challenge_target` int(11)
,`challenge_fin` date
,`jours_restants_defi` int(7)
);

-- --------------------------------------------------------

--
-- Structure for view `v_challenge_stats`
--
DROP TABLE IF EXISTS `v_challenge_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_challenge_stats`  AS SELECT `c`.`id` AS `id`, `c`.`titre` AS `titre`, `c`.`type` AS `type`, `c`.`statut` AS `statut`, `c`.`valeur_cible` AS `valeur_cible`, `c`.`date_debut` AS `date_debut`, `c`.`date_fin` AS `date_fin`, `c`.`nb_vues` AS `nb_vues`, `c`.`nb_likes` AS `nb_likes`, `c`.`ordre` AS `ordre`, count(`p`.`id`) AS `nb_participants`, coalesce(avg(`p`.`engagement`),0) AS `avg_engagement`, to_days(`c`.`date_fin`) - to_days(curdate()) AS `jours_restants` FROM (`challenge` `c` left join `participant` `p` on(`p`.`id_challenge` = `c`.`id`)) GROUP BY `c`.`id`, `c`.`titre`, `c`.`type`, `c`.`statut`, `c`.`valeur_cible`, `c`.`date_debut`, `c`.`date_fin`, `c`.`nb_vues`, `c`.`nb_likes`, `c`.`ordre` ;

-- --------------------------------------------------------

--
-- Structure for view `v_participants_details`
--
DROP TABLE IF EXISTS `v_participants_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_participants_details`  AS SELECT `p`.`id` AS `id`, `p`.`id_challenge` AS `id_challenge`, `p`.`nom` AS `nom`, `p`.`email` AS `email`, `p`.`objectif` AS `objectif`, `p`.`motivation` AS `motivation`, `p`.`action` AS `action`, `p`.`engagement` AS `engagement`, `p`.`notifications` AS `notifications`, `p`.`date_inscription` AS `date_inscription`, `c`.`titre` AS `challenge_titre`, `c`.`streak_icon` AS `challenge_icon`, `c`.`statut` AS `challenge_statut`, `c`.`valeur_cible` AS `challenge_target`, `c`.`date_fin` AS `challenge_fin`, to_days(`c`.`date_fin`) - to_days(curdate()) AS `jours_restants_defi` FROM (`participant` `p` left join `challenge` `c` on(`c`.`id` = `p`.`id_challenge`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aliments`
--
ALTER TABLE `aliments`
  ADD PRIMARY KEY (`id_aliment`);

--
-- Indexes for table `challenge`
--
ALTER TABLE `challenge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date_debut` (`date_debut`),
  ADD KEY `idx_date_fin` (`date_fin`),
  ADD KEY `idx_ordre` (`ordre`);

--
-- Indexes for table `challenge_ai_summaries`
--
ALTER TABLE `challenge_ai_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_challenge` (`challenge_id`);

--
-- Indexes for table `challenge_likes`
--
ALTER TABLE `challenge_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_like_user_challenge` (`id_challenge`,`id_user`);

--
-- Indexes for table `challenge_live_streams`
--
ALTER TABLE `challenge_live_streams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_challenge_live_challenge_status` (`challenge_id`,`status`,`created_at`),
  ADD KEY `idx_challenge_live_participant` (`requested_by_participant_id`);

--
-- Indexes for table `challenge_videos`
--
ALTER TABLE `challenge_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_challenge_videos_challenge_status` (`challenge_id`,`status`,`created_at`),
  ADD KEY `idx_challenge_videos_participant` (`uploader_participant_id`);

--
-- Indexes for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_attachments_message` (`message_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_messages_thread_time` (`thread_id`,`created_at`),
  ADD KEY `idx_chat_messages_parent` (`parent_id`),
  ADD KEY `idx_chat_messages_participant` (`participant_id`);

--
-- Indexes for table `chat_notifications`
--
ALTER TABLE `chat_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_notif_part_msg` (`participant_id`,`message_id`),
  ADD KEY `idx_chat_notif_part_read` (`participant_id`,`read_at`),
  ADD KEY `fk_chat_notif_message` (`message_id`);

--
-- Indexes for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chat_threads_challenge` (`challenge_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `demandeplanning`
--
ALTER TABLE `demandeplanning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indexes for table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD PRIMARY KEY (`id_dossier`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `fk_dossier_regime` (`id_regime`);

--
-- Indexes for table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_event`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`sender_id`,`receiver_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `paiement_defi`
--
ALTER TABLE `paiement_defi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_paiement_challenge` (`id_challenge`),
  ADD KEY `idx_paiement_participant` (`id_participant`),
  ADD KEY `idx_paiement_email` (`email`);

--
-- Indexes for table `participant`
--
ALTER TABLE `participant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_participant_email_challenge` (`email`,`id_challenge`),
  ADD KEY `idx_id_challenge` (`id_challenge`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_engagement` (`engagement`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD KEY `id_event` (`id_event`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_demande` (`id_demande`);

--
-- Indexes for table `regimes`
--
ALTER TABLE `regimes`
  ADD PRIMARY KEY (`id_regime`),
  ADD UNIQUE KEY `nom_regime` (`nom_regime`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `repas`
--
ALTER TABLE `repas`
  ADD PRIMARY KEY (`id_repas`);

--
-- Indexes for table `repas_aliments`
--
ALTER TABLE `repas_aliments`
  ADD PRIMARY KEY (`id_repas`,`id_aliment`),
  ADD KEY `fk_ra_aliment` (`id_aliment`);

--
-- Indexes for table `sportsommeil`
--
ALTER TABLE `sportsommeil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sportsommeil_demande` (`id_demande`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_verification_code` (`verification_code`,`verification_expires`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aliments`
--
ALTER TABLE `aliments`
  MODIFY `id_aliment` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `challenge`
--
ALTER TABLE `challenge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `challenge_ai_summaries`
--
ALTER TABLE `challenge_ai_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `challenge_likes`
--
ALTER TABLE `challenge_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_notifications`
--
ALTER TABLE `chat_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1208;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `demandeplanning`
--
ALTER TABLE `demandeplanning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  MODIFY `id_dossier` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_event` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paiement_defi`
--
ALTER TABLE `paiement_defi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participant`
--
ALTER TABLE `participant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `regimes`
--
ALTER TABLE `regimes`
  MODIFY `id_regime` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `repas`
--
ALTER TABLE `repas`
  MODIFY `id_repas` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sportsommeil`
--
ALTER TABLE `sportsommeil`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `challenge_ai_summaries`
--
ALTER TABLE `challenge_ai_summaries`
  ADD CONSTRAINT `fk_summary_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_likes`
--
ALTER TABLE `challenge_likes`
  ADD CONSTRAINT `fk_like_challenge` FOREIGN KEY (`id_challenge`) REFERENCES `challenge` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_live_streams`
--
ALTER TABLE `challenge_live_streams`
  ADD CONSTRAINT `fk_challenge_live_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_challenge_live_participant` FOREIGN KEY (`requested_by_participant_id`) REFERENCES `participant` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `challenge_videos`
--
ALTER TABLE `challenge_videos`
  ADD CONSTRAINT `fk_challenge_videos_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_challenge_videos_participant` FOREIGN KEY (`uploader_participant_id`) REFERENCES `participant` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD CONSTRAINT `fk_chat_attachments_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_parent` FOREIGN KEY (`parent_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_notifications`
--
ALTER TABLE `chat_notifications`
  ADD CONSTRAINT `fk_chat_notif_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_notif_participant` FOREIGN KEY (`participant_id`) REFERENCES `participant` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD CONSTRAINT `fk_chat_threads_challenge` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `demandeplanning`
--
ALTER TABLE `demandeplanning`
  ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD CONSTRAINT `fk_dossier_regime` FOREIGN KEY (`id_regime`) REFERENCES `regimes` (`id_regime`) ON DELETE SET NULL;

--
-- Constraints for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD CONSTRAINT `friend_requests_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_requests_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `participant`
--
ALTER TABLE `participant`
  ADD CONSTRAINT `fk_participant_challenge` FOREIGN KEY (`id_challenge`) REFERENCES `challenge` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`id_event`) REFERENCES `evenement` (`id_event`) ON DELETE CASCADE;

--
-- Constraints for table `planning`
--
ALTER TABLE `planning`
  ADD CONSTRAINT `fk_planning_demande` FOREIGN KEY (`id_demande`) REFERENCES `demandeplanning` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `repas_aliments`
--
ALTER TABLE `repas_aliments`
  ADD CONSTRAINT `fk_ra_aliment` FOREIGN KEY (`id_aliment`) REFERENCES `aliments` (`id_aliment`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ra_repas` FOREIGN KEY (`id_repas`) REFERENCES `repas` (`id_repas`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sportsommeil`
--
ALTER TABLE `sportsommeil`
  ADD CONSTRAINT `fk_sportsommeil_demande` FOREIGN KEY (`id_demande`) REFERENCES `demandeplanning` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
