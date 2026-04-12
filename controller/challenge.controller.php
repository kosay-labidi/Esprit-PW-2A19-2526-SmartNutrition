<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Challenge.php');

class ChallengeController {

    public function addChallenge(Challenge $challenge) {
        $sql = "INSERT INTO challenge (titre, description, type, objectif, valeur_cible, date_debut, date_fin, statut, streak_icon, image) 
                VALUES (:titre, :description, :type, :objectif, :valeur_cible, :date_debut, :date_fin, :statut, :streak_icon, :image)";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'titre' => $challenge->getTitre(),
                'description' => $challenge->getDescription(),
                'type' => $challenge->getType(),
                'objectif' => $challenge->getObjectif(),
                'valeur_cible' => $challenge->getValeurCible(),
                'date_debut' => $challenge->getDateDebut(),
                'date_fin' => $challenge->getDateFin(),
                'statut' => $challenge->getStatut(),
                'streak_icon' => $challenge->getStreakIcon(),
                'image' => $challenge->getImage()
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function listChallenges() {
        $sql = "SELECT * FROM challenge";
        $db = Config::getConnexion();
        try {
            $list = $db->query($sql);
            return $list->fetchAll();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function deleteChallenge($id) {
        $sql = "DELETE FROM challenge WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function showChallenge($id) {
        $sql = "SELECT * FROM challenge WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            return $query->fetch();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function updateChallenge($challenge, $id) {
        $sql = "UPDATE challenge SET titre = :titre, description = :description, type = :type, objectif = :objectif, valeur_cible = :valeur_cible, date_debut = :date_debut, date_fin = :date_fin, statut = :statut, streak_icon = :streak_icon, image = :image WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'titre' => $challenge->getTitre(),
                'description' => $challenge->getDescription(),
                'type' => $challenge->getType(),
                'objectif' => $challenge->getObjectif(),
                'valeur_cible' => $challenge->getValeurCible(),
                'date_debut' => $challenge->getDateDebut(),
                'date_fin' => $challenge->getDateFin(),
                'statut' => $challenge->getStatut(),
                'streak_icon' => $challenge->getStreakIcon(),
                'image' => $challenge->getImage(),
                'id' => $id
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

}
?>
