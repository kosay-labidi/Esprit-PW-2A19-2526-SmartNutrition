<?php
/**
 * AI Challenge Summary API - GaiaLumen
 * Provides an AI-generated summary of a challenge, its participants, and trends.
 * Caches results for 24 hours.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once(__DIR__ . '/../../../config.php');

function summary_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function coach_days_between(?string $start, ?string $end): int {
    if (!$start || !$end) return 1;
    $a = strtotime($start);
    $b = strtotime($end);
    if (!$a || !$b || $b <= $a) return 1;
    return max(1, (int)ceil(($b - $a) / 86400));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    summary_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$challengeId = (int)($payload['challenge_id'] ?? 0);

if ($challengeId <= 0) {
    summary_json(['ok' => false, 'error' => 'challenge_id requis'], 400);
}

try {
    $db = Config::getConnexion();

    // 1. Check History Mode
    if (isset($payload['mode']) && $payload['mode'] === 'history') {
        $q = $db->prepare("
            SELECT summary_json, created_at 
            FROM challenge_ai_summaries 
            WHERE challenge_id = :id 
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $q->execute(['id' => $challengeId]);
        $history = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach($history as &$h) {
            $h['summary'] = json_decode($h['summary_json'], true);
            unset($h['summary_json']);
        }
        summary_json(['ok' => true, 'history' => $history]);
    }

    // 2. Check cache (24h)
    $q = $db->prepare("
        SELECT summary_json, created_at 
        FROM challenge_ai_summaries 
        WHERE challenge_id = :id 
          AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $q->execute(['id' => $challengeId]);
    $cached = $q->fetch();

    if ($cached && !isset($payload['force_refresh'])) {
        summary_json([
            'ok' => true,
            'cached' => true,
            'summary' => json_decode($cached['summary_json'], true),
            'generated_at' => $cached['created_at']
        ]);
    }

    // 3. Fetch Challenge & Participants data
    $q = $db->prepare("
        SELECT c.*, 
               COUNT(p.id) as participants_count,
               COALESCE(AVG(p.objectif), 0) as avg_progress,
               COALESCE(AVG(p.engagement), 0) as avg_engagement
        FROM challenge c
        LEFT JOIN participant p ON p.id_challenge = c.id
        WHERE c.id = :id
        GROUP BY c.id
    ");
    $q->execute(['id' => $challengeId]);
    $challenge = $q->fetch();

    if (!$challenge) {
        summary_json(['ok' => false, 'error' => 'Défi introuvable'], 404);
    }

    // Fetch some recent participant details for context
    $q = $db->prepare("
        SELECT nom, motivation, action, objectif, engagement, date_inscription
        FROM participant
        WHERE id_challenge = :id
        ORDER BY date_inscription DESC
        LIMIT 10
    ");
    $q->execute(['id' => $challengeId]);
    $participants = $q->fetchAll();

    // 4. Call Groq
    $apiKey = getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? '');
    if (!$apiKey) {
        summary_json(['ok' => false, 'error' => 'GROQ_API_KEY manquante'], 500);
    }

    $model = getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile');
    $system = "Tu es un assistant IA d'analyse de défis collaboratifs pour la plateforme GaiaLumen. 
 Tu reçois les statistiques globales d'un défi. 
 
 Données du défi : 
 - Titre : {$challenge['titre']} (#{$challenge['id']}) 
 - Type : {$challenge['type']} 
 - Objectif cible : {$challenge['valeur_cible']} 
 - Statut : {$challenge['statut']} 
 - Nombre de participants : {$challenge['participants_count']} 
 - Progression moyenne : " . round((float)$challenge['avg_progress'], 2) . "% 
 - Engagement moyen : " . round((float)$challenge['avg_engagement'], 2) . "% 
 - Vues : {$challenge['nb_vues']} | Likes : {$challenge['nb_likes']} 
 - Jours restants : " . coach_days_between(date('Y-m-d'), $challenge['date_fin'] ?? null) . " 
 
 Réponds UNIQUEMENT en JSON valide, sans aucun texte avant ou après, sans balises markdown. 
 Format exact : 
 { 
   \"score_sante\": <entier 0-100>, 
   \"synthese_participants\": \"<2 phrases résumant le profil et la motivation des participants>\", 
   \"tendances_engagement\": \"<2 phrases sur les tendances de progression et d'engagement>\", 
   \"points_vigilance\": \"<1-2 phrases sur les risques ou points faibles à surveiller>\", 
   \"recommandations\": [ 
     \"<recommandation stratégique 1>\", 
     \"<recommandation stratégique 2>\", 
     \"<recommandation stratégique 3>\" 
   ] 
 }";

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
            'temperature' => 0.3,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Génère l'analyse pour ce défi."],
            ],
            'response_format' => ['type' => 'json_object']
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 30,
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new Error("Erreur Curl: " . $err);
    }

    $decoded = json_decode($resp, true);
    $content = $decoded['choices'][0]['message']['content'] ?? '';
    $summary = json_decode($content, true);

    if (!$summary) {
        throw new Error("Réponse IA invalide: " . substr($resp, 0, 500));
    }

    // 5. Save to cache (Update existing or Insert new if no unique constraint)
    // Note: If uq_challenge exists, this will update. If not, it will insert a new row for history.
    $q = $db->prepare("
        INSERT INTO challenge_ai_summaries (challenge_id, summary_json, created_at)
        VALUES (:cid, :json, NOW())
        ON DUPLICATE KEY UPDATE summary_json = VALUES(summary_json), created_at = NOW()
    ");
    $q->execute([
        'cid' => $challengeId,
        'json' => json_encode($summary, JSON_UNESCAPED_UNICODE)
    ]);

    summary_json([
        'ok' => true,
        'cached' => false,
        'summary' => $summary,
        'generated_at' => date('Y-m-d H:i:s')
    ]);

} catch (Throwable $e) {
    error_log('Erreur ai-challenge-summary: ' . $e->getMessage());
    summary_json(['ok' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()], 500);
}
