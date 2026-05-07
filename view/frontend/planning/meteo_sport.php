<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../../config.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Clé Groq chargée depuis config.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true);
$id_demande = (int)($body['id_demande'] ?? 0);
$meteo      = $body['meteo']  ?? [];
$sports     = $body['sports'] ?? [];

if ($id_demande <= 0 || empty($meteo)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

function getProfil(int $id): array {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("
            SELECT d.calories, d.budget, d.type_budget,
                   ss.activite_sportive, ss.duree_sport_hebdo
            FROM demandeplanning d
            LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
            WHERE d.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) { return []; }
}

$profil       = getProfil($id_demande);
$sport_profil = $profil['activite_sportive'] ?? 'non renseigné';

// duree_sport_hebdo = total semaine → on calcule la durée par séance
$duree_hebdo  = (int)($profil['duree_sport_hebdo'] ?? 0);
$duree_seance = $duree_hebdo > 0 ? max(20, (int)round($duree_hebdo / 5)) : 45;

$sports_str = empty($sports) ? 'Aucun sport prévu aujourd\'hui' : implode(', ', $sports);

$condition = $meteo['condition'] ?? 'inconnue';
$temp      = $meteo['temp']      ?? '?';
$pluie     = $meteo['pluie']     ?? false;
$vent      = $meteo['vent']      ?? 0;

// Déterminer si météo défavorable (pour guider l'IA sans ambiguïté)
$meteo_defavorable = $pluie || (int)$vent > 40;
$meteo_conseil_txt = $meteo_defavorable
    ? "La météo est défavorable (pluie ou vent fort) → privilégie l'intérieur SI le sport le permet."
    : "La météo est favorable ({$temp}°C, {$condition}) → reste sur le sport prévu, mentionne la météo agréable dans le conseil.";

$prompt = "Tu es un coach sportif IA intégré dans SmartNutrition.

MÉTÉO ACTUELLE :
- Condition  : {$condition}
- Température: {$temp}°C
- Pluie      : " . ($pluie ? 'Oui' : 'Non') . "
- Vent       : {$vent} km/h

PROFIL SPORTIF :
- Sport pratiqué      : {$sport_profil}
- Volume hebdomadaire : {$duree_hebdo} min/semaine

SPORT PRÉVU AUJOURD'HUI (extrait du planning) : {$sports_str}

RÈGLES STRICTES :
1. \"duree\" = durée d'UNE séance = environ {$duree_seance} min (JAMAIS le total hebdomadaire).
2. \"lieu\" = déterminé par la NATURE DU SPORT, pas par la météo.
   - Musculation, fitness, yoga, natation → \"intérieur\"
   - Course, cyclisme, football, randonnée → \"extérieur\"
   - Si météo défavorable et sport extérieur → passe à \"intérieur\" + adapte le sport.
3. {$meteo_conseil_txt}
4. \"alerte\" = true UNIQUEMENT si météo dangereuse (pluie forte, vent > 40 km/h, orage). Sinon false.
5. Si météo favorable, le \"conseil\" doit être positif et mentionner la météo agréable.

Réponds UNIQUEMENT dans ce format JSON strict (sans markdown, sans texte avant/après) :
{
  \"adapte\": true ou false,
  \"raison\": \"Une phrase courte expliquant pourquoi\",
  \"sport_recommande\": \"Nom du sport recommandé\",
  \"duree\": \"ex: 45 min\",
  \"intensite\": \"légère | modérée | intense\",
  \"lieu\": \"intérieur | extérieur\",
  \"conseil\": \"Un conseil pratique court lié à la météo (max 20 mots)\",
  \"alerte\": true ou false
}";

$payload = json_encode([
    'model'       => GROQ_MODEL,
    'max_tokens'  => 400,
    'temperature' => 0.1,
    'messages'    => [
        ['role' => 'system', 'content' => 'Tu es un coach sportif IA. Tu réponds UNIQUEMENT en JSON valide, sans aucun texte avant ou après. Le champ "lieu" dépend de la nature du sport, pas de la météo. La durée = 1 seule séance.'],
        ['role' => 'user',   'content' => $prompt],
    ],
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($err || $code !== 200) {
    echo json_encode(['success' => false, 'error' => "Erreur API ($code) : $err"]);
    exit;
}

$data = json_decode($raw, true);
$text = trim($data['choices'][0]['message']['content'] ?? '');
$text = preg_replace('/^```json\s*/i', '', $text);
$text = preg_replace('/```$/', '', $text);

$reco = json_decode(trim($text), true);
if (!$reco) {
    echo json_encode(['success' => false, 'error' => 'Réponse IA invalide', 'raw' => $text]);
    exit;
}

echo json_encode([
    'success' => true,
    'meteo'   => $meteo,
    'reco'    => $reco,
]);