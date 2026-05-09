<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../config.php');

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Clé Groq chargée depuis config.php
// ── Langues supportées ────────────────────────────────────────────────────
$LANGUES = [
    'ar' => 'arabe',
    'en' => 'anglais',
    'es' => 'espagnol',
    'de' => 'allemand',
    'it' => 'italien',
    'pt' => 'portugais',
];

// ── Appel API Groq ────────────────────────────────────────────────────────
function callGroq(string $systemPrompt, string $userMessage, int $maxTokens = 4000): array {
    $payload = json_encode([
        'model'       => GROQ_MODEL,
        'max_tokens'  => $maxTokens,
        'temperature' => 0.3,
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMessage],
        ],
    ]);

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60,
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
    if ($code !== 200) return ['ok' => false, 'error' => "Erreur API Groq ($code) : $raw"];

    $data = json_decode($raw, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    return ['ok' => true, 'text' => $text];
}

// ── Récupérer le planning depuis la base ─────────────────────────────────
function getPlanningData(int $id): ?array {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("SELECT * FROM planning WHERE id_demande = :id ORDER BY date, id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) { return null; }
}

// ── DISPATCH ──────────────────────────────────────────────────────────────
$id     = (int)($_GET['id']     ?? 0);
$langue = trim($_GET['langue']  ?? 'en');

global $LANGUES;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

if (!array_key_exists($langue, $LANGUES)) {
    echo json_encode(['success' => false, 'error' => 'Langue non supportée']);
    exit;
}

$nomLangue = $LANGUES[$langue];

// Récupérer les lignes du planning
$lignes = getPlanningData($id);
if (!$lignes || count($lignes) === 0) {
    echo json_encode(['success' => false, 'error' => 'Aucun planning trouvé pour cette demande.']);
    exit;
}

// Construire le texte à traduire (toutes les descriptions)
$textesPourTraduction = [];
foreach ($lignes as $i => $ligne) {
    $textesPourTraduction[$i] = $ligne['description'];
}

$jsonTextes = json_encode($textesPourTraduction, JSON_UNESCAPED_UNICODE);

// ── Prompt traduction ─────────────────────────────────────────────────────
$system = "Tu es un traducteur professionnel spécialisé en nutrition et sport. "
        . "Traduis fidèlement le contenu en conservant les emojis, la mise en forme et le sens médical/nutritionnel. "
        . "Réponds UNIQUEMENT avec un JSON valide, sans texte avant ou après, sans markdown.";

$user = "Traduis ces textes de planning nutritionnel du français vers le $nomLangue.\n"
      . "Conserve absolument tous les emojis (🌅🍽️🌙🏃💡📋 etc.), les deux-points, les tirets.\n"
      . "Réponds uniquement avec le même objet JSON avec les mêmes clés numériques mais les valeurs traduites :\n\n"
      . $jsonTextes;

$res = callGroq($system, $user, 4000);

if (!$res['ok']) {
    echo json_encode(['success' => false, 'error' => $res['error']]);
    exit;
}

// Nettoyer et parser
$text = trim($res['text']);
$text = preg_replace('/^```json\s*/i', '', $text);
$text = preg_replace('/^```\s*/i',    '', $text);
$text = preg_replace('/```\s*$/i',    '', $text);
$text = trim($text);

$traduits = json_decode($text, true);
if (!is_array($traduits)) {
    echo json_encode(['success' => false, 'error' => 'Réponse IA invalide', 'raw' => substr($text, 0, 300)]);
    exit;
}

// Reconstruire les lignes avec descriptions traduites
$lignesTraduites = [];
foreach ($lignes as $i => $ligne) {
    $ligne['description'] = $traduits[$i] ?? $ligne['description'];
    $lignesTraduites[] = $ligne;
}

echo json_encode([
    'success'  => true,
    'langue'   => $langue,
    'nom'      => $nomLangue,
    'lignes'   => $lignesTraduites,
    'nb'       => count($lignesTraduites),
]);
exit;