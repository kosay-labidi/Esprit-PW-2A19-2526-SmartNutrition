<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');
require_once(__DIR__ . '/../../../Model/Demandeplanning.php');
require_once(__DIR__ . '/../../../helpers/auth_user.php');

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => '', 'errors' => []];

try {
    // ── GET : Recuperer ────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id       = (int)$_GET['id'];
        $demandeC = new DemandeplanningController();
        $demande  = $demandeC->getDemandeById($id);

        if (!$demande) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Demande non trouvee.']);
            exit;
        }
        if (!gl_is_admin() && gl_current_user_id() > 0 && (int)$demande['id_utilisateur'] !== gl_current_user_id()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acces refuse.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id'             => (int)$demande['id'],
                'id_utilisateur' => (int)$demande['id_utilisateur'],
                'calories'       => (int)$demande['calories'],
                'budget'         => (float)$demande['budget'],
                'type_budget'    => $demande['type_budget'],
                'duree'          => (int)$demande['duree'],
                'type_duree'     => $demande['type_duree'],
                'statut'         => $demande['statut'] ?? 'en_attente',
                'date_demande'   => $demande['date_demande'] ?? null,
            ]
        ]);
        exit;
    }

    // ── POST : Update ──────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['error'] = 'Methode non autorisee.';
        echo json_encode($response);
        exit;
    }

    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
        http_response_code(400);
        $response['error'] = 'ID manquant ou invalide.';
        echo json_encode($response);
        exit;
    }

    $id          = (int)$_POST['id'];
    $calories    = trim($_POST['calories']    ?? '');
    $budget      = trim($_POST['budget']      ?? '');
    $type_budget = trim($_POST['type_budget'] ?? '');
    $duree       = trim($_POST['duree']       ?? '');
    $type_duree  = trim($_POST['type_duree']  ?? '');

    $demandeC = new DemandeplanningController();
    $existing = $demandeC->getDemandeById($id);

    if (!$existing) {
        http_response_code(404);
        $response['error'] = 'Demande non trouvee.';
        echo json_encode($response);
        exit;
    }
    if (!gl_is_admin() && gl_current_user_id() > 0 && (int)$existing['id_utilisateur'] !== gl_current_user_id()) {
        http_response_code(403);
        $response['error'] = 'Acces refuse.';
        echo json_encode($response);
        exit;
    }

    // ── Validation ────────────────────────────────────────────────────────

    // CORRECTION : coherent avec addDemandeplanning.php (calories > 0, pas de min 500)
    if ($calories === '') {
        $response['errors']['calories'] = "L'objectif calorique est requis.";
    } elseif (!ctype_digit($calories) || (int)$calories <= 0) {
        $response['errors']['calories'] = "Les calories doivent etre un entier positif.";
    } elseif ((int)$calories > 10000) {
        $response['errors']['calories'] = "Max 10 000 kcal.";
    }

    if ($budget === '') {
        $response['errors']['budget'] = "Le budget est requis.";
    } elseif (!is_numeric(str_replace(',', '.', $budget)) || (float)str_replace(',', '.', $budget) <= 0) {
        $response['errors']['budget'] = "Le budget doit etre un nombre positif.";
    } elseif ((float)str_replace(',', '.', $budget) > 100000) {
        $response['errors']['budget'] = "Le budget semble trop eleve (max 100 000).";
    }

    if (!in_array($type_budget, ['quotidien', 'hebdomadaire'], true)) {
        $response['errors']['type_budget'] = "Veuillez selectionner une periode valide.";
    }

    if ($duree === '') {
        $response['errors']['duree'] = "La duree est requise.";
    } elseif (!ctype_digit($duree) || (int)$duree <= 0) {
        $response['errors']['duree'] = "La duree doit etre un entier positif.";
    } elseif ((int)$duree > 365) {
        $response['errors']['duree'] = "La duree ne peut pas depasser 365 unites.";
    }

    if (!in_array($type_duree, ['jours', 'semaines'], true)) {
        $response['errors']['type_duree'] = "Veuillez selectionner une unite valide.";
    }

    if (!empty($response['errors'])) {
        http_response_code(422);
        $response['message'] = 'Validation echouee.';
        echo json_encode($response);
        exit;
    }

    // CORRECTION : on conserve le statut existant au lieu de passer null
    $updated = new Demandeplanning(
        $id,
        (int)$existing['id_utilisateur'],
        (int)$calories,
        (float)str_replace(',', '.', $budget),
        $type_budget,
        (int)$duree,
        $type_duree,
        $existing['statut'] ?? 'en_attente'
    );

    $demandeC->updateDemande($updated, $id);

    $response['success'] = true;
    $response['message'] = 'Demande modifiee avec succes !';
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = 'Erreur serveur : ' . $e->getMessage();
    echo json_encode($response);
}
?>
