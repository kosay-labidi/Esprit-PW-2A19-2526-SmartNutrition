<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Evenement.php';
require_once __DIR__ . '/../helpers/auth_user.php';

class EvenementController {
    private bool $userSchemaReady = false;

    private function ensureUserSchema(): void {
        if ($this->userSchemaReady) return;
        try {
            $db = config::getConnexion();
            $cols = $db->query("SHOW COLUMNS FROM evenement")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_utilisateur', $cols, true)) {
                $db->exec("ALTER TABLE evenement ADD COLUMN id_utilisateur INT UNSIGNED DEFAULT NULL AFTER id_event");
            }
        } catch (Exception $e) {
            error_log('Erreur ensureUserSchema evenement: ' . $e->getMessage());
        }
        $this->userSchemaReady = true;
    }

    public function listEvenements($orderBy = 'date ASC') {
        $this->ensureUserSchema();
        $allowed = ['date ASC', 'date DESC', 'titre ASC', 'titre DESC', 'id_event ASC', 'id_event DESC'];
        if (!in_array($orderBy, $allowed)) $orderBy = 'date ASC';
        $sql = "SELECT * FROM evenement ORDER BY $orderBy";
        $db  = config::getConnexion();
        return $db->query($sql);
    }

    public function getStats() {
        $db    = config::getConnexion();
        $today = date('Y-m-d');
        $year  = (int) date('Y');

        $total = (int) $db->query("SELECT COUNT(*) FROM evenement")->fetchColumn();

        $stmtYear = $db->prepare("SELECT COUNT(*) FROM evenement WHERE YEAR(date) = :year");
        $stmtYear->execute([':year' => $year]);
        $thisYear = (int) $stmtYear->fetchColumn();

        $stmtUp = $db->prepare("SELECT COUNT(*) FROM evenement WHERE date >= :today");
        $stmtUp->execute([':today' => $today]);
        $upcoming = (int) $stmtUp->fetchColumn();

        $byType = [];
        $stmtType = $db->query("SELECT type, COUNT(*) AS cnt FROM evenement GROUP BY type");
        foreach ($stmtType->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byType[$row['type']] = (int) $row['cnt'];
        }

        return [
            'total'    => $total,
            'thisYear' => $thisYear,
            'upcoming' => $upcoming,
            'byType'   => $byType
        ];
    }

    public function deleteEvenement($id) {
        $this->ensureUserSchema();
        $sql = "DELETE FROM evenement WHERE id_event = :id";
        $db  = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $req->execute();
    }

    public function addEvenement(Evenement $evenement) {
        $this->ensureUserSchema();
        $sql   = "INSERT INTO evenement (id_utilisateur, titre, description, date, heure, type)
                  VALUES (:id_utilisateur, :titre, :description, :date, :heure, :type)";
        $db    = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            ':id_utilisateur' => gl_current_user_id() ?: null,
            ':titre'       => $evenement->getTitre(),
            ':description' => $evenement->getDescription(),
            ':date'        => $evenement->getDate()->format('Y-m-d'),
            ':heure'       => $evenement->getHeure(),
            ':type'        => $evenement->getType()
        ]);
    }

    public function updateEvenement(Evenement $evenement, $id) {
        $this->ensureUserSchema();
        $sql   = "UPDATE evenement SET titre=:titre, description=:description,
                  date=:date, heure=:heure, type=:type WHERE id_event=:id";
        $db    = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            ':id'          => (int) $id,
            ':titre'       => $evenement->getTitre(),
            ':description' => $evenement->getDescription(),
            ':date'        => $evenement->getDate()->format('Y-m-d'),
            ':heure'       => $evenement->getHeure(),
            ':type'        => $evenement->getType()
        ]);
    }

    public function showEvenement($id) {
        $this->ensureUserSchema();
        $sql = "SELECT * FROM evenement WHERE id_event = :id";
        $db  = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute([':id' => (int) $id]);
        return $req->fetch();
    }
}
?>
