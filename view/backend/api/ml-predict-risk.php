<?php
/**
 * ML Success Predictor - Phase 2
 * Calcule un score de probabilité de réussite basé sur l'activité chat et la progression.
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

require_once(__DIR__ . '/../../../config.php');

function risk_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Calcule le volume d'activité chat sur les 7 derniers jours.
 */
function risk_get_chat_activity(PDO $db, int $participantId): int {
    $q = $db->prepare("
        SELECT COUNT(*) 
        FROM chat_messages 
        WHERE participant_id = :pid 
          AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $q->execute(['pid' => $participantId]);
    return (int)$q->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    risk_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = $_POST;
$id = (int)($payload['id_participant'] ?? $payload['id'] ?? 0);

if ($id <= 0) {
    risk_json(['success' => false, 'error' => 'id_participant requis'], 400);
}

try {
    $db = Config::getConnexion();
    
    // 1. Fetch Participant & Challenge data
    $q = $db->prepare("
        SELECT p.*, c.titre AS challenge_titre, c.date_debut, c.date_fin, c.objectif AS challenge_objectif
        FROM participant p
        LEFT JOIN challenge c ON c.id = p.id_challenge
        WHERE p.id = :id
        LIMIT 1
    ");
    $q->execute(['id' => $id]);
    $row = $q->fetch();
    if (!$row) risk_json(['success' => false, 'error' => 'Participant introuvable'], 404);

    // 2. Metrics calculation
    $chatActivity = risk_get_chat_activity($db, $id);
    $progress = (int)($row['objectif'] ?? 0);
    
    $start = strtotime($row['date_debut'] ?? '');
    $end = strtotime($row['date_fin'] ?? '');
    $now = time();
    
    $totalDays = ($end - $start) / 86400;
    $elapsedDays = ($now - $start) / 86400;
    $expectedProgress = $totalDays > 0 ? min(100, max(0, ($elapsedDays / $totalDays) * 100)) : 0;
    
    // 3. Simple ML-like Scoring Logic (Heuristic model)
    $score = 50; // Base score
    
    // Factor: Progress Gap
    $gap = $progress - $expectedProgress;
    $score += ($gap * 0.5); // Positive gap helps, negative gap hurts
    
    // Factor: Chat Engagement (Social signal)
    if ($chatActivity > 5) $score += 15;
    elseif ($chatActivity > 0) $score += 5;
    else $score -= 10;
    
    // Factor: Declared Engagement
    if ($row['engagement'] == 1) $score += 10;
    else $score -= 5;

    // Factor: Motivation length (proxy for commitment)
    $motivationLen = strlen($row['motivation'] ?? '');
    if ($motivationLen > 100) $score += 5;

    $score = max(0, min(100, (int)$score));

    // 4. Categorization
    $riskLevel = 'Faible';
    if ($score < 40) $riskLevel = 'Élevé';
    elseif ($score < 70) $riskLevel = 'Moyen';

    // 5. Groq Recommendation (Optional)
    $recommendation = "Continuer l'engagement social.";
    $apiKey = GROQ_API_KEY;
    if ($apiKey) {
        // We could call Groq here for a more refined recommendation based on the score
        // For brevity in this phase, we use a solid heuristic
        if ($score < 40) $recommendation = "Intervention urgente requise : le participant décroche.";
        elseif ($score < 70) $recommendation = "Encourager le participant à partager une petite victoire.";
    }

    risk_json([
        'success' => true,
        'participant_id' => $id,
        'metrics' => [
            'progress' => $progress,
            'expected_progress' => (int)$expectedProgress,
            'chat_activity_7d' => $chatActivity,
            'gap' => (int)$gap
        ],
        'prediction' => [
            'success_probability' => $score,
            'risk_level' => $riskLevel,
            'recommendation' => $recommendation,
            'analysis_date' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Throwable $e) {
    error_log('Erreur ml-predict-risk: ' . $e->getMessage());
    risk_json(['success' => false, 'error' => 'Erreur serveur ML'], 500);
}
