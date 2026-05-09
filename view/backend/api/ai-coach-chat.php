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

    $apiKey = getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? '');
    $model = getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile');

    $system = "Tu es le Coach IA de GaiaLumen. Tu discutes avec un administrateur à propos de la progression de {$participant['nom']} dans le défi '{$participant['challenge_titre']}'.
Progression actuelle : {$participant['objectif']}% / {$participant['valeur_cible']}%.
Engagement : {$participant['engagement']}/10.
Sois concis, analytique et encourageant. Réponds en français.";

    $messages = [['role' => 'system', 'content' => $system]];
    foreach ($history as $h) {
        $messages[] = ['role' => 'user', 'content' => $h['body']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

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
    curl_close($ch);

    $decoded = json_decode($resp, true);
    $reply = $decoded['choices'][0]['message']['content'] ?? 'Désolé, je ne peux pas répondre pour le moment.';

    chat_json([
        'ok' => true,
        'reply' => $reply
    ]);

} catch (Throwable $e) {
    chat_json(['ok' => false, 'error' => $e->getMessage()], 500);
}
