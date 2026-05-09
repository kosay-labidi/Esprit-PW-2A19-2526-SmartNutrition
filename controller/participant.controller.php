<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Participant.php');

class ParticipantController {
    private bool $paymentSchemaReady = false;

    private function ensurePaymentSchema(): void {
        if ($this->paymentSchemaReady) return;
        try {
            Config::getConnexion()->exec("
                CREATE TABLE IF NOT EXISTS paiement_defi (
                    id INT NOT NULL AUTO_INCREMENT,
                    id_challenge INT NOT NULL,
                    id_participant INT DEFAULT NULL,
                    nom VARCHAR(150) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    montant DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    statut ENUM('en_attente','paye','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
                    methode VARCHAR(50) NOT NULL DEFAULT 'simulation',
                    reference_transaction VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_paiement_challenge (id_challenge),
                    INDEX idx_paiement_participant (id_participant),
                    INDEX idx_paiement_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log('Erreur ensurePaymentSchema participant: ' . $e->getMessage());
        }
        $this->paymentSchemaReady = true;
    }

    // ═══════════════════════════════════════════════════════════
    // CRUD DE BASE (préservés identiques)
    // ═══════════════════════════════════════════════════════════

    public function addParticipant(Participant $participant) {
        $sql = "INSERT INTO participant (id_challenge, nom, email, objectif, motivation, action, engagement, notifications, points, days_active, smart_score)
                VALUES (:id_challenge, :nom, :email, :objectif, :motivation, :action, :engagement, :notifications, :points, :days_active, :smart_score)";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_challenge' => (int)$participant->getIdChallenge(),
                'nom'          => Config::sanitizeInput($participant->getNom()),
                'email'        => Config::sanitizeInput($participant->getEmail()),
                'objectif'     => (int)$participant->getObjectif(),
                'motivation'   => Config::sanitizeInput($participant->getMotivation()),
                'action'       => Config::sanitizeInput($participant->getAction()),
                'engagement'   => (int)$participant->getEngagement(),
                'notifications'=> (int)$participant->getNotifications(),
                'points'       => (int)($participant->getPoints() ?? 0),
                'days_active'  => (int)($participant->getDaysActive() ?? 1),
                'smart_score'  => (float)($participant->getSmartScore() ?? 0)
            ]);
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erreur addParticipant: ' . $e->getMessage());
            return false;
        }
    }

    public function listParticipants($id_challenge = null) {
        $this->ensurePaymentSchema();
        $sql = "SELECT p.*,
                    c.titre        AS challenge_titre,
                    c.streak_icon  AS challenge_icon,
                    c.valeur_cible AS challenge_target,
                    c.prix         AS challenge_prix,
                    c.est_payant   AS challenge_est_payant,
                    pd.statut      AS paiement_statut,
                    pd.methode     AS paiement_methode,
                    pd.montant     AS paiement_montant,
                    pd.reference_transaction AS paiement_reference
                FROM participant p
                LEFT JOIN challenge c ON c.id = p.id_challenge
                LEFT JOIN paiement_defi pd ON pd.id_participant = p.id";

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
            error_log('Erreur listParticipants: ' . $e->getMessage());
            return [];
        }
    }

    public function listParticipantsByChallenge($id_challenge) {
        $sql = "SELECT * FROM participant WHERE id_challenge = :id_challenge";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id_challenge' => (int)$id_challenge]);
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur listParticipantsByChallenge: ' . $e->getMessage());
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
            error_log('Erreur deleteParticipant: ' . $e->getMessage());
            return false;
        }
    }

    public function showParticipant($id) {
        $sql = "SELECT p.*, c.titre AS challenge_titre, c.streak_icon AS challenge_icon, c.valeur_cible AS challenge_target
                FROM participant p
                LEFT JOIN challenge c ON c.id = p.id_challenge
                WHERE p.id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return $query->fetch();
        } catch (Exception $e) {
            error_log('Erreur showParticipant: ' . $e->getMessage());
            return null;
        }
    }

    public function updateParticipant(Participant $participant, $id) {
        $sql = "UPDATE participant SET
                    id_challenge  = :id_challenge,
                    nom           = :nom,
                    email         = :email,
                    objectif      = :objectif,
                    motivation    = :motivation,
                    action        = :action,
                    engagement    = :engagement,
                    notifications = :notifications,
                    points        = :points,
                    days_active   = :days_active,
                    smart_score   = :smart_score
                WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id_challenge' => $participant->getIdChallenge(),
                'nom'          => $participant->getNom(),
                'email'        => $participant->getEmail(),
                'objectif'     => $participant->getObjectif(),
                'motivation'   => $participant->getMotivation(),
                'action'       => $participant->getAction(),
                'engagement'   => $participant->getEngagement(),
                'notifications'=> $participant->getNotifications(),
                'points'       => $participant->getPoints(),
                'days_active'  => $participant->getDaysActive(),
                'smart_score'  => $participant->getSmartScore(),
                'id'           => (int)$id
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateParticipant: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule le score intelligent pour tous les participants et met à jour le classement.
     * Algorithme Smart Ranking : (Points * 0.4) + (Engagement * 0.4) + (Régularité * 0.2)
     */
    public function updateSmartRankings() {
        $db = Config::getConnexion();
        try {
            $participants = $db->query("SELECT id, points, engagement, days_active FROM participant")->fetchAll();
            
            $sql = "UPDATE participant SET smart_score = :score WHERE id = :id";
            $stmt = $db->prepare($sql);
            
            foreach ($participants as $p) {
                // Algorithme Smart Ranking
                $score = ($p['points'] * 0.4) + ($p['engagement'] * 0.4) + ($p['days_active'] * 0.2);
                
                $stmt->execute([
                    'score' => $score,
                    'id'    => $p['id']
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateSmartRankings: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE — STATISTIQUES PARTICIPANTS
    // ═══════════════════════════════════════════════════════════

    public function getStatistiquesParticipants(): array {
        $db = Config::getConnexion();
        try {
            $row = $db->query("
                SELECT
                    COUNT(*)                              AS total,
                    SUM(engagement = 1)                  AS engages,
                    SUM(notifications = 1)               AS notifs_actives,
                    COALESCE(AVG(objectif),    0)         AS avg_objectif,
                    COALESCE(AVG(engagement),  0)         AS avg_engagement,
                    COALESCE(AVG(points),      0)         AS avg_points,
                    COALESCE(MAX(points),      0)         AS max_points
                FROM participant
            ")->fetch();

            // Répartition par niveau de Smart Score
            $scoreDistribution = $db->query("
                SELECT 
                    CASE 
                        WHEN smart_score >= 80 THEN 'Expert'
                        WHEN smart_score >= 50 THEN 'Intermédiaire'
                        ELSE 'Débutant'
                    END as level,
                    COUNT(*) as count
                FROM participant
                GROUP BY level
            ")->fetchAll();
            $row['score_distribution'] = $scoreDistribution;

            // Top défis avec le plus de participants
            $topDefis = $db->query("
                SELECT c.titre, c.streak_icon, COUNT(p.id) AS nb
                FROM participant p
                JOIN challenge c ON c.id = p.id_challenge
                GROUP BY c.id, c.titre, c.streak_icon
                ORDER BY nb DESC
                LIMIT 5
            ")->fetchAll();
            $row['top_defis'] = $topDefis;

            return $row;
        } catch (Exception $e) {
            error_log('Erreur getStatistiquesParticipants: ' . $e->getMessage());
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE — RECHERCHE + FILTRE + PAGINATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Liste des participants avec recherche, filtre et pagination.
     *
     * @param string $search      Recherche dans nom ou email
     * @param int    $id_challenge Filtrer par défi (0 = tous)
     * @param string $engagement  '' | '1' | '0'
     * @param int    $page        Numéro de page (1-indexed)
     * @param int    $limit       Participants par page
     * @return array ['data' => [], 'total' => int, 'page' => int, 'totalPages' => int]
     */
    public function getRanking($limit = 10) {
        $sql = "SELECT nom, points, engagement, days_active, smart_score 
                FROM participant 
                ORDER BY smart_score DESC, points DESC 
                LIMIT :limit";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $query->execute();
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur getRanking: ' . $e->getMessage());
            return [];
        }
    }

    public function listParticipantsFiltres(
        string $search       = '',
        int    $id_challenge = 0,
        string $engagement   = '',
        int    $page         = 1,
        int    $limit        = 8
    ): array {
        $db     = Config::getConnexion();
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(p.nom LIKE :search OR p.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($id_challenge > 0) {
            $where[] = "p.id_challenge = :id_challenge";
            $params['id_challenge'] = $id_challenge;
        }
        if ($engagement !== '') {
            $where[] = "p.engagement = :engagement";
            $params['engagement'] = (int)$engagement;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            // Compter le total
            $countQuery = $db->prepare("SELECT COUNT(*) FROM participant p $whereClause");
            $countQuery->execute($params);
            $total = (int)$countQuery->fetchColumn();

            // Pagination
            $page  = max(1, $page);
            $limit = max(1, $limit);
            $offset = ($page - 1) * $limit;
            $totalPages = (int)ceil($total / $limit);

            // Requête principale
            $sql = "SELECT p.*, c.titre AS challenge_titre, c.streak_icon AS challenge_icon,
                           c.valeur_cible AS challenge_target,
                           c.prix         AS challenge_prix,
                           c.est_payant   AS challenge_est_payant,
                           pd.statut      AS paiement_statut,
                           pd.methode     AS paiement_methode,
                           pd.montant     AS paiement_montant
                    FROM participant p
                    LEFT JOIN challenge c ON c.id = p.id_challenge
                    LEFT JOIN paiement_defi pd ON pd.id_participant = p.id
                    $whereClause
                    ORDER BY p.date_inscription DESC, p.id DESC
                    LIMIT :limit OFFSET :offset";

            $query = $db->prepare($sql);
            foreach ($params as $k => $v) $query->bindValue($k, $v);
            $query->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $query->bindValue(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            return [
                'data'       => $query->fetchAll(),
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'totalPages' => $totalPages
            ];
        } catch (Exception $e) {
            error_log('Erreur listParticipantsFiltres: ' . $e->getMessage());
            return ['data' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'totalPages' => 0];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE — EXPORT CSV PARTICIPANTS
    // ═══════════════════════════════════════════════════════════

    public function exportCSV(): void {
        $data = $this->listParticipants();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="participants_gaialumen_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

        fputcsv($output, [
            'ID', 'Défi', 'Nom', 'Email', 'Objectif (%)',
            'Motivation', 'Plan d\'action', 'Engagé', 'Notifications', 'Date Inscription'
        ], ';');

        foreach ($data as $p) {
            fputcsv($output, [
                $p['id']               ?? '',
                $p['challenge_titre']  ?? '',
                $p['nom']              ?? '',
                $p['email']            ?? '',
                $p['objectif']         ?? 0,
                $p['motivation']       ?? '',
                $p['action']           ?? '',
                ((int)($p['engagement']    ?? 0) === 1) ? 'Oui' : 'Non',
                ((int)($p['notifications'] ?? 0) === 1) ? 'Oui' : 'Non',
                $p['date_inscription'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE — VÉRIFIER DOUBLON EMAIL
    // ═══════════════════════════════════════════════════════════

    public function emailDejaInscrit(string $email, int $id_challenge): bool {
        $db = Config::getConnexion();
        try {
            $q = $db->prepare(
                "SELECT COUNT(*) FROM participant WHERE email = :email AND id_challenge = :id_challenge"
            );
            $q->execute(['email' => $email, 'id_challenge' => $id_challenge]);
            return (int)$q->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log('Erreur emailDejaInscrit: ' . $e->getMessage());
            return false;
        }
    }
}
?>
