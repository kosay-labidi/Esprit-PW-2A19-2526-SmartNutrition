<?php
/**
 * Generate an AI image URL for a GaiaLumen challenge from the filled admin form.
 *
 * This endpoint returns a generated image URL. It uses Pollinations' image API,
 * so the admin form can store a normal URL in the existing `challenge.image` field.
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

function challenge_image_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function challenge_image_limit(string $value, int $limit): string {
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function challenge_image_ascii_prompt(string $value): string {
    if (function_exists('iconv')) {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }
    $value = preg_replace('/[^a-zA-Z0-9,.:%()\/\- ]+/', ' ', $value);
    return trim(preg_replace('/\s+/', ' ', $value));
}

function challenge_image_category_context(string $objective): string {
    $map = [
        'dechets' => 'zero waste, recycling, clean campus, reusable bottles',
        'eau' => 'water conservation, blue water drops, responsible usage',
        'repas' => 'healthy food, sustainable meals, fresh vegetables',
        'transport' => 'bicycle mobility, walking, green urban transport',
        'energie' => 'renewable energy, solar panels, efficient lights',
        'co2' => 'low carbon lifestyle, green planet, climate action',
    ];
    return $map[$objective] ?? 'eco friendly lifestyle, positive community challenge';
}

function challenge_image_absolute_url(string $path): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $prompt = challenge_image_limit((string)($_GET['prompt'] ?? ''), 850);
    $seed = max(1, (int)($_GET['seed'] ?? 1));
    if ($prompt === '') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Prompt manquant';
        exit;
    }
    $remote = 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt)
        . '?width=1200&height=800&seed=' . $seed
        . '&model=flux&enhance=true&nologo=true';
    header('Location: ' . $remote, true, 302);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    challenge_image_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    challenge_image_json(['ok' => false, 'error' => 'JSON invalide'], 400);
}

$title = challenge_image_limit((string)($payload['titre'] ?? ''), 90);
$description = challenge_image_limit((string)($payload['description'] ?? ''), 260);
$type = challenge_image_limit((string)($payload['type'] ?? 'collectif'), 40);
$objective = challenge_image_limit((string)($payload['objectif'] ?? ''), 40);
$objectiveLabel = challenge_image_limit((string)($payload['objectif_label'] ?? $objective), 80);
$target = max(1, min(100, (int)($payload['valeur_cible'] ?? 50)));
$icon = challenge_image_limit((string)($payload['streak_icon'] ?? ''), 12);

if (strlen($title) < 3) {
    challenge_image_json(['ok' => false, 'error' => 'Titre du défi requis'], 400);
}
if (strlen($description) < 10) {
    challenge_image_json(['ok' => false, 'error' => 'Description du défi requise'], 400);
}

$categoryContext = challenge_image_category_context($objective);
$rawPrompt = implode(', ', array_filter([
    'GaiaLumen challenge hero image',
    $title,
    $description,
    $type . ' challenge',
    'category ' . $objectiveLabel,
    'target ' . $target . ' percent',
    $categoryContext,
    $icon ? 'visual mood inspired by ' . $icon : '',
    'modern eco wellness app banner',
    'realistic polished illustration',
    'bright natural light',
    'motivating community atmosphere',
    'no text, no letters, no watermark, no logo',
]));

$prompt = challenge_image_ascii_prompt($rawPrompt);
$prompt = challenge_image_limit($prompt, 850);
$seed = abs(crc32($title . '|' . $description . '|' . $objective . '|' . $target));
$imageUrl = challenge_image_absolute_url('ai-challenge-image.php?prompt=' . rawurlencode($prompt) . '&seed=' . $seed);

challenge_image_json([
    'ok' => true,
    'provider' => 'pollinations',
    'image_url' => $imageUrl,
    'prompt' => $prompt,
]);
