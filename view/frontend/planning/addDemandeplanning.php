<?php
ob_start();
error_reporting(0);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';
    require_once __DIR__ . '/../../../Model/Demandeplanning.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Methode non autorisee']);
        exit;
    }

    $id_utilisateur = (int)   ($_POST['id_utilisateur'] ?? 0);
    $calories       = (int)   ($_POST['calories']       ?? 0);
    $budget         = (float) ($_POST['budget']         ?? 0);
    $type_budget    = trim($_POST['type_budget']    ?? '');
    $duree          = (int)   ($_POST['duree']          ?? 0);
    $type_duree     = trim($_POST['type_duree']     ?? '');

    $errors = [];
    if ($id_utilisateur <= 0) $errors['id_utilisateur'] = 'ID Utilisateur invalide.';
    if ($calories <= 0)       $errors['calories']       = 'Calories doivent etre > 0.';
    if ($calories > 10000)    $errors['calories']       = 'Max 10 000 kcal.';
    if ($budget   <= 0)       $errors['budget']         = 'Budget doit etre > 0.';
    if (empty($type_budget))  $errors['type_budget']    = 'Type budget requis.';
    if ($duree    <= 0)       $errors['duree']          = 'Duree doit etre > 0.';
    if ($duree    > 365)      $errors['duree']          = 'Max 365 jours.';
    if (empty($type_duree))   $errors['type_duree']     = 'Type duree requis.';

    if (!empty($errors)) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['success'=>false,'errors'=>$errors,'message'=>'Validation echouee.']);
        exit;
    }

    $controller = new DemandeplanningController();
    $demande    = new Demandeplanning(null,$id_utilisateur,$calories,$budget,$type_budget,$duree,$type_duree,'en_attente');
    $idDemande  = $controller->addDemande($demande);

    ob_end_clean();
    http_response_code(201);
    echo json_encode([
        'success'    => true,
        'id_demande' => $idDemande,
        'statut'     => 'en_attente',
        'message'    => "Demande creee (#$idDemande). Passez a l'etape 2.",
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
}
?>
