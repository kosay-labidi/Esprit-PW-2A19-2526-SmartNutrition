<?php
/* ============================================================
   model/repas.php — MODÈLE Repas (couche M du MVC)

   RÔLE : Toutes les interactions BDD concernant les repas
          ET la table de jointure repas_aliments.

   JOINTURE : Un repas est composé de plusieurs aliments.
   La relation N à N est gérée via la table repas_aliments :
       repas (1) ———< repas_aliments >——— (N) aliments

   CE FICHIER NE CONTIENT PAS DE HTML.
   ============================================================ */

require_once __DIR__ . '/../config.php';

class Repas {

    /* -- Connexion PDO ---------------------------------------- */
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /* ==========================================================
       CREATE REPAS — Créer un nouveau repas (entête)
       Retourne l'id du repas créé.
       ========================================================== */
    public function create(array $data): int {
        $sql = "INSERT INTO repas (nom_repas, date_repas, id_utilisateur)
                VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nom_repas'],
            $data['date_repas'],
            $data['id_utilisateur'] ?? 1
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /* ==========================================================
       JOINTURE — Associer des aliments à un repas
       Insère les lignes dans repas_aliments pour chaque aliment
       sélectionné avec sa quantité en grammes.
       ========================================================== */
    public function attachAliments(int $idRepas, array $aliments, array $quantites): void {
        /* Supprime les anciennes associations (utile pour update) */
        $this->detachAliments($idRepas);

        $sql  = "INSERT INTO repas_aliments (id_repas, id_aliment, quantite) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($aliments as $idAliment) {
            $qte = (float) ($quantites[$idAliment] ?? 100);
            if ($qte <= 0) $qte = 100;
            $stmt->execute([$idRepas, (int)$idAliment, $qte]);
        }
    }

    /* ==========================================================
       JOINTURE — Détacher tous les aliments d'un repas
       Utilisé avant un update pour repartir de zéro.
       ========================================================== */
    public function detachAliments(int $idRepas): void {
        $stmt = $this->pdo->prepare("DELETE FROM repas_aliments WHERE id_repas = ?");
        $stmt->execute([$idRepas]);
    }

    /* ==========================================================
       READ ALL — Lire tous les repas (avec nombre d'aliments)
       Utilise une jointure LEFT JOIN pour compter les aliments
       de chaque repas.
       ========================================================== */
    public function getAll(): array {
        $sql = "SELECT r.*,
                       COUNT(ra.id_aliment) AS nb_aliments
                FROM repas r
                LEFT JOIN repas_aliments ra ON r.id_repas = ra.id_repas
                GROUP BY r.id_repas
                ORDER BY r.date_repas DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================
       READ ALL par utilisateur
       Retourne les repas d'un utilisateur spécifique.
       ========================================================== */
    public function getAllByUser(int $idUser): array {
        $sql = "SELECT r.*,
                       COUNT(ra.id_aliment) AS nb_aliments
                FROM repas r
                LEFT JOIN repas_aliments ra ON r.id_repas = ra.id_repas
                WHERE r.id_utilisateur = ?
                GROUP BY r.id_repas
                ORDER BY r.date_repas DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================
       READ ONE — Lire un repas par son id
       ========================================================== */
    public function getById(int $id): array|false {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM repas WHERE id_repas = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ==========================================================
       JOINTURE — Lire les aliments d'un repas avec leurs détails
       Requête de jointure entre repas_aliments et aliments.
       Retourne tous les attributs de chaque aliment + quantité.
       ========================================================== */
    public function getAlimentsOfRepas(int $idRepas): array {
        $sql = "SELECT a.*, ra.quantite
                FROM repas_aliments ra
                INNER JOIN aliments a ON ra.id_aliment = a.id_aliment
                WHERE ra.id_repas = ?
                ORDER BY a.nom ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idRepas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================
       TOTAUX NUTRITIONNELS — Calculer les apports du repas
       Additionne les valeurs nutritionnelles de chaque aliment
       en tenant compte de la quantité (pour 100g → * qte/100).
       ========================================================== */
    public function getTotauxNutritionnels(int $idRepas): array {
        $sql = "SELECT
                    SUM(a.calories  * ra.quantite / 100) AS total_calories,
                    SUM(a.proteines * ra.quantite / 100) AS total_proteines,
                    SUM(a.glucides  * ra.quantite / 100) AS total_glucides,
                    SUM(a.lipides   * ra.quantite / 100) AS total_lipides,
                    SUM(a.fibres    * ra.quantite / 100) AS total_fibres,
                    SUM(a.sucre     * ra.quantite / 100) AS total_sucre,
                    SUM(a.sodium    * ra.quantite / 100) AS total_sodium,
                    SUM(a.co2       * ra.quantite / 100) AS total_co2,
                    SUM(ra.quantite)                     AS total_poids
                FROM repas_aliments ra
                INNER JOIN aliments a ON ra.id_aliment = a.id_aliment
                WHERE ra.id_repas = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idRepas]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /* ==========================================================
       UPDATE — Modifier le nom et la date d'un repas
       ========================================================== */
    public function update(int $id, array $data): void {
        $sql  = "UPDATE repas SET nom_repas=?, date_repas=? WHERE id_repas=?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$data['nom_repas'], $data['date_repas'], $id]);
    }

    /* ==========================================================
       DELETE — Supprimer un repas (cascade supprime repas_aliments)
       La contrainte FK ON DELETE CASCADE dans la BDD supprime
       automatiquement les lignes de repas_aliments associées.
       ========================================================== */
    public function delete(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM repas WHERE id_repas = ?");
        $stmt->execute([$id]);
    }

    /* ==========================================================
       VÉRIFICATION DOUBLON — Repas même nom + même date + même user
       ========================================================== */
    public function existsByNomDate(string $nom, string $date, int $userId, int $excludeId = 0): bool {
        $sql  = "SELECT COUNT(*) FROM repas
                 WHERE LOWER(TRIM(nom_repas)) = LOWER(TRIM(?))
                 AND DATE(date_repas) = DATE(?)
                 AND id_utilisateur = ?
                 AND id_repas != ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$nom, $date, $userId, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
?>
