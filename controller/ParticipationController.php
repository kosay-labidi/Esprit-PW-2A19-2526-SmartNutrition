<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Participation.php';
require_once __DIR__ . '/../helpers/auth_user.php';

class ParticipationController {
    private bool $userSchemaReady = false;

    private function ensureUserSchema(): void {
        if ($this->userSchemaReady) return;
        try {
            $db = config::getConnexion();
            $cols = $db->query("SHOW COLUMNS FROM participation")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_utilisateur', $cols, true)) {
                $db->exec("ALTER TABLE participation ADD COLUMN id_utilisateur INT UNSIGNED DEFAULT NULL AFTER id_event");
            }
        } catch (Exception $e) {
            error_log('Erreur ensureUserSchema participation: ' . $e->getMessage());
        }
        $this->userSchemaReady = true;
    }

    public function listParticipations() {
        $this->ensureUserSchema();
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
        $this->ensureUserSchema();
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
        $this->ensureUserSchema();
        $sql = "DELETE FROM participation WHERE id_participation = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    public function addParticipation(Participation $participation, int $userId = 0) {
        $this->ensureUserSchema();
        $sql = "INSERT INTO participation (id_event, id_utilisateur, nom_complet, email, telephone, centre_interet, statut) 
                VALUES (:id_event, :id_utilisateur, :nom_complet, :email, :telephone, :centre_interet, 'en_attente')";
        $db = config::getConnexion();
        try {
            $userId = $userId > 0 ? $userId : gl_current_user_id();
            $query = $db->prepare($sql);
            return $query->execute([
                'id_event'       => $participation->getIdEvent(),
                'id_utilisateur' => $userId > 0 ? $userId : null,
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
        $this->ensureUserSchema();
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
        $this->ensureUserSchema();
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

    public function listParticipationsByUser(int $userId): array {
        $this->ensureUserSchema();
        if ($userId <= 0) return [];
        $sql = "SELECT p.*, e.titre as evenement_titre, e.date, e.heure, e.type
                FROM participation p
                JOIN evenement e ON p.id_event = e.id_event
                WHERE p.id_utilisateur = :user_id
                ORDER BY e.date DESC, e.heure DESC";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
