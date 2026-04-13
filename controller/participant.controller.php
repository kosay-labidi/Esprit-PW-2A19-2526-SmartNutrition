<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Participant.php');

class ParticipantController {

    public function addParticipant(Participant $participant) {
        $sql = "INSERT INTO participant (id_challenge, nom, email, objectif, motivation, action, engagement, notifications) 
                VALUES (:id_challenge, :nom, :email, :objectif, :motivation, :action, :engagement, :notifications)";
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
                'notifications' => $participant->getNotifications()
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Error adding participant: ' . $e->getMessage());
            return false;
        }
    }

    public function listParticipants($id_challenge = null) {
        $sql = "SELECT p.*, c.titre AS challenge_titre, c.streak_icon AS challenge_icon
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
            die('Error: ' . $e->getMessage());
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
            die('Error: ' . $e->getMessage());
        }
    }
}
?>
