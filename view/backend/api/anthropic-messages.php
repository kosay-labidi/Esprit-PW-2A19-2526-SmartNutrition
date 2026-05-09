<?php
/**
 * Server-side AI chat proxy to avoid exposing secrets in frontend.
 *
 * Preferred env vars:
 *  - GROQ_API_KEY  (Groq Chat Completions, OpenAI-compatible)
 *  - GROQ_MODEL    (optional, default: llama-3.3-70b-versatile)
 *
 * Backward compatible with the existing frontend request shape:
 * { model, max_tokens, system, messages:[{role, content}] }
 *
 * Response is normalized to Anthropic-like:
 * { content: [{ type: "text", text: "..." }], provider, model }
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Basic CORS for same-origin dev; adjust if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$apiKey = GROQ_API_KEY;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfigured: GROQ_API_KEY missing']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$requestedModel = (string)($payload['model'] ?? '');
$model = (string)(getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile'));
if ($requestedModel !== '' && stripos($requestedModel, 'claude') === false) {
    $model = $requestedModel;
}
$maxTokens = (int)($payload['max_tokens'] ?? 800);
$system = (string)($payload['system'] ?? '');
$messages = $payload['messages'] ?? null;

if (!is_array($messages) || $system === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: system, messages[]']);
    exit;
}

$groqMessages = [];
if ($system !== '') {
    $groqMessages[] = ['role' => 'system', 'content' => $system];
}
foreach ($messages as $message) {
    if (!is_array($message)) continue;
    $role = (string)($message['role'] ?? 'user');
    $content = $message['content'] ?? '';
    if (is_array($content)) {
        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && isset($part['text'])) $parts[] = (string)$part['text'];
            elseif (is_string($part)) $parts[] = $part;
        }
        $content = implode("\n", $parts);
    }
    $content = trim((string)$content);
    if ($content === '') continue;
    if (!in_array($role, ['system', 'user', 'assistant'], true)) $role = 'user';
    $groqMessages[] = ['role' => $role, 'content' => $content];
}

if (count($groqMessages) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid messages']);
    exit;
}

// Forward to Groq Chat Completions.
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => $model,
        'max_tokens' => max(1, min($maxTokens, 4096)),
        'temperature' => 0.7,
        'messages' => $groqMessages,
    ]),
    CURLOPT_TIMEOUT => 30,
]);

$respBody = curl_exec($ch);
$respCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($respBody === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream request failed', 'detail' => $err]);
    exit;
}

$decoded = json_decode($respBody, true);
if ($respCode < 200 || $respCode >= 300 || !is_array($decoded)) {
    http_response_code($respCode ?: 502);
    echo $respBody;
    exit;
}

$text = (string)($decoded['choices'][0]['message']['content'] ?? '');
http_response_code(200);
echo json_encode([
    'content' => [
        ['type' => 'text', 'text' => $text !== '' ? $text : 'Je suis là pour vous aider sur vos défis.']
    ],
    'provider' => 'groq',
    'model' => $decoded['model'] ?? $model,
    'usage' => $decoded['usage'] ?? null,
], JSON_UNESCAPED_UNICODE);
