<?php
/**
 * repas.controller.php — CRUD Repas + liaison aliments
 * Intégré depuis GSRepasVF2 dans le projet Mainn
 * Utilise Config::getConnexion() de Mainn
 */

require_once __DIR__ . '/../config.php';

/* ============================================================
   FONCTIONS SQL — Repas
   ============================================================ */

function repas_create($pdo, array $data): int {
    $sql = "INSERT INTO repas (nom_repas, date_repas, id_utilisateur) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nom_repas'],
        $data['date_repas'],
        $data['id_utilisateur'] ?? 1
    ]);
    return (int) $pdo->lastInsertId();
}

function repas_attachAliments($pdo, int $idRepas, array $aliments, array $quantites): void {
    repas_detachAliments($pdo, $idRepas);
    $sql  = "INSERT INTO repas_aliments (id_repas, id_aliment, quantite) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    foreach ($aliments as $idAliment) {
        $qte = (float) ($quantites[$idAliment] ?? 100);
        if ($qte <= 0) $qte = 100;
        $stmt->execute([$idRepas, (int)$idAliment, $qte]);
    }
}

function repas_detachAliments($pdo, int $idRepas): void {
    $stmt = $pdo->prepare("DELETE FROM repas_aliments WHERE id_repas = ?");
    $stmt->execute([$idRepas]);
}

function repas_getAll($pdo): array {
    $sql = "SELECT r.*, COUNT(ra.id_aliment) AS nb_aliments
            FROM repas r
            LEFT JOIN repas_aliments ra ON r.id_repas = ra.id_repas
            GROUP BY r.id_repas
            ORDER BY r.date_repas DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function repas_getAllByUser($pdo, int $idUser): array {
    $sql = "SELECT r.*, COUNT(ra.id_aliment) AS nb_aliments
            FROM repas r
            LEFT JOIN repas_aliments ra ON r.id_repas = ra.id_repas
            WHERE r.id_utilisateur = ?
            GROUP BY r.id_repas
            ORDER BY r.date_repas DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUser]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function repas_getById($pdo, int $id): array|false {
    $stmt = $pdo->prepare("SELECT * FROM repas WHERE id_repas = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function repas_getAlimentsOfRepas($pdo, int $idRepas): array {
    $sql = "SELECT a.*, ra.quantite
            FROM repas_aliments ra
            INNER JOIN aliments a ON ra.id_aliment = a.id_aliment
            WHERE ra.id_repas = ?
            ORDER BY a.nom ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idRepas]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function repas_getTotauxNutritionnels($pdo, int $idRepas): array {
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
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idRepas]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function repas_update($pdo, int $id, array $data): void {
    $stmt = $pdo->prepare("UPDATE repas SET nom_repas=?, date_repas=? WHERE id_repas=?");
    $stmt->execute([$data['nom_repas'], $data['date_repas'], $id]);
}

function repas_delete($pdo, int $id): void {
    $stmt = $pdo->prepare("DELETE FROM repas WHERE id_repas = ?");
    $stmt->execute([$id]);
}

function repas_existsByNomDate($pdo, string $nom, string $date, int $userId, int $excludeId = 0): bool {
    $sql  = "SELECT COUNT(*) FROM repas
             WHERE LOWER(TRIM(nom_repas)) = LOWER(TRIM(?))
             AND DATE(date_repas) = DATE(?)
             AND id_utilisateur = ?
             AND id_repas != ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $date, $userId, $excludeId]);
    return (int) $stmt->fetchColumn() > 0;
}

/* ============================================================
   ROUTING — si appelé directement
   ============================================================ */
if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) { return; }

if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = Config::getConnexion();

// Récupérer l'utilisateur connecté
$idUser = (int) ($_SESSION['user_id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $errors = [];
        $nom  = trim($_POST['nom_repas'] ?? '');
        $date = trim($_POST['date_repas'] ?? '');
        $aliments = $_POST['aliments'] ?? [];

        if (strlen($nom) < 2) $errors[] = 'Le nom du repas est obligatoire (minimum 2 caractères).';
        if (empty($date))     $errors[] = 'La date du repas est obligatoire.';
        if (empty($aliments)) $errors[] = 'Veuillez sélectionner au moins un aliment.';

        if (empty($errors) && repas_existsByNomDate($pdo, $nom, $date, $idUser)) {
            $errors[] = 'Un repas avec ce nom existe déjà à cette date.';
        }

        if (!empty($errors)) {
            header("Location: ../view/frontend/meals/listRepas.php?error=" . urlencode(implode(' | ', $errors)));
            exit;
        }

        $idRepas = repas_create($pdo, ['nom_repas' => $nom, 'date_repas' => $date, 'id_utilisateur' => $idUser]);
        repas_attachAliments($pdo, $idRepas, $aliments, $_POST['quantites'] ?? []);
        header("Location: ../view/frontend/meals/listRepas.php?success=created");
        exit;
    }

    if ($action === 'update') {
        $id   = (int) ($_POST['id_repas'] ?? 0);
        $nom  = trim($_POST['nom_repas']  ?? '');
        $date = trim($_POST['date_repas'] ?? '');

        if ($id <= 0 || strlen($nom) < 2 || empty($date)) {
            header("Location: ../view/frontend/meals/updateRepas.php?id=$id&error=validation");
            exit;
        }

        repas_update($pdo, $id, ['nom_repas' => $nom, 'date_repas' => $date]);
        repas_attachAliments($pdo, $id, $_POST['aliments'] ?? [], $_POST['quantites'] ?? []);
        header("Location: ../view/frontend/meals/listRepas.php?success=updated");
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) repas_delete($pdo, $id);
    header("Location: ../view/frontend/meals/listRepas.php?success=deleted");
    exit;
}
