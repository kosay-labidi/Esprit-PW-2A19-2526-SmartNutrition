<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Participant.php');

class ParticipantController {

    // ═══════════════════════════════════════════════════════════
    // CRUD DE BASE (préservés identiques)
    // ═══════════════════════════════════════════════════════════

    public function addParticipant(Participant $participant) {
        $sql = "INSERT INTO participant (id_challenge, nom, email, objectif, motivation, action, engagement, notifications)
                VALUES (:id_challenge, :nom, :email, :objectif, :motivation, :action, :engagement, :notifications)";
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
                'notifications'=> $participant->getNotifications()
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur addParticipant: ' . $e->getMessage());
            return false;
        }
    }

    public function listParticipants($id_challenge = null) {
        $sql = "SELECT p.*,
                    c.titre        AS challenge_titre,
                    c.streak_icon  AS challenge_icon,
                    c.valeur_cible AS challenge_target
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
                    notifications = :notifications
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
                'id'           => (int)$id
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateParticipant: ' . $e->getMessage());
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
                    COALESCE(AVG(engagement),  0)         AS avg_engagement
                FROM participant
            ")->fetch();

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
                           c.valeur_cible AS challenge_target
                    FROM participant p
                    LEFT JOIN challenge c ON c.id = p.id_challenge
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
