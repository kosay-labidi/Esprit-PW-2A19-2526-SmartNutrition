<?php

require_once __DIR__ . '/../config.php';
$pdo = Config::getConnexion();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function repas_currentUserId(): int {
    return (int) ($_SESSION['user']['id_utilisateur'] ?? $_SESSION['user_id'] ?? 1);
}

function repas_isAdmin(): bool {
    return (($_SESSION['user']['role'] ?? '') === 'admin');
}

function repas_targetUserId(array $data): int {
    if (repas_isAdmin() && !empty($data['id_utilisateur'])) {
        return max(1, (int) $data['id_utilisateur']);
    }
    return repas_currentUserId();
}

function repas_redirect(string $fallback, string $statusKey, string $statusValue): void {
    $target = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? $fallback;
    $separator = str_contains($target, '?') ? '&' : '?';
    header('Location: ' . $target . $separator . $statusKey . '=' . urlencode($statusValue));
    exit;
}

function repas_validatePayload(array $data, int $userId, int $excludeId = 0): array {
    $errors = [];

    $nom = trim($data['nom_repas'] ?? '');
    if (strlen($nom) < 2) {
        $errors[] = 'Le nom du repas est obligatoire (minimum 2 caractères).';
    }

    $date = trim($data['date_repas'] ?? '');
    if ($date === '' || strtotime($date) === false) {
        $errors[] = 'La date du repas est obligatoire.';
    }

    $aliments = array_values(array_filter((array) ($data['aliments'] ?? []), static fn($id) => (int) $id > 0));
    if (!$aliments) {
        $errors[] = 'Veuillez sélectionner au moins un aliment.';
    }

    foreach ($aliments as $idAliment) {
        $qte = (float) (($data['quantites'] ?? [])[$idAliment] ?? 100);
        if ($qte <= 0 || $qte > 2000) {
            $errors[] = 'Chaque quantité doit être comprise entre 1g et 2000g.';
            break;
        }
    }

    if (!$errors && repas_existsByNomDate(Config::getConnexion(), $nom, $date, $userId, $excludeId)) {
        $errors[] = 'Un repas avec ce nom existe déjà à cette date.';
    }

    return $errors;
}

/* ============================================================
   FONCTIONS SQL — Repas (requêtes dans le Controller)
   ============================================================ */

function repas_create($pdo, array $data): int {
    $sql = "INSERT INTO repas (nom_repas, date_repas, id_utilisateur)
            VALUES (?, ?, ?)";
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
    $sql = "SELECT r.*,
                   COUNT(ra.id_aliment) AS nb_aliments
            FROM repas r
            LEFT JOIN repas_aliments ra ON r.id_repas = ra.id_repas
            GROUP BY r.id_repas
            ORDER BY r.date_repas DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function repas_getAllByUser($pdo, int $idUser): array {
    $sql = "SELECT r.*,
                   COUNT(ra.id_aliment) AS nb_aliments
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
    $stmt = $pdo->prepare(
        "SELECT * FROM repas WHERE id_repas = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function repas_getByIdForUser($pdo, int $id, int $userId): array|false {
    $stmt = $pdo->prepare("SELECT * FROM repas WHERE id_repas = ? AND id_utilisateur = ?");
    $stmt->execute([$id, $userId]);
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
    $sql  = "UPDATE repas SET nom_repas=?, date_repas=?, id_utilisateur=? WHERE id_repas=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['nom_repas'], $data['date_repas'], $data['id_utilisateur'] ?? repas_currentUserId(), $id]);
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
   ROUTING — Traitement des requêtes POST/GET
   ============================================================ */

/* Garde : si inclus via require_once dans une vue, on s'arrete ici */
if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) { return; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    if ($action === 'create') {

        $idUser = repas_targetUserId($_POST);
        $nom = trim($_POST['nom_repas'] ?? '');
        $date = trim($_POST['date_repas'] ?? '');
        $aliments = $_POST['aliments'] ?? [];
        $errors = repas_validatePayload($_POST, $idUser);

        if (!empty($errors)) {
            $msg = implode(' | ', $errors);
            repas_redirect('../view/frontend/fo_repaslist.php', 'error', $msg);
        }

        $idRepas = repas_create($pdo, [
            'nom_repas'      => $nom,
            'date_repas'     => $date,
            'id_utilisateur' => $idUser,
        ]);

        $quantites = $_POST['quantites'] ?? [];
        repas_attachAliments($pdo, $idRepas, $aliments, $quantites);

        repas_redirect('../view/frontend/fo_repaslist.php', 'success', 'created');
    }



    if ($action === 'update') {

        $id   = (int) ($_POST['id_repas'] ?? 0);
        $nom  = trim($_POST['nom_repas']  ?? '');
        $date = trim($_POST['date_repas'] ?? '');

        $idUser = repas_targetUserId($_POST);
        if ($id <= 0 || (!repas_isAdmin() && !repas_getByIdForUser($pdo, $id, $idUser)) || (repas_isAdmin() && !repas_getById($pdo, $id))) {
            repas_redirect("../view/frontend/fo_updaterepas.php?id=$id", 'error', 'not_found');
        }

        $errors = repas_validatePayload($_POST, $idUser, $id);
        if ($errors) {
            repas_redirect("../view/frontend/fo_updaterepas.php?id=$id", 'error', implode(' | ', $errors));
        }

        repas_update($pdo, $id, ['nom_repas' => $nom, 'date_repas' => $date, 'id_utilisateur' => $idUser]);

        $aliments  = $_POST['aliments']  ?? [];
        $quantites = $_POST['quantites'] ?? [];
        repas_attachAliments($pdo, $id, $aliments, $quantites);

        repas_redirect('../view/frontend/fo_repaslist.php', 'success', 'updated');
    }
}




if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0 && (repas_isAdmin() ? repas_getById($pdo, $id) : repas_getByIdForUser($pdo, $id, repas_currentUserId()))) {
        repas_delete($pdo, $id);
    }
    repas_redirect('../view/frontend/fo_repaslist.php', 'success', 'deleted');
}
?>
