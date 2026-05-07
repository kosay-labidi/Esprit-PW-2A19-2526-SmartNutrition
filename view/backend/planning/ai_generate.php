<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../controller/Demandeplanning.controller.php');

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Clé Groq chargée depuis config.php
// ── Appel API Groq ────────────────────────────────────────────────────────
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

// ── Récupérer la demande + sport/sommeil ──────────────────────────────────
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

// ─────────────────────────────────────────────────────────────────────────
// DISPATCH
// ─────────────────────────────────────────────────────────────────────────
$action = trim($_GET['action'] ?? 'generer');
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}


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

    $system = "Tu es un expert nutritionniste et coach sportif professionnel. "
            . "Tu génères des plannings nutritionnels complets et personnalisés. "
            . "Réponds UNIQUEMENT en JSON valide, sans texte avant ou après, sans markdown, sans backticks. "
            . "Le JSON doit être directement parseable par json_decode().";

    $user = "Génère un planning nutritionnel complet pour $nbJours jours avec ces données :
- Objectif calorique : {$d['calories']} kcal/jour
- Budget : {$d['budget']} € / {$d['type_budget']}
- Durée totale : {$d['duree']} {$d['type_duree']}
- Activité sportive : " . ($d['activite_sportive'] ?? 'non renseignée') . "
- Sport par semaine : " . ($d['duree_sport_hebdo'] ?? 0) . " minutes
- Heure coucher : " . ($d['heure_coucher'] ?? '22:00') . "
- Heure réveil : " . ($d['heure_reveil'] ?? '07:00') . "
- Qualité sommeil : " . ($d['qualite_sommeil'] ?? 'normale') . "

Réponds avec ce JSON exact (remplace les valeurs) :
{
  \"jours\": [
    {
      \"jour\": 1,
      \"repas\": {
        \"petit_dejeuner\": \"description du petit-déjeuner adapté au budget et calories\",
        \"dejeuner\": \"description du déjeuner\",
        \"diner\": \"description du dîner\",
        \"collation\": \"collation optionnelle\"
      },
      \"sport\": {
        \"activite\": \"nom de l'activité\",
        \"duree_min\": 30,
        \"description\": \"détails de la séance\"
      },
      \"sommeil\": {
        \"heure_coucher\": \"22:00\",
        \"heure_reveil\": \"07:00\",
        \"conseil\": \"conseil pour améliorer le sommeil ce jour\"
      },
      \"calories_estimees\": 2000,
      \"conseil_jour\": \"conseil ou motivation du jour\"
    }
  ],
  \"bilan\": \"résumé global du planning en 2 phrases\"
}
Génère exactement $nbJours jours. Adapte chaque jour progressivement.";

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

    // Compter le nombre d'activités estimé (pour l'affichage)
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

    // ⚠️ ON NE SAUVEGARDE PAS EN DB ICI — l'admin doit valider via action=publier
    echo json_encode([
        'success'   => true,
        'message'   => '✨ Planning IA généré — en attente de validation',
        'nb_lignes' => $nbEstime,
        'nb_jours'  => count($planning['jours']),
        'bilan'     => $planning['bilan'] ?? '',
        'planning'  => $planning,
        'pending'   => true, // flag pour le frontend
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

        // Supprimer l'ancien planning
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
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'sport', ':description'=>"🏃 {$sport['activite']} — " . ($sport['duree_min']??0) . " min : " . ($sport['description']??'')]);
                $nbInserts++;
            }

            $sommeil = $j['sommeil'] ?? [];
            if (!empty($sommeil['conseil'])) {
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'sommeil', ':description'=>"🌙 Coucher : " . ($sommeil['heure_coucher']??'') . " | Réveil : " . ($sommeil['heure_reveil']??'') . " — {$sommeil['conseil']}"]);
                $nbInserts++;
            }

            if (!empty($j['conseil_jour'])) {
                $insert->execute([':id_demande'=>$id, ':date'=>$date, ':type_activite'=>'conseil', ':description'=>"💡 {$j['conseil_jour']}"]);
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
// ACTION : REJETER — L'admin rejette : on supprime de la DB si inséré
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