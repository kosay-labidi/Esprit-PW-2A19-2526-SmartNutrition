<?php
/**
 * GaiaLumen AI Hub
 * Centralise les conversations de l'assistant et route vers le meilleur moteur disponible.
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

function hub_json(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function hub_env(string $key, string $fallback = ''): string
{
    return trim((string)(getenv($key) ?: ($_SERVER[$key] ?? $_ENV[$key] ?? $fallback)));
}

function hub_detect_route(string $message): string
{
    $text = mb_strtolower($message, 'UTF-8');
    $routes = [
        'nutrition' => ['repas', 'nutrition', 'calorie', 'protéine', 'proteine', 'glucide', 'lipide', 'régime', 'regime', 'aliment', 'coach nutritionnel', 'manger'],
        'health' => ['santé', 'sante', 'imc', 'allergie', 'douleur', 'traitement', 'médical', 'medical', 'symptôme', 'symptome', 'sommeil'],
        'planning' => ['planning', 'calendrier', 'programme', 'semaine', 'sport', 'météo', 'meteo', 'restaurant'],
        'challenge' => ['défi', 'defi', 'challenge', 'progression', 'badge', 'objectif', 'participant'],
        'admin' => ['utilisateur', 'admin', 'rôle', 'role', 'compte', 'statut', 'recherche', 'tri'],
    ];

    foreach ($routes as $route => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, $keyword) !== false) {
                return $route;
            }
        }
    }

    return 'general';
}

function hub_provider_for_route(string $route): string
{
    if (in_array($route, ['nutrition', 'health'], true) && hub_env('GEMINI_API_KEY') !== '') {
        return 'gemini';
    }
    if (hub_env('OPENAI_API_KEY') !== '') return 'openai';
    if (defined('GROQ_API_KEY') && trim((string)GROQ_API_KEY) !== '') return 'groq';
    if (hub_env('MISTRAL_API_KEY') !== '') return 'mistral';
    if (hub_env('ANTHROPIC_API_KEY') !== '') return 'anthropic';
    return 'local';
}

function hub_system_prompt(string $route): string
{
    $base = "Tu es GaiaLumen Hub, assistant IA du site GaiaLumen. Réponds en français, de façon utile, concise et structurée. Tu centralises les réponses dans une seule interface.";
    $safety = "Pour la santé et la nutrition: donne des conseils généraux prudents, ne diagnostique pas, ne prescris pas de médicament, et recommande un professionnel en cas de symptôme grave, allergie sévère, grossesse ou traitement médical.";
    $routePrompt = [
        'nutrition' => "Mode Coach Nutritionnel IA: aide sur repas, calories, aliments, équilibre nutritionnel et empreinte écologique.",
        'health' => "Mode Santé/Bien-être: aide avec IMC, suivi, sommeil, hydratation et prudence médicale.",
        'planning' => "Mode Planning: aide à organiser repas, sport, demandes et emplois du temps.",
        'challenge' => "Mode Défis: aide à analyser objectifs, motivation, progression et actions concrètes.",
        'admin' => "Mode Admin: aide à comprendre gestion utilisateurs, recherche, tri, statuts et rôles.",
        'general' => "Mode Général: oriente l'utilisateur vers le bon module GaiaLumen si utile.",
    ];

    return $base . "\n" . ($routePrompt[$route] ?? $routePrompt['general']) . "\n" . $safety;
}

function hub_normalize_history(array $history): array
{
    $messages = [];
    foreach (array_slice($history, -10) as $message) {
        if (!is_array($message)) continue;
        $role = (string)($message['role'] ?? 'user');
        if (!in_array($role, ['user', 'assistant'], true)) continue;
        $content = trim((string)($message['content'] ?? ''));
        if ($content === '') continue;
        $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 1800, 'UTF-8')];
    }
    return $messages;
}

function hub_local_reply(string $message, string $route): string
{
    $map = [
        'nutrition' => "Je peux t'aider à équilibrer un repas: indique les aliments, quantités, objectif nutritionnel et contraintes. Sans moteur IA configuré, je conseille déjà: légumes + protéines + céréales complètes, moins de sucre ajouté, et aliments de saison.",
        'health' => "Je peux t'aider à lire tes indicateurs santé de façon générale. Pour tout symptôme important ou traitement, il faut valider avec un professionnel de santé.",
        'planning' => "Pour le planning, donne-moi ton objectif, tes disponibilités et les contraintes. Je peux proposer une semaine simple avec repas, sport et rappels.",
        'challenge' => "Pour les défis, donne la progression actuelle, la date limite et le blocage. Je peux proposer un plan en petites actions.",
        'admin' => "Pour l'admin utilisateurs: combine recherche, rôle, statut et date. Si un filtre semble bloqué, actualise puis vérifie nom/email/rôle/statut.",
        'general' => "Je suis prêt. Demande-moi une aide sur nutrition, santé, planning, défis ou gestion utilisateur.",
    ];
    return $map[$route] ?? $map['general'];
}

function hub_call_groq(array $messages): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL non activé'];
    }

    $model = defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile';
    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.45,
            'max_tokens' => 900,
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($response === false || $code < 200 || $code >= 300 || !is_array($decoded)) {
        return ['ok' => false, 'error' => $error ?: ($decoded['error']['message'] ?? 'Groq indisponible')];
    }

    return [
        'ok' => true,
        'reply' => trim((string)($decoded['choices'][0]['message']['content'] ?? '')),
        'model' => $decoded['model'] ?? $model,
    ];
}

function hub_call_openai_compatible(string $provider, string $url, string $apiKey, string $model, array $messages): array
{
    if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'cURL non activé'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.45,
            'max_tokens' => 900,
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($response === false || $code < 200 || $code >= 300 || !is_array($decoded)) {
        return ['ok' => false, 'error' => $error ?: ($decoded['error']['message'] ?? "$provider indisponible")];
    }

    return [
        'ok' => true,
        'reply' => trim((string)($decoded['choices'][0]['message']['content'] ?? '')),
        'model' => $decoded['model'] ?? $model,
    ];
}

function hub_call_gemini(array $messages): array
{
    if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'cURL non activé'];
    $apiKey = hub_env('GEMINI_API_KEY');
    $model = hub_env('GEMINI_MODEL', 'gemini-2.0-flash');
    if ($apiKey === '') return ['ok' => false, 'error' => 'GEMINI_API_KEY manquante'];

    $text = implode("\n\n", array_map(static function ($message) {
        return strtoupper($message['role']) . ": " . $message['content'];
    }, $messages));

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . rawurlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => ['temperature' => 0.45, 'maxOutputTokens' => 900],
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($response === false || $code < 200 || $code >= 300 || !is_array($decoded)) {
        return ['ok' => false, 'error' => $error ?: ($decoded['error']['message'] ?? 'Gemini indisponible')];
    }

    return [
        'ok' => true,
        'reply' => trim((string)($decoded['candidates'][0]['content']['parts'][0]['text'] ?? '')),
        'model' => $model,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hub_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    hub_json(['success' => false, 'error' => 'JSON invalide'], 400);
}

$message = trim((string)($payload['message'] ?? ''));
if ($message === '') {
    hub_json(['success' => false, 'error' => 'Message requis'], 400);
}

$route = hub_detect_route($message);
$provider = hub_provider_for_route($route);
$history = hub_normalize_history($payload['history'] ?? []);
$messages = array_merge(
    [['role' => 'system', 'content' => hub_system_prompt($route)]],
    $history,
    [['role' => 'user', 'content' => $message]]
);

$result = ['ok' => false];
if ($provider === 'gemini') {
    $result = hub_call_gemini($messages);
} elseif ($provider === 'openai') {
    $result = hub_call_openai_compatible('openai', 'https://api.openai.com/v1/chat/completions', hub_env('OPENAI_API_KEY'), hub_env('OPENAI_MODEL', 'gpt-4o-mini'), $messages);
} elseif ($provider === 'mistral') {
    $result = hub_call_openai_compatible('mistral', 'https://api.mistral.ai/v1/chat/completions', hub_env('MISTRAL_API_KEY'), hub_env('MISTRAL_MODEL', 'mistral-small-latest'), $messages);
} elseif ($provider === 'groq') {
    $result = hub_call_groq($messages);
}

if (empty($result['ok']) || trim((string)($result['reply'] ?? '')) === '') {
    $result = [
        'ok' => true,
        'reply' => hub_local_reply($message, $route),
        'model' => 'rules',
    ];
    $provider = 'local';
}

hub_json([
    'success' => true,
    'reply' => $result['reply'],
    'route' => $route,
    'provider' => $provider,
    'model' => $result['model'] ?? '',
]);
