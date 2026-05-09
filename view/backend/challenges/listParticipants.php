<?php
/**
 * Liste JSON des participants (défi optionnel) — utilisé par le module Défis + chat.
 * GET id_challenge (ou id) : filtre sur un défi ; absent : tous les participants.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../controller/participant.controller.php');

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || isset($_GET['ajax']);

if (!$isAjax) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Requête AJAX uniquement (?ajax=1 ou en-tête X-Requested-With).';
    exit;
}

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json; charset=utf-8');

$idChallenge = (int)($_GET['id_challenge'] ?? $_GET['id'] ?? 0);

$participantC = new ParticipantController();
$rows = $participantC->listParticipants($idChallenge > 0 ? $idChallenge : null);

$out = [];
foreach ($rows as $p) {
    if (!is_array($p)) {
        continue;
    }
    $out[] = [
        'id' => (int)($p['id'] ?? 0),
        'id_challenge' => (int)($p['id_challenge'] ?? 0),
        'nom' => isset($p['nom']) ? (string)$p['nom'] : 'Participant',
        'email' => isset($p['email']) ? (string)$p['email'] : '',
        'objectif' => (int)($p['objectif'] ?? 0),
        'motivation' => isset($p['motivation']) ? (string)$p['motivation'] : '',
        'action' => isset($p['action']) ? (string)$p['action'] : '',
        'challenge_titre' => $p['challenge_titre'] ?? null,
        'challenge_icon' => $p['challenge_icon'] ?? null,
        'challenge_target' => (int)($p['challenge_target'] ?? 100),
        'engagement' => (int)($p['engagement'] ?? 0),
        'notifications' => (int)($p['notifications'] ?? 0),
        'date_inscription' => $p['date_inscription'] ?? null,
        'paiement_statut' => $p['paiement_statut'] ?? null,
        'paiement_methode' => $p['paiement_methode'] ?? null,
        'paiement_montant' => isset($p['paiement_montant']) ? (float)$p['paiement_montant'] : null,
        'paiement_reference' => $p['paiement_reference'] ?? null,
    ];
}

echo json_encode($out);
