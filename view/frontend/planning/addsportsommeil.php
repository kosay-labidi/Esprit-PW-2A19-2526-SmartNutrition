<?php
ob_start();
error_reporting(0);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../controller/Sportsommeil.controller.php';
    require_once __DIR__ . '/../../../Model/Sportsommeil.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'Methode non autorisee']);
        exit;
    }

    $id_demande        = trim($_POST['id_demande']        ?? '');
    $activite_sportive = trim($_POST['activite_sportive'] ?? '');
    $duree_sport_hebdo = trim($_POST['duree_sport_hebdo'] ?? '');
    $heure_coucher     = trim($_POST['heure_coucher']     ?? '');
    $heure_reveil      = trim($_POST['heure_reveil']      ?? '');
    $qualite_sommeil   = trim($_POST['qualite_sommeil']   ?? '');

    $ACTIVITES = ['cardio','musculation','yoga','natation','cyclisme','course','fitness','autre'];
    $QUALITES  = ['mauvaise','passable','bonne','excellente'];

    $errors = [];
    if (!ctype_digit($id_demande)||(int)$id_demande<=0)
        $errors['id_demande'] = 'ID demande invalide.';
    if (!in_array($activite_sportive,$ACTIVITES,true))
        $errors['activite_sportive'] = 'Activite sportive invalide.';
    if (!ctype_digit($duree_sport_hebdo)||(int)$duree_sport_hebdo<=0||(int)$duree_sport_hebdo>840)
        $errors['duree_sport_hebdo'] = 'Duree invalide (1-840 min).';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$heure_coucher))
        $errors['heure_coucher'] = 'Format HH:MM requis.';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$heure_reveil))
        $errors['heure_reveil'] = 'Format HH:MM requis.';
    if (empty($errors['heure_coucher'])&&empty($errors['heure_reveil'])) {
        [$hC,$mC]=array_map('intval',explode(':',$heure_coucher));
        [$hR,$mR]=array_map('intval',explode(':',$heure_reveil));
        $minC=$hC*60+$mC; $minR=$hR*60+$mR;
        if ($minR<=$minC) $minR+=1440;
        $dur=$minR-$minC;
        if ($dur<240) $errors['heure_reveil']='Minimum 4h de sommeil.';
        if ($dur>720) $errors['heure_reveil']='Maximum 12h de sommeil.';
    }
    if (!in_array($qualite_sommeil,$QUALITES,true))
        $errors['qualite_sommeil'] = 'Qualite de sommeil invalide.';

    if (!empty($errors)) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['success'=>false,'errors'=>$errors,'message'=>'Validation echouee.']);
        exit;
    }

    $ss = new SportSommeil(
        null,(int)$id_demande,
        $activite_sportive,(int)$duree_sport_hebdo,
        $heure_coucher,$heure_reveil,$qualite_sommeil
    );
    $ctrl = new SportSommeilController();
    $idSS = $ctrl->addSportSommeil($ss);

    ob_end_clean();
    http_response_code(201);
    echo json_encode([
        'success'    => true,
        'id_ss'      => $idSS,
        'id_demande' => (int)$id_demande,
        'statut'     => 'en_attente',
        'message'    => 'Profil sport et sommeil enregistre. En attente de validation admin.',
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
}
?>
