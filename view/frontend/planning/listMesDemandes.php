<?php
ob_start();
error_reporting(0);
ini_set('display_errors','0');
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

    if (isset($_GET['action']) && $_GET['action']==='delete' && isset($_GET['id'])) {
        $ok = (new DemandeplanningController())->deleteDemande((int)$_GET['id']);
        ob_end_clean();
        echo json_encode(['success'=>$ok]);
        exit;
    }

    $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $demandes = (new DemandeplanningController())->listDemandesByUser($userId);
    if (!is_array($demandes)) $demandes = [];

    $out = array_map(function($d) {
        return [
            'id'            => (int)$d['id'],
            'id_utilisateur'=> (int)$d['id_utilisateur'],
            'calories'      => (int)$d['calories'],
            'budget'        => (float)$d['budget'],
            'type_budget'   => $d['type_budget'],
            'duree'         => (int)$d['duree'],
            'type_duree'    => $d['type_duree'],
            'statut'        => $d['statut'] ?? 'en_attente',
            'date_demande'  => $d['date_demande'] ?? null,
            '_has_planning' => (bool)($d['_has_planning'] ?? false),
            'nb_lignes'     => (int)($d['nb_lignes'] ?? 0),
        ];
    }, $demandes);

    ob_end_clean();
    echo json_encode($out);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>
