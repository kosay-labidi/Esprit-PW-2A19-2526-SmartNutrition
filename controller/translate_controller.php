<?php
/* ============================================================
   controller/translate_controller.php
   Reçoit POST {texts:[], targetLang:string} depuis la View,
   délègue au Model, retourne JSON {translations:[]}.
   ============================================================ */

require_once __DIR__ . '/../model/translate_model.php';

/* Garde MVC : si inclus via require_once, ne pas exécuter */
if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) { return; }

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST requis']);
    exit;
}

/* Lire le body JSON */
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['texts']) || !isset($body['targetLang'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants : texts et targetLang']);
    exit;
}

$texts      = array_values(array_slice((array)$body['texts'], 0, 100));
$targetLang = preg_replace('/[^a-zA-Z\-]/', '', (string)$body['targetLang']);

if (empty($texts) || empty($targetLang)) {
    http_response_code(400);
    echo json_encode(['error' => 'texts vide ou targetLang invalide']);
    exit;
}

/* Appel Model */
$translations = translate_texts($texts, $targetLang);

echo json_encode(
    ['translations' => $translations],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
