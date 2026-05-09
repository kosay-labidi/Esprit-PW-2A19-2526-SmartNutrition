<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Participation.php';

class ParticipationController {

    public function listParticipations() {
        $sql = "SELECT p.*, e.titre as evenement_titre 
                FROM participation p 
                JOIN evenement e ON p.id_event = e.id_event";
        $db = config::getConnexion();
        try {
            return $db->query($sql);
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function listParticipationsByEvent($id_event) {
        $sql = "SELECT p.*, e.titre as evenement_titre 
                FROM participation p 
                JOIN evenement e ON p.id_event = e.id_event
                WHERE p.id_event = :id_event";
        $db = config::getConnexion();
        try {
            $req = $db->prepare($sql);
            $req->execute(['id_event' => $id_event]);
            return $req;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function getEvenementTitre($id_event) {
        $sql = "SELECT titre FROM evenement WHERE id_event = :id_event";
        $db = config::getConnexion();
        try {
            $req = $db->prepare($sql);
            $req->execute(['id_event' => $id_event]);
            $row = $req->fetch();
            return $row ? $row['titre'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function deleteParticipation($id) {
        $sql = "DELETE FROM participation WHERE id_participation = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    public function addParticipation(Participation $participation) {
        $sql = "INSERT INTO participation (id_event, nom_complet, email, telephone, centre_interet, statut) 
                VALUES (:id_event, :nom_complet, :email, :telephone, :centre_interet, 'en_attente')";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            return $query->execute([
                'id_event'       => $participation->getIdEvent(),
                'nom_complet'    => $participation->getNomComplet(),
                'email'          => $participation->getEmail(),
                'telephone'      => $participation->getTelephone(),
                'centre_interet' => $participation->getCentreInteret()
            ]);
        } catch (Exception $e) {
            error_log('Erreur addParticipation: ' . $e->getMessage());
            return false;
        }
    }

    public function showParticipation($id) {
        $sql = "SELECT p.*, e.titre as evenement_titre 
                FROM participation p 
                JOIN evenement e ON p.id_event = e.id_event 
                WHERE p.id_participation = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }

    public function updateParticipationStatut($id, $statut) {
        $sql = "UPDATE participation SET statut = :statut WHERE id_participation = :id";
        $db = config::getConnexion();
        try {
            $req = $db->prepare($sql);
            return $req->execute(['statut' => $statut, 'id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur updateParticipationStatut: ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────
    //  MÉTIER AVANCÉ : Met à jour le statut + envoie l'email
    // ─────────────────────────────────────────────────────────
    public function updateStatusWithEmailNotification(int $id, string $statut): array
    {
        // 1. Récupérer les données du participant
        $data = $this->showParticipation($id);
        if (!$data) {
            return ['success' => false, 'email_sent' => false, 'message' => 'Participant introuvable.'];
        }

        // 2. Mettre à jour le statut en base de données
        $updated = $this->updateParticipationStatut($id, $statut);
        if (!$updated) {
            return ['success' => false, 'email_sent' => false, 'message' => 'Erreur lors de la mise à jour.'];
        }

        // 3. Envoyer l'email au participant
        require_once __DIR__ . '/../services/EmailService.php';

        $emailSent = false;
        if (!empty(trim($data['email'] ?? ''))) {
            $emailSent = EmailService::envoyerNotificationStatut(
                $data['email'],
                $data['nom_complet'],
                $data['evenement_titre'] ?? 'un événement',
                $statut
            );
        }

        return [
            'success'    => true,
            'email_sent' => $emailSent,
            'email'      => $data['email'] ?? '',
            'message'    => $emailSent
                ? "Statut mis à jour et email envoyé à {$data['email']}."
                : "Statut mis à jour. Aucun email (adresse manquante).",
        ];
    }
}
?>
