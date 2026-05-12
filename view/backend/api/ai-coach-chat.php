<?php
/**
 * AI Coach Chat API - GaiaLumen
 * Permet de discuter avec le Coach IA pour un participant spécifique.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../../../config.php');

function chat_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function coach_chat_fallback(array $participant, string $message, string $reason = ''): string {
    $progress = (int)($participant['objectif'] ?? 0);
    $target = (int)($participant['valeur_cible'] ?? 100);
    $name = (string)($participant['nom'] ?? 'ce participant');
    $challenge = (string)($participant['challenge_titre'] ?? 'ce défi');
    $lower = mb_strtolower($message, 'UTF-8');

    if (str_contains($lower, 'retard') || str_contains($lower, 'risque') || str_contains($lower, 'abandon')) {
        return "{$name} est à {$progress}% sur {$target}% pour « {$challenge} ». Proposez une action très simple aujourd'hui, puis un rappel court demain. L'objectif est de réduire la friction plutôt que d'augmenter la pression.";
    }
    if (str_contains($lower, 'message') || str_contains($lower, 'motivation') || str_contains($lower, 'encourag')) {
        return "Message conseillé: Bravo {$name}, chaque petite action compte pour « {$challenge} ». Choisis une action facile aujourd'hui, note ta progression, et garde le rythme.";
    }
    if (str_contains($lower, 'plan')) {
        return "Plan rapide: J1 action simple, J2 rappel, J3 mini-bilan, J4 encouragement dans le chat, J5 répétition, J6 petit palier, J7 bilan final. Progression actuelle: {$progress}%.";
    }

    return "Analyse locale: {$name} progresse à {$progress}% sur {$target}% dans « {$challenge} ». Je conseille un suivi court, concret et encourageant. " . ($reason ? "Note technique: {$reason}" : "");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chat_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$participantId = (int)($payload['participant_id'] ?? 0);
$message = trim((string)($payload['message'] ?? ''));

if ($participantId <= 0 || empty($message)) {
    chat_json(['ok' => false, 'error' => 'Données manquantes'], 400);
}

try {
    $db = Config::getConnexion();

    // 1. Récupérer les données du participant pour le contexte
    $q = $db->prepare("
        SELECT p.*, c.titre AS challenge_titre, c.valeur_cible
        FROM participant p
        LEFT JOIN challenge c ON c.id = p.id_challenge
        WHERE p.id = :id
    ");
    $q->execute(['id' => $participantId]);
    $participant = $q->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        chat_json(['ok' => false, 'error' => 'Participant introuvable'], 404);
    }

    // 2. Préparer l'historique récent (optionnel, pour la mémoire du chat)
    // Pour l'instant on fait du stateless ou on récupère les derniers messages chat_messages
    $q = $db->prepare("
        SELECT body, created_at
        FROM chat_messages
        WHERE participant_id = :pid
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $q->execute(['pid' => $participantId]);
    $history = array_reverse($q->fetchAll(PDO::FETCH_ASSOC));

    $apiKey = trim((string)GROQ_API_KEY);
    $model = GROQ_MODEL;

    $system = "Tu es le Coach IA de GaiaLumen. Tu discutes avec un administrateur à propos de la progression de {$participant['nom']} dans le défi '{$participant['challenge_titre']}'.
Progression actuelle : {$participant['objectif']}% / {$participant['valeur_cible']}%.
Engagement : {$participant['engagement']}/10.
Sois concis, analytique et encourageant. Réponds en français.";

    $messages = [['role' => 'system', 'content' => $system]];
    foreach ($history as $h) {
        $messages[] = ['role' => 'user', 'content' => $h['body']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    if ($apiKey === '' || !function_exists('curl_init')) {
        chat_json([
            'ok' => true,
            'reply' => coach_chat_fallback($participant, $message, $apiKey === '' ? 'clé IA manquante' : 'cURL non activé'),
            'provider' => 'local',
            'model' => 'rules',
        ]);
    }

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
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);

    $resp = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($resp, true);
    $reply = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
    if ($resp === false || $httpCode < 200 || $httpCode >= 300 || $reply === '') {
        $detail = $curlError ?: ($decoded['error']['message'] ?? 'service IA indisponible');
        chat_json([
            'ok' => true,
            'reply' => coach_chat_fallback($participant, $message, $detail),
            'provider' => 'local',
            'model' => 'rules',
            'upstream_error' => $detail,
        ]);
    }

    chat_json([
        'ok' => true,
        'reply' => $reply,
        'provider' => 'groq',
        'model' => $decoded['model'] ?? $model,
    ]);

} catch (Throwable $e) {
    chat_json(['ok' => false, 'error' => $e->getMessage()], 500);
}
