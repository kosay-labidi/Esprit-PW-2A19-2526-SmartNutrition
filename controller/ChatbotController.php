<?php
// controller/ChatbotController.php
// Endpoint utilise par l'assistant sante: POST ?action=ask

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

function chatbot_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function chatbot_current_user_id(array $input): int
{
    foreach ([
        $input['user_id'] ?? null,
        $_SESSION['user']['id_utilisateur'] ?? null,
        $_SESSION['user_id'] ?? null,
        $_SESSION['id_utilisateur'] ?? null,
    ] as $value) {
        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }
    }

    return 1;
}

function chatbot_get_dossier_context(int $userId): array
{
    try {
        $db = Config::getConnexion();
        $stmt = $db->prepare(
            'SELECT poids, taille, imc, allergie, gravite_allergie, maladies, traitement, regime_special
             FROM dossier_medical
             WHERE id_utilisateur = :user_id
             ORDER BY id_dossier DESC
             LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function chatbot_detect_sentiment(string $message): string
{
    $lower = mb_strtolower($message, 'UTF-8');
    $negativeWords = ['douleur', 'mal', 'fatigue', 'triste', 'stress', 'anxieux', 'anxieuse', 'peur', 'insomnie', 'vomir', 'vertige'];
    $positiveWords = ['bien', 'mieux', 'content', 'heureux', 'motivé', 'energie', 'énergie'];

    foreach ($negativeWords as $word) {
        if (mb_strpos($lower, $word) !== false) {
            return 'NEGATIVE';
        }
    }

    foreach ($positiveWords as $word) {
        if (mb_strpos($lower, $word) !== false) {
            return 'POSITIVE';
        }
    }

    return 'NEUTRAL';
}

function chatbot_suggest_tcm_herbs(string $message, array $dossier): array
{
    $lower = mb_strtolower($message . ' ' . implode(' ', array_filter($dossier)), 'UTF-8');
    $suggestions = [];

    $map = [
        'stress' => 'camomille',
        'anx' => 'camomille',
        'insomnie' => 'lavande',
        'sommeil' => 'lavande',
        'digestion' => 'menthe poivrée',
        'ventre' => 'menthe poivrée',
        'fatigue' => 'gingembre',
        'rhume' => 'gingembre',
    ];

    foreach ($map as $keyword => $herb) {
        if (mb_strpos($lower, $keyword) !== false && !in_array($herb, $suggestions, true)) {
            $suggestions[] = $herb;
        }
    }

    $allergies = mb_strtolower((string) ($dossier['allergie'] ?? ''), 'UTF-8');
    return array_values(array_filter($suggestions, static function ($herb) use ($allergies) {
        return $allergies === '' || mb_strpos($allergies, mb_strtolower($herb, 'UTF-8')) === false;
    }));
}

function chatbot_predicted_outcome(array $dossier): string
{
    $imc = isset($dossier['imc']) ? (float) $dossier['imc'] : 0.0;

    if ($imc <= 0) {
        return 'Ajoutez votre poids et votre taille dans le dossier médical pour obtenir une estimation plus personnalisée.';
    }

    if ($imc >= 18.5 && $imc < 25) {
        return 'Votre IMC est dans la zone normale; avec une bonne régularité, le maintien de vos objectifs est réaliste.';
    }

    if ($imc < 18.5) {
        return 'Votre IMC indique une insuffisance pondérale; une progression prudente avec suivi professionnel est recommandée.';
    }

    return 'Votre IMC indique un suivi nutritionnel utile; des objectifs progressifs et réguliers donnent les meilleurs résultats.';
}

function chatbot_call_groq(array $messages): string
{
    $apiKey = trim((string) GROQ_API_KEY);
    if ($apiKey === '') {
        chatbot_json([
            'error' => 'GROQ_API_KEY manquante côté serveur. Ajoutez-la dans .env ou dans les variables système puis redémarrez Apache/XAMPP.'
        ], 500);
    }

    if (!function_exists('curl_init')) {
        chatbot_json(['error' => 'Extension PHP cURL non activée. Activez curl dans php.ini.'], 500);
    }

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => GROQ_MODEL,
            'messages' => $messages,
            'temperature' => 0.35,
            'max_tokens' => 700,
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 35,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        chatbot_json(['error' => 'Erreur de connexion Groq: ' . $curlError], 502);
    }

    $decoded = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $decoded['error']['message'] ?? 'Réponse invalide du service Groq.';
        chatbot_json(['error' => 'Erreur Groq', 'details' => $message, 'status' => $httpCode], 502);
    }

    $reply = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    if ($reply === '') {
        chatbot_json(['error' => 'Groq a répondu sans contenu exploitable.'], 502);
    }

    return $reply;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_GET['action'] ?? '') !== 'ask') {
    chatbot_json(['error' => 'Not found'], 404);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    chatbot_json(['error' => 'JSON invalide'], 400);
}

$message = trim((string) ($input['message'] ?? ''));
if ($message === '') {
    chatbot_json(['error' => 'Message is required'], 400);
}

$userId = chatbot_current_user_id($input);
$dossier = chatbot_get_dossier_context($userId);
$sentiment = chatbot_detect_sentiment($message);
$herbs = chatbot_suggest_tcm_herbs($message, $dossier);
$predictedOutcome = chatbot_predicted_outcome($dossier);

$context = [
    'poids' => $dossier['poids'] ?? 'non renseigné',
    'taille' => $dossier['taille'] ?? 'non renseignée',
    'imc' => $dossier['imc'] ?? 'non renseigné',
    'allergies' => $dossier['allergie'] ?? 'aucune renseignée',
    'gravite_allergie' => $dossier['gravite_allergie'] ?? 'non renseignée',
    'maladies' => $dossier['maladies'] ?? 'aucune renseignée',
    'traitement' => $dossier['traitement'] ?? 'aucun renseigné',
    'regime_special' => $dossier['regime_special'] ?? 'aucun renseigné',
    'herbs' => $herbs ? implode(', ', $herbs) : 'aucune suggestion automatique',
    'prediction' => $predictedOutcome,
];

$systemPrompt = "Tu es l'assistant santé et nutrition de GaiaLumen. Réponds en français, clairement et avec prudence.
Tu n'es pas médecin: ne pose pas de diagnostic, ne prescris pas de médicament et recommande de consulter un professionnel en cas de symptôme grave, douleur intense, grossesse, allergie sévère ou traitement médical.

Contexte du dossier utilisateur:
- Poids: {$context['poids']}
- Taille: {$context['taille']}
- IMC: {$context['imc']}
- Allergies: {$context['allergies']} (gravité: {$context['gravite_allergie']})
- Maladies: {$context['maladies']}
- Traitement actuel: {$context['traitement']}
- Régime spécial: {$context['regime_special']}
- Plantes MTC suggérées localement: {$context['herbs']}
- Estimation locale: {$context['prediction']}

Réponds avec des conseils nutritionnels généraux, sûrs et personnalisés au contexte disponible. Si tu mentionnes des plantes, ajoute qu'il faut consulter un praticien qualifié avant usage.";

$reply = chatbot_call_groq([
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $message],
]);

chatbot_json([
    'response' => $reply,
    'sentiment' => $sentiment,
    'tcm_herbs_suggested' => $herbs,
    'predicted_outcome' => $predictedOutcome,
    'provider' => 'groq',
    'model' => GROQ_MODEL,
]);
