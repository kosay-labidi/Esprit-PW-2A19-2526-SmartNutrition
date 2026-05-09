<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../../config.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Clé Groq chargée depuis config.php
// ── Construire le system prompt avec données réelles ─────────────────────
function buildCoachSystemPrompt(array $d): string {
    $cal = (int)($d['calories'] ?? 2000);
    if ($cal < 1600)     $obj = 'perte de poids (déficit calorique)';
    elseif ($cal > 2800) $obj = 'prise de masse (surplus calorique)';
    else                 $obj = 'maintien du poids';

    $budget      = ($d['budget'] ?? '?') . ' / ' . ($d['type_budget'] ?? 'jour');
    $sport       = $d['activite_sportive'] ?? 'non renseignée';
    $durSport    = ($d['duree_sport_hebdo'] ?? 0) . ' min/semaine';
    $coucher     = $d['heure_coucher']   ?? '22:00';
    $reveil      = $d['heure_reveil']    ?? '07:00';
    $qualSommeil = $d['qualite_sommeil'] ?? 'normale';

    $h1 = (int)explode(':', $coucher)[0];
    $h2 = (int)explode(':', $reveil)[0];
    $hS = ($h2 > $h1) ? ($h2 - $h1) : (24 - $h1 + $h2);

    return "Tu es un Coach Nutritionnel IA professionnel intégré dans SmartNutrition.
Tu réponds TOUJOURS en français avec un ton motivant mais réaliste.

═══════════ PROFIL UTILISATEUR ═══════════
• Objectif déduit   : {$obj}
• Calories cibles   : {$cal} kcal/jour
• Budget alimentaire: {$budget}
• Sport pratiqué    : {$sport}
• Volume sportif    : {$durSport}
• Coucher / Réveil  : {$coucher} → {$reveil} ({$hS}h de sommeil)
• Qualité du sommeil: {$qualSommeil}
══════════════════════════════════════════

RÈGLES ABSOLUES :
1. Adapter chaque réponse au profil ci-dessus (calories, budget, sport, sommeil)
2. Structurer la réponse avec ces sections (utilise celles qui sont pertinentes) :

🔥 Objectif : (résumer l'intention de l'utilisateur)
🍽️ Alimentation : (conseils, aliments, erreurs à éviter)
🏋️ Sport : (entraînement adapté à {$sport})
😴 Sommeil : (conseil basé sur {$hS}h, qualité '{$qualSommeil}')
💡 Astuce : (1 conseil immédiatement applicable)
📊 Estimation : Calories: X | Protéines: Xg | Glucides: Xg | Lipides: Xg

RÈGLES DE FORME :
- Max 200 mots par réponse
- Exemples d'aliments adaptés au budget ({$budget})
- Ne jamais inventer de données médicales
- Si hors sujet : répondre brièvement, puis recentrer sur nutrition/sport";
}

// ── Appel API Groq avec historique ───────────────────────────────────────
function callGroqChat(string $systemPrompt, array $history, int $maxTokens = 1500): array {
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($history as $msg) {
        if (isset($msg['role'], $msg['content'])) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
    }

    $payload = json_encode([
        'model'       => GROQ_MODEL,
        'max_tokens'  => $maxTokens,
        'temperature' => 0.7,
        'messages'    => $messages,
    ]);

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)          return ['ok' => false, 'error' => 'Connexion échouée : ' . $err];
    if ($code !== 200) return ['ok' => false, 'error' => "Erreur API ($code)"];

    $data = json_decode($raw, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    return ['ok' => true, 'text' => trim($text)];
}

// ── Récupérer la demande avec sport/sommeil ──────────────────────────────
function getDemandeAvecProfil(int $id): ?array {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("
            SELECT d.*,
                   ss.activite_sportive, ss.duree_sport_hebdo,
                   ss.heure_coucher, ss.heure_reveil, ss.qualite_sommeil
            FROM demandeplanning d
            LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
            WHERE d.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) { return null; }
}

// ── MAIN ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$id       = (int)($body['id_demande'] ?? 0);
$question = trim($body['question'] ?? '');
$history  = $body['history'] ?? [];

if ($id <= 0 || empty($question)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

$demande = getDemandeAvecProfil($id);
if (!$demande) {
    echo json_encode(['success' => false, 'error' => 'Demande introuvable']);
    exit;
}

// Ajouter la question dans l'historique
$history[] = ['role' => 'user', 'content' => $question];

$systemPrompt = buildCoachSystemPrompt($demande);
$result       = callGroqChat($systemPrompt, $history);

if (!$result['ok']) {
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

echo json_encode([
    'success'  => true,
    'response' => $result['text'],
    'question' => $question,
]);