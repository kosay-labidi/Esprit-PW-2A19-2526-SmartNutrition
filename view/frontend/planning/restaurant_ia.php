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

$body        = json_decode(file_get_contents('php://input'), true);
$id_demande  = (int)($body['id_demande'] ?? 0);
$restaurants = $body['restaurants'] ?? [];

if ($id_demande <= 0 || empty($restaurants)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

// ── Profil utilisateur ────────────────────────────────────────────────────
function getProfil(int $id): array {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("
            SELECT d.calories, d.budget, d.type_budget, d.duree, d.type_duree,
                   ss.activite_sportive
            FROM demandeplanning d
            LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
            WHERE d.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) { return []; }
}

$profil  = getProfil($id_demande);
$cal     = (int)($profil['calories'] ?? 2000);
$budget  = floatval($profil['budget'] ?? 20);
$type_budget = $profil['type_budget'] ?? 'jour';
$sport   = $profil['activite_sportive'] ?? 'non renseigné';

if ($cal < 1600)     $objectif = 'perte de poids';
elseif ($cal > 2800) $objectif = 'prise de masse';
else                 $objectif = 'maintien du poids';

// Budget estimé par repas (si budget/jour → diviser par 3 repas)
$budget_repas = ($type_budget === 'jour') ? round($budget / 3, 1) : round($budget / 90, 1);

// ── Construire liste restaurants ──────────────────────────────────────────
$restoList = '';
foreach ($restaurants as $i => $r) {
    $name    = $r['name']    ?? 'Inconnu';
    $cuisine = $r['cuisine'] ?? 'non précisée';
    $type    = $r['type']    ?? 'restaurant';
    $dist    = (int)($r['dist'] ?? 0);
    $restoList .= ($i+1).". \"{$name}\" (cuisine: {$cuisine}, type: {$type}, distance: {$dist}m)\n";
}

// ── Prompt IA avec grille de notation EXPLICITE ───────────────────────────
$prompt = <<<PROMPT
Tu es un coach nutritionnel IA dans SmartNutrition. Analyse ces restaurants selon le profil de l'utilisateur.

PROFIL UTILISATEUR :
- Objectif         : {$objectif}
- Calories cibles  : {$cal} kcal/jour
- Budget par repas : ~{$budget_repas} TND
- Sport            : {$sport}

RESTAURANTS À ANALYSER :
{$restoList}

GRILLE DE NOTATION STRICTE (tu DOIS respecter cette logique) :
• note_ia = 5.0 → Parfaitement compatible : cuisine légère ET healthy ET peu chère (ex: restaurant tunisien traditionnel, salade, poisson grillé)
• note_ia = 4.0 → Très compatible : cuisine saine avec quelques options grasses (ex: café avec salades, méditerranéen)
• note_ia = 3.0 → Partiellement compatible : possible de bien manger MAIS nécessite de bien choisir (ex: restaurant généraliste, chinois)
• note_ia = 2.0 → Peu compatible : majoritairement gras/calorique (ex: pizza, burger) — difficile pour {$objectif}
• note_ia = 1.0 → Incompatible : fast-food ultra-transformé, friture systématique — déconseillé pour {$objectif}

RÈGLES ABSOLUES SUR LA NOTE :
- Fast-food (burger, pizza industrielle, frite) → note MAX 2.0 pour perte de poids, 2.5 pour maintien
- Café avec restauration légère → note MIN 3.0
- Restaurant tunisien/méditerranéen traditionnel → note MIN 4.0
- La note doit DIRECTEMENT refléter la compatibilité : oui=4-5, partiel=2.5-3.5, non=1-2

MISSION :
1. Analyser chaque restaurant
2. Identifier le MEILLEUR restaurant (top_pick) selon calories + budget + distance
3. Donner 3 conseils généraux adaptés à l'objectif

Réponds UNIQUEMENT en JSON strict (sans markdown) :
{
  "analysis": [
    {
      "name": "Nom exact du restaurant",
      "compatibilite": "oui | partiel | non",
      "note_ia": 4.0,
      "raison_note": "Phrase courte expliquant pourquoi cette note (max 10 mots)",
      "conseil_ia": "Conseil SPÉCIFIQUE à ce restaurant max 12 mots",
      "tags_alimentation": ["tag1", "tag2", "tag3"],
      "estimation_prix": "Faible | Moyen | Élevé",
      "calories_estimees": "~600 kcal par repas"
    }
  ],
  "top_pick": {
    "name": "Nom du meilleur restaurant",
    "raison": "Explication complète (2-3 phrases) pourquoi ce restaurant est le meilleur choix selon calories {$cal} kcal et budget {$budget_repas} TND/repas et objectif {$objectif}",
    "commande_ideale": "Ce que commander précisément dans ce restaurant pour respecter le régime",
    "calories_repas": "~XXX kcal",
    "prix_estime": "~XX TND"
  },
  "conseils": [
    { "titre": "Titre", "texte": "Conseil concret pour {$objectif} au restaurant (max 18 mots)" },
    { "titre": "Titre", "texte": "Conseil concret pour {$objectif} au restaurant (max 18 mots)" },
    { "titre": "Titre", "texte": "Conseil concret pour {$objectif} au restaurant (max 18 mots)" }
  ]
}
PROMPT;

$payload = json_encode([
    'model'       => GROQ_MODEL,
    'max_tokens'  => 1800,
    'temperature' => 0.1,
    'messages'    => [
        ['role' => 'system', 'content' => 'Tu es un coach nutritionnel IA expert. Réponds UNIQUEMENT en JSON valide sans markdown. Les notes doivent être COHÉRENTES : fast-food = 1-2, restaurant sain = 4-5. Respecte la grille de notation fournie.'],
        ['role' => 'user',   'content' => $prompt],
    ],
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 25,
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
$text = preg_replace('/```$/',         '', trim($text));

$result = json_decode(trim($text), true);
if (!$result || !isset($result['analysis'])) {
    echo json_encode(['success' => false, 'error' => 'Réponse IA invalide', 'raw' => $text]);
    exit;
}

// ── Correction PHP : forcer cohérence note ↔ compatibilite ───────────────
foreach ($result['analysis'] as &$item) {
    $compat = $item['compatibilite'] ?? 'partiel';
    $note   = floatval($item['note_ia'] ?? 2.5);

    // Corriger si l'IA donne une note incohérente avec sa propre compatibilité
    if ($compat === 'oui'    && $note < 3.5) $item['note_ia'] = 4.0;
    if ($compat === 'non'    && $note > 2.5) $item['note_ia'] = 2.0;
    if ($compat === 'partiel' && $note > 4.0) $item['note_ia'] = 3.5;
    if ($compat === 'partiel' && $note < 2.0) $item['note_ia'] = 2.5;

    // Arrondir au 0.5
    $item['note_ia'] = round(floatval($item['note_ia']) * 2) / 2;
}
unset($item);

echo json_encode([
    'success'       => true,
    'analysis'      => $result['analysis'] ?? [],
    'top_pick'      => $result['top_pick']  ?? null,
    'conseils'      => $result['conseils']  ?? [],
    'objectif'      => $objectif,
    'budget_repas'  => $budget_repas,
    'calories'      => $cal,
]);