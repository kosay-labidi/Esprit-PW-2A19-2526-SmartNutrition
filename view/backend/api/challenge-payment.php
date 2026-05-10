<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once(__DIR__ . '/../../../controller/challenge.controller.php');
require_once(__DIR__ . '/../../../controller/paiementDefi.controller.php');

function payment_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    payment_json(['success' => false, 'message' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$idChallenge = (int)($payload['id_challenge'] ?? 0);
$nom = trim((string)($payload['nom'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$methode = trim((string)($payload['methode'] ?? 'card'));

if ($idChallenge <= 0 || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    payment_json(['success' => false, 'message' => 'Données paiement invalides.'], 400);
}

$challengeC = new ChallengeController();
$paiementC = new PaiementDefiController();

if (!$paiementC->methodeAutorisee($methode)) {
    payment_json(['success' => false, 'message' => 'Méthode de paiement non supportée.'], 400);
}

$challenge = $challengeC->showChallenge($idChallenge);
if (!$challenge) {
    payment_json(['success' => false, 'message' => 'Défi introuvable.'], 404);
}

$estPayant = (int)($challenge['est_payant'] ?? 0) === 1;
$prix = max(0, (float)($challenge['prix'] ?? 0));
if (!$estPayant || $prix <= 0) {
    payment_json(['success' => false, 'message' => 'Ce défi ne nécessite pas de paiement.'], 400);
}

$payment = $paiementC->enregistrerPaiementSimule($idChallenge, 0, $nom, $email, $prix, $methode);
if (!$payment['success']) {
    payment_json($payment, 500);
}

payment_json([
    'success' => true,
    'message' => 'Paiement validé.',
    'payment' => [
        'reference' => $payment['reference'],
        'statut' => $payment['statut'],
        'methode' => $payment['methode'],
        'montant' => $prix,
        'challenge_id' => $idChallenge,
    ],
]);
?>
