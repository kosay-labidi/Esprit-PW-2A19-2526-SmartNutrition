<?php
// CORRECTION : casse du require_once corrigee (Sportsommeil avec S minuscule apres sport)
// Sur Linux/XAMPP, le nom de fichier est case-sensitive
require_once(__DIR__ . '/../../../controller/Sportsommeil.controller.php');

header('Content-Type: application/json; charset=utf-8');

$id_demande = trim($_GET['id_demande'] ?? '');

if ($id_demande === '' || !ctype_digit($id_demande) || (int)$id_demande <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'id_demande invalide.']);
    exit;
}

$ctrl = new SportSommeilController();

// getPlanningCompletByDemande() -> INNER JOIN planning + demandeplanning + LEFT JOIN sportsommeil
$lignes = $ctrl->getPlanningCompletByDemande((int) $id_demande);

echo json_encode([
    'success'    => true,
    'id_demande' => (int) $id_demande,
    'planning'   => $lignes,
    'total'      => count($lignes),
]);
?>
