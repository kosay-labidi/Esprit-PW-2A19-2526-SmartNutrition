<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../config.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Methode non autorisee']);
        exit;
    }

    $id_utilisateur = 0;
    if (!empty($_SESSION['user']['id_utilisateur'])) {
        $id_utilisateur = (int) $_SESSION['user']['id_utilisateur'];
    } elseif (!empty($_SESSION['user_id'])) {
        $id_utilisateur = (int) $_SESSION['user_id'];
    } elseif (isset($_POST['id_utilisateur'])) {
        $id_utilisateur = (int) $_POST['id_utilisateur'];
    }

    $calories       = isset($_POST['calories']) ? (int)$_POST['calories'] : 0;
    $budget         = isset($_POST['budget']) ? (float)str_replace(',', '.', $_POST['budget']) : 0;
    $type_budget    = isset($_POST['type_budget']) ? trim($_POST['type_budget']) : 'quotidien';
    $duree          = isset($_POST['duree']) ? (int)$_POST['duree'] : 7;
    $type_duree     = isset($_POST['type_duree']) ? trim($_POST['type_duree']) : 'jours';

    $errors = [];
    if ($id_utilisateur <= 0) $errors['id_utilisateur'] = 'Utilisateur non connecté.';
    if ($calories <= 0)       $errors['calories']       = 'Calories doivent etre > 0.';
    if ($budget   <= 0)       $errors['budget']         = 'Budget doit etre > 0.';
    if (empty($type_budget))  $errors['type_budget']    = 'Type budget requis.';
    if ($duree    <= 0)       $errors['duree']          = 'Duree doit etre > 0.';
    if (empty($type_duree))   $errors['type_duree']     = 'Type duree requis.';

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Validation echouee.']);
        exit;
    }

    $db = Config::getConnexion();
    $sql = "INSERT INTO demandeplanning (id_utilisateur, calories, budget, type_budget, duree, type_duree, statut, date_demande) VALUES (?, ?, ?, ?, ?, ?, 'en_attente', ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_utilisateur, $calories, $budget, $type_budget, $duree, $type_duree, date('Y-m-d H:i:s')]);
    $idDemande = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success'    => true,
        'id_demande' => $idDemande,
        'statut'     => 'en_attente',
        'message'    => "Demande creee (#$idDemande). Passez a l'etape 2.",
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString()
    ]);
}
?>
