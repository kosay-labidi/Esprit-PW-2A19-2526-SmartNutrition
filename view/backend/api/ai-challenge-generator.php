<?php
/**
 * Generate a GaiaLumen challenge draft from an admin prompt.
 * The API key stays server-side; prefer GROQ_API_KEY in the environment.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function ai_challenge_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ai_challenge_limit(string $value, int $limit): string {
    $value = trim($value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function ai_challenge_lower(string $value): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function ai_challenge_contains(string $haystack, string $needle): bool {
    return $needle !== '' && strpos($haystack, $needle) !== false;
}

function ai_challenge_error_detail($decoded, string $raw = ''): string {
    if (is_array($decoded)) {
        if (isset($decoded['error']['message'])) return (string)$decoded['error']['message'];
        if (isset($decoded['error'])) {
            return is_string($decoded['error'])
                ? $decoded['error']
                : json_encode($decoded['error'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($decoded['message'])) return (string)$decoded['message'];
    }
    return $raw !== '' ? substr($raw, 0, 500) : 'Erreur inconnue';
}

function ai_challenge_local_draft(string $prompt, string $today): array {
    $lower = ai_challenge_lower($prompt);
    $objective = 'dechets';
    if (ai_challenge_contains($lower, 'sport') || ai_challenge_contains($lower, 'vélo') || ai_challenge_contains($lower, 'velo') || ai_challenge_contains($lower, 'marche') || ai_challenge_contains($lower, 'transport')) {
        $objective = 'transport';
    } elseif (ai_challenge_contains($lower, 'eau')) {
        $objective = 'eau';
    } elseif (ai_challenge_contains($lower, 'repas') || ai_challenge_contains($lower, 'nutrition') || ai_challenge_contains($lower, 'aliment')) {
        $objective = 'repas';
    } elseif (ai_challenge_contains($lower, 'energie') || ai_challenge_contains($lower, 'énergie')) {
        $objective = 'energie';
    } elseif (ai_challenge_contains($lower, 'co2') || ai_challenge_contains($lower, 'carbone')) {
        $objective = 'co2';
    }

    $type = (ai_challenge_contains($lower, 'individuel') || ai_challenge_contains($lower, 'solo')) ? 'individuel' : 'collectif';
    preg_match('/(\d{1,3})\s*%?/', $prompt, $targetMatch);
    $target = isset($targetMatch[1]) ? max(1, min(100, (int)$targetMatch[1])) : 30;
    $title = 'Défi GaiaLumen';
    if ($objective === 'transport') $title = 'Défi Sport & Mobilité Verte';
    if ($objective === 'dechets') $title = 'Défi Zéro Déchet';
    if ($objective === 'repas') $title = 'Défi Nutrition Durable';
    if ($objective === 'eau') $title = 'Défi Économie d’Eau';
    if ($objective === 'energie') $title = 'Défi Énergie Responsable';
    if ($objective === 'co2') $title = 'Défi Bas Carbone';

    return [
        'titre' => ai_challenge_limit($title, 80),
        'description' => ai_challenge_limit($prompt !== '' ? $prompt : 'Un défi concret pour progresser ensemble sur GaiaLumen.', 500),
        'type' => $type,
        'objectif' => $objective,
        'valeur_cible' => $target,
        'date_debut' => $today,
        'date_fin' => date('Y-m-d', strtotime($today . ' +14 days')),
        'statut' => 'actif',
        'streak_icon' => $objective === 'transport' ? '🚴' : ($objective === 'repas' ? '🥗' : '🌿'),
        'image' => '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ai_challenge_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    ai_challenge_json(['ok' => false, 'error' => 'JSON invalide'], 400);
}

$prompt = trim((string)($payload['prompt'] ?? ''));
if (function_exists('mb_strlen') ? mb_strlen($prompt, 'UTF-8') < 4 : strlen($prompt) < 4) {
    ai_challenge_json(['ok' => false, 'error' => 'Prompt trop court'], 400);
}

$apiKey = defined('GROQ_API_KEY') ? trim((string)GROQ_API_KEY) : '';
if (!$apiKey) {
    ai_challenge_json([
        'ok' => true,
        'challenge' => ai_challenge_local_draft($prompt, date('Y-m-d')),
        'provider' => 'local',
        'model' => 'rules',
        'warning' => 'GROQ_API_KEY manquant côté serveur: brouillon local utilisé.',
    ]);
}

$today = date('Y-m-d');
$system = <<<SYS
Tu es un assistant admin pour GaiaLumen, une plateforme de défis écologiques, sportifs, nutrition et bien-être.
Tu dois transformer une idée en brouillon de défi prêt à remplir un formulaire.
Réponds uniquement avec un objet JSON valide, sans markdown, sans texte autour.

Champs obligatoires:
- titre: string, 3 à 80 caractères, en français.
- description: string, 30 à 500 caractères, motivante et concrète.
- type: "collectif" ou "individuel".
- objectif: une valeur exacte parmi ["dechets","eau","repas","transport","energie","co2"].
- valeur_cible: nombre entier entre 1 et 100.
- date_debut: date YYYY-MM-DD, aujourd'hui ou plus tard.
- date_fin: date YYYY-MM-DD, après date_debut.
- statut: "actif" ou "futur".
- streak_icon: un seul emoji pertinent.
- image: URL https publique pertinente, ou chaîne vide si aucune URL fiable.

Date du jour: {$today}
SYS;

$user = "Crée un défi GaiaLumen à partir de cette demande admin:\n" . $prompt;
$model = (string)(getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile'));

if (!function_exists('curl_init')) {
    ai_challenge_json([
        'ok' => true,
        'challenge' => ai_challenge_local_draft($prompt, $today),
        'provider' => 'local',
        'model' => 'rules',
        'warning' => 'Extension PHP cURL désactivée: brouillon local utilisé.',
    ]);
}

$body = [
    'model' => $model,
    'temperature' => 0.45,
    'max_tokens' => 900,
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user],
    ],
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 35,
]);

$respBody = curl_exec($ch);
$respCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($respBody === false) {
    ai_challenge_json([
        'ok' => true,
        'challenge' => ai_challenge_local_draft($prompt, $today),
        'provider' => 'local',
        'model' => 'rules',
        'warning' => 'Requête IA échouée: brouillon local utilisé.',
        'detail' => $err,
    ]);
}

$decoded = json_decode($respBody, true);
if ($respCode < 200 || $respCode >= 300 || !is_array($decoded)) {
    ai_challenge_json([
        'ok' => true,
        'challenge' => ai_challenge_local_draft($prompt, $today),
        'provider' => 'local',
        'model' => 'rules',
        'warning' => 'Erreur IA: brouillon local utilisé.',
        'detail' => ai_challenge_error_detail($decoded, $respBody),
        'status' => $respCode,
    ]);
}

$content = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
$content = preg_replace('/^```(?:json)?\s*/i', '', $content);
$content = preg_replace('/\s*```$/', '', $content);
$challenge = json_decode($content, true);
if (!is_array($challenge)) {
    ai_challenge_json([
        'ok' => true,
        'challenge' => ai_challenge_local_draft($prompt, $today),
        'provider' => 'local',
        'model' => 'rules',
        'warning' => 'Réponse IA non exploitable: brouillon local utilisé.',
        'detail' => substr($content, 0, 500),
    ]);
}

$allowedTypes = ['collectif', 'individuel'];
$allowedObjectives = ['dechets', 'eau', 'repas', 'transport', 'energie', 'co2'];
$allowedStatuses = ['actif', 'futur'];

$dateStart = (string)($challenge['date_debut'] ?? $today);
$dateEnd = (string)($challenge['date_fin'] ?? date('Y-m-d', strtotime($today . ' +14 days')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart) || strtotime($dateStart) < strtotime($today)) {
    $dateStart = $today;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd) || strtotime($dateEnd) <= strtotime($dateStart)) {
    $dateEnd = date('Y-m-d', strtotime($dateStart . ' +14 days'));
}

$out = [
    'titre' => ai_challenge_limit((string)($challenge['titre'] ?? 'Nouveau défi GaiaLumen'), 80),
    'description' => ai_challenge_limit((string)($challenge['description'] ?? 'Un défi concret pour progresser ensemble sur GaiaLumen.'), 500),
    'type' => in_array(($challenge['type'] ?? ''), $allowedTypes, true) ? $challenge['type'] : 'collectif',
    'objectif' => in_array(($challenge['objectif'] ?? ''), $allowedObjectives, true) ? $challenge['objectif'] : 'dechets',
    'valeur_cible' => max(1, min(100, (int)($challenge['valeur_cible'] ?? 50))),
    'date_debut' => $dateStart,
    'date_fin' => $dateEnd,
    'statut' => in_array(($challenge['statut'] ?? ''), $allowedStatuses, true) ? $challenge['statut'] : 'actif',
    'streak_icon' => ai_challenge_limit((string)($challenge['streak_icon'] ?? '🏆'), 8),
    'image' => filter_var(($challenge['image'] ?? ''), FILTER_VALIDATE_URL) ? (string)$challenge['image'] : '',
];

ai_challenge_json([
    'ok' => true,
    'challenge' => $out,
    'provider' => 'groq',
    'model' => $decoded['model'] ?? $model,
]);
