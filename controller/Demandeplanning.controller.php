<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Demandeplanning.php';

class DemandeplanningController {

    private static ?bool $_hasStatut = null;
    private function hasStatutColumn(): bool {
        if (self::$_hasStatut !== null) return self::$_hasStatut;
        try {
            $db = config::getConnexion();
            $r  = $db->query("SHOW COLUMNS FROM demandeplanning LIKE 'statut'");
            self::$_hasStatut = ($r->rowCount() > 0);
        } catch (PDOException $e) {
            self::$_hasStatut = false;
        }
        return self::$_hasStatut;
    }

    public function addDemande(Demandeplanning $demande): int {
        try {
            $db = config::getConnexion();
            if ($this->hasStatutColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO demandeplanning
                        (id_utilisateur, calories, budget, type_budget, duree, type_duree, statut, date_demande)
                    VALUES
                        (:id_utilisateur, :calories, :budget, :type_budget, :duree, :type_duree, 'en_attente', NOW())
                ");
            } else {
                $stmt = $db->prepare("
                    INSERT INTO demandeplanning
                        (id_utilisateur, calories, budget, type_budget, duree, type_duree, date_demande)
                    VALUES
                        (:id_utilisateur, :calories, :budget, :type_budget, :duree, :type_duree, NOW())
                ");
            }
            $stmt->execute([
                ':id_utilisateur' => $demande->getIdUtilisateur(),
                ':calories'       => $demande->getCalories(),
                ':budget'         => $demande->getBudget(),
                ':type_budget'    => $demande->getTypeBudget(),
                ':duree'          => $demande->getDuree(),
                ':type_duree'     => $demande->getTypeDuree(),
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur insertion demande : " . $e->getMessage());
        }
    }

    // ── UPDATE STATUT ─────────────────────────────────────────────────────
    public function updateStatut(int $id, string $statut): bool {
        if (!in_array($statut, ['en_attente','approuve','rejete'], true)) return false;
        if (!$this->hasStatutColumn()) return false;
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("UPDATE demandeplanning SET statut=:statut WHERE id=:id");
            return $stmt->execute([':statut' => $statut, ':id' => $id]);
        } catch (PDOException $e) { return false; }
    }

    // ── LIST ALL ──────────────────────────────────────────────────────────
    public function listAllDemandes(): array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->query("SELECT * FROM demandeplanning ORDER BY date_demande DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function listAllDemandesAvecStats(): array {
        try {
            $db  = config::getConnexion();
            $sel = $this->hasStatutColumn() ? 'd.statut' : "'en_attente' AS statut";
            $stmt = $db->query("
                SELECT
                    d.id,
                    d.id_utilisateur,
                    d.calories,
                    d.budget,
                    d.type_budget,
                    d.duree,
                    d.type_duree,
                    d.date_demande,
                    {$sel},
                    COUNT(p.id)               AS nb_lignes_planning,
                    MAX(ss.activite_sportive) AS activite_sport
                FROM demandeplanning d
                LEFT JOIN planning     p  ON p.id_demande  = d.id
                LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
                GROUP BY d.id
                ORDER BY d.date_demande DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function afficherPlanningByDemande(int $idDemande): array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                SELECT
                    p.id AS planning_id, p.date, p.type_activite, p.description,
                    d.id AS demande_id, d.calories AS objectif_calories,
                    d.budget AS objectif_budget, d.type_budget, d.duree, d.type_duree, d.id_utilisateur
                FROM planning p
                INNER JOIN demandeplanning d ON d.id = p.id_demande
                WHERE p.id_demande = :id
                ORDER BY p.date ASC, FIELD(p.type_activite,'repas','sport','sommeil')
            ");
            $stmt->execute([':id' => $idDemande]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function getDemandeById(int $id): ?array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("SELECT * FROM demandeplanning WHERE id=:id");
            $stmt->execute([':id' => $id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r && !isset($r['statut'])) $r['statut'] = 'en_attente';
            return $r ?: null;
        } catch (PDOException $e) { return null; }
    }

    public function updateDemande(Demandeplanning $demande, int $id): bool {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                UPDATE demandeplanning SET
                    id_utilisateur=:id_utilisateur, calories=:calories,
                    budget=:budget, type_budget=:type_budget, duree=:duree, type_duree=:type_duree
                WHERE id=:id
            ");
            return $stmt->execute([
                ':id'             => $id,
                ':id_utilisateur' => $demande->getIdUtilisateur(),
                ':calories'       => $demande->getCalories(),
                ':budget'         => $demande->getBudget(),
                ':type_budget'    => $demande->getTypeBudget(),
                ':duree'          => $demande->getDuree(),
                ':type_duree'     => $demande->getTypeDuree(),
            ]);
        } catch (PDOException $e) { return false; }
    }

    public function deleteDemande(int $id): bool {
        try {
            $db = config::getConnexion();
            $db->beginTransaction();
            $db->prepare("DELETE FROM planning     WHERE id_demande=:id")->execute([':id'=>$id]);
            $db->prepare("DELETE FROM sportsommeil WHERE id_demande=:id")->execute([':id'=>$id]);
            $db->prepare("DELETE FROM demandeplanning WHERE id=:id")->execute([':id'=>$id]);
            $db->commit();
            return true;
        } catch (PDOException $e) { $db->rollBack(); return false; }
    }
    // ── EXPORT CSV ────────────────────────────────────────────────────────
    public function exportCSV(?string $statut = null): void {
        try {
            $db  = config::getConnexion();
            $sel = $this->hasStatutColumn() ? 'd.statut' : "'en_attente' AS statut";

            $where = '';
            $params = [];
            if ($statut && in_array($statut, ['en_attente','approuve','rejete'], true)) {
                $where = 'WHERE d.statut = :statut';
                $params[':statut'] = $statut;
            }

            $stmt = $db->prepare("
                SELECT
                    d.id,
                    d.id_utilisateur,
                    d.calories,
                    d.budget,
                    d.type_budget,
                    d.duree,
                    d.type_duree,
                    d.date_demande,
                    {$sel},
                    COUNT(p.id)               AS nb_lignes_planning,
                    MAX(ss.activite_sportive) AS activite_sport
                FROM demandeplanning d
                LEFT JOIN planning     p  ON p.id_demande  = d.id
                LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
                {$where}
                GROUP BY d.id
                ORDER BY d.date_demande DESC
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $filename = 'planning_demandes_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour compatibilité Excel
            fwrite($out, "\xEF\xBB\xBF");
            // En-têtes
            fputcsv($out, ['ID','Utilisateur','Calories (kcal)','Budget','Type budget','Durée','Type durée','Statut','Nb lignes planning','Activité sport','Date demande'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['id_utilisateur'],
                    $r['calories'],
                    number_format((float)$r['budget'], 2, '.', ''),
                    $r['type_budget'],
                    $r['duree'],
                    $r['type_duree'],
                    $r['statut'] ?? 'en_attente',
                    $r['nb_lignes_planning'] ?? 0,
                    $r['activite_sport'] ?? '',
                    $r['date_demande'] ?? '',
                ], ';');
            }
            fclose($out);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur export CSV : ' . $e->getMessage();
        }
    }

    public function listDemandesByUser(int $userId): array {
        try {
            $db  = config::getConnexion();
            $sel = $this->hasStatutColumn() ? 'd.statut,' : "'en_attente' AS statut,";
            $stmt = $db->prepare("
                SELECT
                    d.id,
                    d.id_utilisateur,
                    d.calories,
                    d.budget,
                    d.type_budget,
                    d.duree,
                    d.type_duree,
                    d.date_demande,
                    {$sel}
                    CASE WHEN COUNT(p.id)>0 THEN 1 ELSE 0 END AS _has_planning,
                    COUNT(p.id) AS nb_lignes
                FROM demandeplanning d
                LEFT JOIN planning p ON p.id_demande=d.id
                WHERE d.id_utilisateur=:userId
                GROUP BY d.id
                ORDER BY d.date_demande DESC
            ");
            $stmt->execute([':userId' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
}
?>
