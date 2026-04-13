<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Demandeplanning.php';

class DemandeplanningController {

    // ==================== FRONTEND METHODS ====================

    public function addDemande2(Demandeplanning $demande): void {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("INSERT INTO demandeplanning 
                (id_utilisateur, calories, budget, type_budget, duree, type_duree, date_demande)
                VALUES (:id_utilisateur, :calories, :budget, :type_budget, :duree, :type_duree, NOW())");
            $stmt->execute([
                ':id_utilisateur' => $demande->getIdUtilisateur(),
                ':calories'       => $demande->getCalories(),
                ':budget'         => $demande->getBudget(),
                ':type_budget'    => $demande->getTypeBudget(),
                ':duree'          => $demande->getDuree(),
                ':type_duree'     => $demande->getTypeDuree(),
            ]);
        } catch (PDOException $e) {
            die("Erreur lors de l'insertion : " . $e->getMessage());
        }
    }

    public function addDemande(): array {
        $errors = [];
        $result = ['success' => false, 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        // Retrieve POST data
        $id_utilisateur = isset($_POST['id_utilisateur']) ? trim($_POST['id_utilisateur']) : '';
        $calories = isset($_POST['calories']) ? trim($_POST['calories']) : '';
        $budget = isset($_POST['budget']) ? trim($_POST['budget']) : '';
        $type_budget = isset($_POST['type_budget']) ? trim($_POST['type_budget']) : '';
        $duree = isset($_POST['duree']) ? trim($_POST['duree']) : '';
        $type_duree = isset($_POST['type_duree']) ? trim($_POST['type_duree']) : '';

        // Validation
        if (empty($id_utilisateur)) {
            $errors[] = "L'identifiant utilisateur est obligatoire.";
        } elseif (!is_numeric($id_utilisateur) || intval($id_utilisateur) <= 0) {
            $errors[] = "L'identifiant utilisateur doit être un nombre entier positif.";
        }

        if (empty($calories)) {
            $errors[] = "Le nombre de calories est obligatoire.";
        } elseif (!is_numeric($calories) || intval($calories) <= 0) {
            $errors[] = "Les calories doivent être un nombre supérieur à 0.";
        }

        if (empty($budget)) {
            $errors[] = "Le budget est obligatoire.";
        } elseif (!is_numeric($budget) || floatval($budget) <= 0) {
            $errors[] = "Le budget doit être un nombre supérieur à 0.";
        }

        $allowedTypeBudget = ['quotidien', 'hebdomadaire'];
        if (empty($type_budget)) {
            $errors[] = "Le type de budget est obligatoire.";
        } elseif (!in_array($type_budget, $allowedTypeBudget, true)) {
            $errors[] = "Le type de budget doit être 'quotidien', 'hebdomadaire'.";
        }

        if (empty($duree)) {
            $errors[] = "La durée est obligatoire.";
        } elseif (!is_numeric($duree) || intval($duree) <= 0) {
            $errors[] = "La durée doit être un nombre supérieur à 0.";
        }

        $allowedTypeDuree = ['jours', 'semaines'];
        if (empty($type_duree)) {
            $errors[] = "Le type de durée est obligatoire.";
        } elseif (!in_array($type_duree, $allowedTypeDuree, true)) {
            $errors[] = "Le type de durée doit être 'jours', 'semaines'.";
        }

        if (!empty($errors)) {
            $result['errors'] = $errors;
            return $result;
        }

        // Create object
        $demande = new Demandeplanning(
            null,
            intval($id_utilisateur),
            intval($calories),
            floatval($budget),
            $type_budget,
            intval($duree),
            $type_duree,
            null
        );

        // Insert to database
        try {
            $db = config::getConnexion();
            $sql = "INSERT INTO demandeplanning 
                    (id_utilisateur, calories, budget, type_budget, duree, type_duree, date_demande) 
                    VALUES 
                    (:id_utilisateur, :calories, :budget, :type_budget, :duree, :type_duree, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_utilisateur' => $demande->getIdUtilisateur(),
                ':calories' => $demande->getCalories(),
                ':budget' => $demande->getBudget(),
                ':type_budget' => $demande->getTypeBudget(),
                ':duree' => $demande->getDuree(),
                ':type_duree' => $demande->getTypeDuree()
            ]);

            $result['success'] = true;
            
        } catch (PDOException $e) {
            $result['errors'][] = "Erreur lors de l'insertion : " . $e->getMessage();
        }

        return $result;
    }

    // ==================== BACKEND METHODS ====================

    public function listAllDemandes(): array {
        try {
            $db = config::getConnexion();
            $stmt = $db->query("SELECT * FROM demandeplanning ORDER BY date_demande DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getDemandeById(int $id): ?array {
        try {
            $db = config::getConnexion();
            $stmt = $db->prepare("SELECT * FROM demandeplanning WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function updateDemande(Demandeplanning $demande, int $id): bool {
        try {
            $db = config::getConnexion();
            $sql = "UPDATE demandeplanning SET 
                    id_utilisateur = :id_utilisateur,
                    calories = :calories,
                    budget = :budget,
                    type_budget = :type_budget,
                    duree = :duree,
                    type_duree = :type_duree
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':id_utilisateur' => $demande->getIdUtilisateur(),
                ':calories' => $demande->getCalories(),
                ':budget' => $demande->getBudget(),
                ':type_budget' => $demande->getTypeBudget(),
                ':duree' => $demande->getDuree(),
                ':type_duree' => $demande->getTypeDuree()
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    

}
?>