<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Challenge.php');

class ChallengeController {
    // Affiche la liste des défis avec le nombre de participants réel
    public function listChallenges() {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM participants p WHERE p.challenge_id = c.id) as participants_count 
                FROM challenges c";
        $db = Config::getConnexion();
        try {
            $liste = $db->query($sql);
            return $liste->fetchAll();
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    // Ajoute un défi (BACKEND)
    public function addChallenge($challenge) {
        $sql = "INSERT INTO challenges (titre, description, type, objectif, valeur_cible, date_debut, date_fin, statut, streak_icon, image) 
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
            die('Erreur: ' . $e->getMessage());
        }
    }
}
?>
