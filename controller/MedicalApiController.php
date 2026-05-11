<?php
// controller/MedicalApiController.php
// Role: Provide medical dossier data to the Python API composer.
// Access: Internal only (via API key).

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/dossierMedical.controller.php';

// Security: Simple API key check
$headers = getallheaders();
$key = $headers['X-API-Key'] ?? '';
$expectedKey = 'sunpicnic'; 

if ($key !== $expectedKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user_id from query string (temporary static fallback)
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

$dossierCtrl = new DossierMedicalController();
$dossier = $dossierCtrl->getByUserId($userId);

if (!$dossier) {
    http_response_code(404);
    echo json_encode(['error' => 'Dossier not found for user ' . $userId]);
    exit;
}

// Build the response - only fields needed for AI recommendations
$response = [
    'poids'            => $dossier['poids'],
    'taille'           => $dossier['taille'],
    'imc'              => $dossier['imc'],
    'allergies'        => $dossier['allergie'],
    'allergie_severity'=> $dossier['gravite_allergie'],
    'maladies'         => $dossier['maladies'],
    'traitements'      => $dossier['traitement'],
    'groupe_sanguin'   => $dossier['groupe_sanguin'],
    'notes_medecin'    => $dossier['notes_medecin']
];

header('Content-Type: application/json');
echo json_encode($response);