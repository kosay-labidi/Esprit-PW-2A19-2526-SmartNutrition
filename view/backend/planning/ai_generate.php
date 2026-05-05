<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// ── Clé API Groq ───────────────────────────────────────────────────────────
define('GROQ_API_KEY', 'apikey');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');

// ══════════════════════════════════════════════════════════════════════════
// PROMPT COACH IA — construit depuis les données réelles de la demande
// ══════════════════════════════════════════════════════════════════════════
function buildCoachSystemPrompt(array $d): string {
    $cal = (int)($d['calories'] ?? 2000);
    if ($cal < 1600)      $objectif = 'perte de poids (déficit calorique)';
    elseif ($cal > 2800)  $objectif = 'prise de masse (surplus calorique)';
    else                  $objectif = 'maintien du poids';

    $budget      = ($d['budget'] ?? '?') . ' / ' . ($d['type_budget'] ?? 'jour');
    $sport       = $d['activite_sportive']  ?? 'non renseignée';
    $durSport    = ($d['duree_sport_hebdo'] ?? 0) . ' min/semaine';
    $coucher     = $d['heure_coucher']   ?? '22:00';
    $reveil      = $d['heure_reveil']    ?? '07:00';
    $qualSommeil = $d['qualite_sommeil'] ?? 'normale';

    // Calcul durée de sommeil
    $h1 = (int)explode(':', $coucher)[0];
    $m1 = (int)(explode(':', $coucher)[1] ?? 0);
    $h2 = (int)explode(':', $reveil)[0];
    $m2 = (int)(explode(':', $reveil)[1] ?? 0);
    $minSommeil = ($h2 * 60 + $m2) - ($h1 * 60 + $m1);
    if ($minSommeil < 0) $minSommeil += 1440;
    $hSommeil = round($minSommeil / 60, 1);

    return "Tu es un Coach Nutritionnel IA professionnel intégré dans SmartNutrition.
Tu génères des plannings nutritionnels COMPLETS et PERSONNALISÉS.

═══════════ PROFIL UTILISATEUR ═══════════
• Objectif déduit    : {$objectif}
• Calories cibles    : {$cal} kcal/jour
• Budget alimentaire : {$budget}
• Sport pratiqué     : {$sport}
• Volume sportif     : {$durSport}
• Coucher / Réveil   : {$coucher} → {$reveil} ({$hSommeil}h de sommeil)
• Qualité du sommeil : {$qualSommeil}
══════════════════════════════════════════

RÈGLES ABSOLUES :
1. Adapter chaque repas aux calories ({$cal} kcal/jour) et au budget ({$budget})
2. Les séances de sport doivent correspondre à : {$sport}
3. Les conseils sommeil doivent tenir compte de {$hSommeil}h et qualité '{$qualSommeil}'
4. Varier les repas et les activités d'un jour à l'autre
5. Réponds UNIQUEMENT en JSON valide, sans texte avant ou après, sans markdown, sans backticks.
   Le JSON doit être directement parseable par json_decode().";
}

// ── Appel API Groq ─────────────────────────────────────────────────────────
function callGroq(string $systemPrompt, string $userMessage, int $maxTokens = 4000): array {
    $payload = json_encode([
        'model'       => GROQ_MODEL,
        'max_tokens'  => $maxTokens,
        'temperature' => 0.7,
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

// ── Récupérer la demande + sport/sommeil ───────────────────────────────────
function getDemande(int $id): ?array {
    try {
        $db   = config::getConnexion();
        $stmt = $db->prepare("
            SELECT d.*,
                   ss.activite_sportive,
                   ss.duree_sport_hebdo,
                   ss.heure_coucher,
                   ss.heure_reveil,
                   ss.qualite_sommeil
            FROM demandeplanning d
            LEFT JOIN sportsommeil ss ON ss.id_demande = d.id
            WHERE d.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) { return null; }
}

// ── DISPATCH ───────────────────────────────────────────────────────────────
$action = trim($_GET['action'] ?? 'generer');
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION : GENERER — Génère le planning IA (aperçu, sans sauvegarder en DB)
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'generer') {

    $d = getDemande($id);
    if (!$d) {
        echo json_encode(['success' => false, 'error' => 'Demande introuvable']);
        exit;
    }

    if ($d['statut'] !== 'approuve') {
        echo json_encode(['success' => false, 'error' => 'La demande doit être approuvée avant de générer un planning.']);
        exit;
    }

    $nbJours = (int)$d['duree'];
    if ($d['type_duree'] === 'semaines') $nbJours *= 7;
    if ($d['type_duree'] === 'mois')     $nbJours *= 30;
    $nbJours = min(max($nbJours, 1), 14);

    // Déduire l'objectif pour le message utilisateur
    $cal = (int)($d['calories'] ?? 2000);
    if ($cal < 1600)      $objectif = 'perte de poids';
    elseif ($cal > 2800)  $objectif = 'prise de masse';
    else                  $objectif = 'maintien du poids';

    $system = buildCoachSystemPrompt($d);

    $user = "Génère un planning nutritionnel complet pour {$nbJours} jours.
Objectif : {$objectif} — {$cal} kcal/jour — Budget : {$d['budget']} / {$d['type_budget']}
Sport : " . ($d['activite_sportive'] ?? 'non renseigné') . " | " . ($d['duree_sport_hebdo'] ?? 0) . " min/semaine
Sommeil : " . ($d['heure_coucher'] ?? '22:00') . " → " . ($d['heure_reveil'] ?? '07:00') . " | Qualité : " . ($d['qualite_sommeil'] ?? 'normale') . "

Réponds avec ce JSON exact :
{
  \"jours\": [
    {
      \"jour\": 1,
      \"repas\": {
        \"petit_dejeuner\": \"description adaptée aux calories et au budget\",
        \"dejeuner\": \"description déjeuner équilibré\",
        \"diner\": \"description dîner léger\",
        \"collation\": \"snack protéiné optionnel\"
      },
      \"sport\": {
        \"activite\": \"nom précis de l'activité\",
        \"duree_min\": 45,
        \"description\": \"détails de la séance adaptée au profil\"
      },
      \"sommeil\": {
        \"heure_coucher\": \"22:00\",
        \"heure_reveil\": \"07:00\",
        \"conseil\": \"conseil personnalisé pour améliorer la qualité du sommeil\"
      },
      \"calories_estimees\": {$cal},
      \"conseil_jour\": \"motivation ou astuce nutritionnelle du jour\"
    }
  ],
  \"bilan\": \"résumé global du planning en 2 phrases avec les points clés\"
}
Génère exactement {$nbJours} jours. Varie les repas et les activités chaque jour.";

    $res = callGroq($system, $user, 4000);
    if (!$res['ok']) {
        echo json_encode(['success' => false, 'error' => $res['error']]);
        exit;
    }

    $text = trim($res['text']);
    $text = preg_replace('/^```json\s*/i', '', $text);
    $text = preg_replace('/^```\s*/i', '', $text);
    $text = preg_replace('/```\s*$/i', '', $text);
    $text = trim($text);

    $planning = json_decode($text, true);
    if (!$planning || empty($planning['jours'])) {
        echo json_encode(['success' => false, 'error' => 'Réponse IA invalide, réessayez.', 'raw' => substr($text, 0, 300)]);
        exit;
    }

    $nbEstime = 0;
    foreach ($planning['jours'] as $j) {
        if (!empty($j['repas']['petit_dejeuner'])) $nbEstime++;
        if (!empty($j['repas']['dejeuner']))       $nbEstime++;
        if (!empty($j['repas']['diner']))          $nbEstime++;
        if (!empty($j['repas']['collation']))      $nbEstime++;
        if (!empty($j['sport']['activite']))       $nbEstime++;
        if (!empty($j['sommeil']['conseil']))      $nbEstime++;
        if (!empty($j['conseil_jour']))            $nbEstime++;
    }

    echo json_encode([
        'success'   => true,
        'message'   => '✨ Planning IA généré — en attente de validation',
        'nb_lignes' => $nbEstime,
        'nb_jours'  => count($planning['jours']),
        'bilan'     => $planning['bilan'] ?? '',
        'planning'  => $planning,
        'pending'   => true,
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION : PUBLIER — L'admin valide et sauvegarde le planning en DB
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'publier') {

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $planning = $body['planning'] ?? null;

    if (!$planning || empty($planning['jours'])) {
        echo json_encode(['success' => false, 'error' => 'Données planning manquantes.']);
        exit;
    }

    try {
        $db = config::getConnexion();
        $db->prepare("DELETE FROM planning WHERE id_demande = ?")->execute([$id]);

        $insert = $db->prepare("
            INSERT INTO planning (id_demande, date, type_activite, description)
            VALUES (:id_demande, :date, :type_activite, :description)
        ");

        $baseDate  = new DateTime();
        $nbInserts = 0;

        foreach ($planning['jours'] as $j) {
            $jourNum = (int)($j['jour'] ?? 1);
            $date    = (clone $baseDate)->modify('+' . ($jourNum - 1) . ' days')->format('Y-m-d');

            $repas = $j['repas'] ?? [];
            $repasMap = [
                'petit_dejeuner' => '🌅 Petit-déjeuner',
                'dejeuner'       => '🍽️ Déjeuner',
                'diner'          => '🌙 Dîner',
                'collation'      => '🍎 Collation',
            ];
            foreach ($repasMap as $key => $label) {
                if (!empty($repas[$key])) {
                    $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'repas', ':description'=>"$label : {$repas[$key]}"]);
                    $nbInserts++;
                }
            }

            $sport = $j['sport'] ?? [];
            if (!empty($sport['activite'])) {
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'sport',
                    ':description'=>"🏃 {$sport['activite']} — " . ($sport['duree_min']??0) . " min : " . ($sport['description']??'')]);
                $nbInserts++;
            }

            $sommeil = $j['sommeil'] ?? [];
            if (!empty($sommeil['conseil'])) {
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'sommeil',
                    ':description'=>"🌙 Coucher : " . ($sommeil['heure_coucher']??'') . " | Réveil : " . ($sommeil['heure_reveil']??'') . " — {$sommeil['conseil']}"]);
                $nbInserts++;
            }

            if (!empty($j['conseil_jour'])) {
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'conseil',
                    ':description'=>"💡 {$j['conseil_jour']}"]);
                $nbInserts++;
            }
        }

        echo json_encode([
            'success'   => true,
            'message'   => "✅ Planning publié : $nbInserts activités sur " . count($planning['jours']) . " jours",
            'nb_lignes' => $nbInserts,
            'nb_jours'  => count($planning['jours']),
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
    }
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// ACTION : REJETER
// ══════════════════════════════════════════════════════════════════════════
if ($action === 'rejeter') {
    try {
        $db = config::getConnexion();
        $db->prepare("DELETE FROM planning WHERE id_demande = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Planning supprimé.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => "Action inconnue : $action"]);
