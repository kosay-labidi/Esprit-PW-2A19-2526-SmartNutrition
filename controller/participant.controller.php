<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Participant.php');

class ParticipantController {

    public function addParticipant(Participant $participant) {
        $sql = "INSERT INTO participant (id_challenge, nom, email, objectif, motivation, action, engagement, notifications, score, xp, level)
                VALUES (:id_challenge, :nom, :email, :objectif, :motivation, :action, :engagement, :notifications, :score, :xp, :level)";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_challenge' => $participant->getIdChallenge(),
                'nom' => $participant->getNom(),
                'email' => $participant->getEmail(),
                'objectif' => $participant->getObjectif(),
                'motivation' => $participant->getMotivation(),
                'action' => $participant->getAction(),
                'engagement' => $participant->getEngagement(),
                'notifications' => $participant->getNotifications(),
                'score' => $participant->getScore() ?? 0,
                'xp' => $participant->getXp() ?? 0,
                'level' => $participant->getLevel() ?? 1
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Error adding participant: ' . $e->getMessage());
            return false;
        }
    }

    public function listParticipants($id_challenge = null) {
        $sql = "SELECT p.*, c.titre AS challenge_titre, c.streak_icon AS challenge_icon, c.valeur_cible AS challenge_target
                FROM participant p
                LEFT JOIN challenge c ON c.id = p.id_challenge";

        $params = [];
        if ($id_challenge !== null) {
            $sql .= " WHERE p.id_challenge = :id_challenge";
            $params['id_challenge'] = (int)$id_challenge;
        }

        $sql .= " ORDER BY p.date_inscription DESC, p.id DESC";

        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute($params);
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log('Error listing participants: ' . $e->getMessage());
            return [];
        }
    }

    public function listParticipantsByChallenge($id_challenge) {
        $sql = "SELECT * FROM participant WHERE id_challenge = :id_challenge";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id_challenge' => $id_challenge]);
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log('Error listing participants by challenge: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteParticipant($id) {
        $sql = "DELETE FROM participant WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return true;
        } catch (Exception $e) {
            error_log('Error deleting participant: ' . $e->getMessage());
            return false;
        }
    }

    public function showParticipant($id) {
        $sql = "SELECT * FROM participant WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return $query->fetch();
        } catch (Exception $e) {
            error_log('Error showing participant: ' . $e->getMessage());
            return null;
        }
    }

    public function updateParticipant(Participant $participant, $id) {
        $sql = "UPDATE participant SET 
                id_challenge = :id_challenge, 
                nom = :nom, 
                email = :email, 
                objectif = :objectif, 
                motivation = :motivation, 
                action = :action, 
                engagement = :engagement, 
                notifications = :notifications,
                score = :score,
                xp = :xp,
                level = :level
                WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_challenge' => $participant->getIdChallenge(),
                'nom' => $participant->getNom(),
                'email' => $participant->getEmail(),
                'objectif' => $participant->getObjectif(),
                'motivation' => $participant->getMotivation(),
                'action' => $participant->getAction(),
                'engagement' => $participant->getEngagement(),
                'notifications' => $participant->getNotifications(),
                'score' => $participant->getScore(),
                'xp' => $participant->getXp(),
                'level' => $participant->getLevel(),
                'id' => (int)$id
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Error updating participant: ' . $e->getMessage());
            return false;
        }
    }
}
?>
