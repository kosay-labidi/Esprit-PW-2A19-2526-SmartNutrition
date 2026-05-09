<?php
/**
 * Generate a GaiaLumen challenge draft from an admin prompt.
 * The API key stays server-side; prefer GROQ_API_KEY in the environment.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ai_challenge_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$apiKey = getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? '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');
if (!$apiKey) {
    ai_challenge_json(['ok' => false, 'error' => 'GROQ_API_KEY manquant côté serveur'], 500);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    ai_challenge_json(['ok' => false, 'error' => 'JSON invalide'], 400);
}

$prompt = trim((string)($payload['prompt'] ?? ''));
if (strlen($prompt) < 8) {
    ai_challenge_json(['ok' => false, 'error' => 'Prompt trop court'], 400);
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
    ai_challenge_json(['ok' => false, 'error' => 'Requête IA échouée', 'detail' => $err], 502);
}

$decoded = json_decode($respBody, true);
if ($respCode < 200 || $respCode >= 300 || !is_array($decoded)) {
    ai_challenge_json([
        'ok' => false,
        'error' => 'Erreur IA',
        'detail' => ai_challenge_error_detail($decoded, $respBody),
        'status' => $respCode,
    ], $respCode ?: 502);
}

$content = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
$content = preg_replace('/^```(?:json)?\s*/i', '', $content);
$content = preg_replace('/\s*```$/', '', $content);
$challenge = json_decode($content, true);
if (!is_array($challenge)) {
    ai_challenge_json([
        'ok' => false,
        'error' => 'Réponse IA non exploitable',
        'detail' => substr($content, 0, 500),
    ], 502);
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
