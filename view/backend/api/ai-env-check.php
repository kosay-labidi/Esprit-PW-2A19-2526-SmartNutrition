<?php
header('Content-Type: application/json; charset=utf-8');

$key = getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? '');

echo json_encode([
    'ok' => true,
    'groq_key_visible' => $key !== '',
    'groq_key_prefix' => $key !== '' ? substr($key, 0, 8) : '',
    'curl_loaded' => function_exists('curl_init'),
    'php_sapi' => PHP_SAPI,
], JSON_UNESCAPED_UNICODE);
