<?php
require_once __DIR__ . '/config.php';

$db = Config::getConnexion();

echo "=== Configuration de la base de données ===\n";

// Check and create 'evenement' table
echo "\n1) Vérification de la table 'evenement'...\n";
try {
    $db->query("SELECT 1 FROM evenement LIMIT 1");
    echo "   ✓ Table 'evenement' existe déjà.\n";
} catch (Exception $e) {
    echo "   ✗ Table 'evenement' n'existe pas, création en cours...\n";
    $sqlEvenement = "CREATE TABLE `evenement` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sqlEvenement);
    echo "   ✓ Table 'evenement' créée avec succès !\n";
}

// Check and create 'participation' table
echo "\n2) Vérification de la table 'participation'...\n";
try {
    $db->query("SELECT 1 FROM participation LIMIT 1");
    echo "   ✓ Table 'participation' existe déjà.\n";
} catch (Exception $e) {
    echo "   ✗ Table 'participation' n'existe pas, création en cours...\n";
    $sqlParticipation = "CREATE TABLE `participation` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $db->exec($sqlParticipation);
    echo "   ✓ Table 'participation' créée avec succès !\n";
}

// Insert test data into evenement
echo "\n3) Insertion de données de test...\n";
try {
    $checkEvenement = $db->query("SELECT COUNT(*) FROM evenement")->fetchColumn();
    if ($checkEvenement == 0) {
        $testEvenements = [
            ['Atelier Bien-être Naturel', 'Un atelier pour découvrir les bienfaits de la nature', '2026-05-15', '14:00:00', 'atelier'],
            ['Défi Sport Extrême', 'Un défi sportif intense pour tous les niveaux', '2026-05-20', '09:00:00', 'sport'],
            ['Consultation Nutrition', 'Consultation individuelle avec un nutritionniste', '2026-05-18', '10:00:00', 'medical'],
            ['Atelier Cuisine Saine', 'Apprenez à cuisiner des repas sains et délicieux', '2026-05-22', '16:00:00', 'repas']
        ];
        
        $stmt = $db->prepare("INSERT INTO evenement (titre, description, date, heure, type) VALUES (?, ?, ?, ?, ?)");
        foreach ($testEvenements as $evt) {
            $stmt->execute($evt);
        }
        echo "   ✓ 4 événements de test insérés !\n";
    } else {
        echo "   ℹ Des événements existent déjà, pas d'insertion.\n";
    }

    $checkParticipation = $db->query("SELECT COUNT(*) FROM participation")->fetchColumn();
    if ($checkParticipation == 0) {
        $testParticipations = [
            [1, 'Koussay Labidi', 'koussaylabidi9@gmail.com', '20953884', 'yoga', 'confirmée'],
            [1, 'Ahmed Ben Ali', 'ahmed.benali@email.com', '12345678', 'sport', 'en_attente'],
            [2, 'Sarra Toumi', 'sarra.toumi@email.com', '87654321', 'nutrition', 'annulée']
        ];
        
        $stmt = $db->prepare("INSERT INTO participation (id_event, nom_complet, email, telephone, centre_interet, statut) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($testParticipations as $part) {
            $stmt->execute($part);
        }
        echo "   ✓ 3 participations de test insérées !\n";
    } else {
        echo "   ℹ Des participations existent déjà, pas d'insertion.\n";
    }
} catch (Exception $e) {
    echo "   ✗ Erreur insertion données : " . $e->getMessage() . "\n";
}

echo "\n=== Configuration terminée avec succès ! ===\n";
echo "\nPour tester les endpoints API :\n";
echo "- http://localhost/Mainn/setup_db.php\n";
echo "- http://localhost/Mainn/view/backend/evenement/list.php?action=getstats\n";
echo "- http://localhost/Mainn/view/backend/evenement/list.php?action=getall\n";
echo "- http://localhost/Mainn/view/backend/admin.html\n";
?>
