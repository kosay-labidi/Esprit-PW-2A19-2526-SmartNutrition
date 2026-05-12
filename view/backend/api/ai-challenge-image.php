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

function challenge_image_svg_fallback(string $prompt, int $seed): string {
    $colors = [
        ['#1F3D2B', '#3A86C4', '#5B3E96'],
        ['#0f766e', '#22c55e', '#2563eb'],
        ['#14532d', '#84cc16', '#0ea5e9'],
        ['#312e81', '#06b6d4', '#16a34a'],
    ];
    $palette = $colors[$seed % count($colors)];
    $safePrompt = htmlspecialchars(challenge_image_limit($prompt, 180), ENT_QUOTES, 'UTF-8');
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$palette[0]}"/>
      <stop offset=".55" stop-color="{$palette[1]}"/>
      <stop offset="1" stop-color="{$palette[2]}"/>
    </linearGradient>
    <radialGradient id="sun" cx=".72" cy=".24" r=".35">
      <stop offset="0" stop-color="#F2E8CF" stop-opacity=".78"/>
      <stop offset="1" stop-color="#F2E8CF" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="1200" height="800" fill="url(#bg)"/>
  <rect width="1200" height="800" fill="url(#sun)"/>
  <path d="M0 610 C180 540 310 650 500 585 C700 515 850 620 1200 500 L1200 800 L0 800Z" fill="#0A1A10" opacity=".28"/>
  <path d="M120 590 C230 520 360 520 470 595 C575 670 720 640 805 560 C895 475 1035 485 1120 560" fill="none" stroke="#F2E8CF" stroke-width="18" stroke-linecap="round" opacity=".45"/>
  <circle cx="316" cy="545" r="56" fill="none" stroke="#F2E8CF" stroke-width="18" opacity=".82"/>
  <circle cx="466" cy="545" r="56" fill="none" stroke="#F2E8CF" stroke-width="18" opacity=".82"/>
  <path d="M335 545 L390 470 L450 545 M390 470 L430 470 M390 470 L372 428" fill="none" stroke="#F2E8CF" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" opacity=".82"/>
  <path d="M710 520 C760 450 840 450 890 520 C820 545 770 545 710 520Z" fill="#F2E8CF" opacity=".78"/>
  <path d="M800 520 C800 470 815 435 855 405" fill="none" stroke="#F2E8CF" stroke-width="12" stroke-linecap="round" opacity=".78"/>
  <circle cx="900" cy="245" r="78" fill="#F2E8CF" opacity=".28"/>
  <title>{$safePrompt}</title>
</svg>
SVG;
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
    if (function_exists('curl_init')) {
        $ch = curl_init($remote);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 18,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'GaiaLumen/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if (is_string($body) && strlen($body) > 1000 && $code >= 200 && $code < 300 && stripos($type, 'image/') !== false) {
            header('Content-Type: ' . $type);
            header('Cache-Control: public, max-age=86400');
            echo $body;
            exit;
        }
    }

    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    echo challenge_image_svg_fallback($prompt, $seed);
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
