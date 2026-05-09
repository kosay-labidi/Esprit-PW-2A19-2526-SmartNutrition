<?php
/**
 * Smart Progress Coach - analyse IA/ML simple pour un participant.
 *
 * POST JSON/Form: id_participant
 * Retourne un score local + conseils Groq si GROQ_API_KEY est configure.
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

function coach_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function coach_clamp(int $value, int $min, int $max): int {
    return max($min, min($max, $value));
}

function coach_days_between(?string $start, ?string $end): int {
    if (!$start || !$end) return 1;
    $a = strtotime($start);
    $b = strtotime($end);
    if (!$a || !$b || $b <= $a) return 1;
    return max(1, (int)ceil(($b - $a) / 86400));
}

/**
 * Récupère les derniers messages du participant pour analyse de sentiment (Phase 1).
 */
function coach_fetch_recent_messages(PDO $db, int $participantId, int $limit = 10): array {
    try {
        $q = $db->prepare("
            SELECT body, created_at
            FROM chat_messages
            WHERE participant_id = :pid
              AND deleted_at IS NULL
              AND body IS NOT NULL
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $q->bindValue(':pid', $participantId, PDO::PARAM_INT);
        $q->bindValue(':limit', $limit, PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Erreur coach_fetch_recent_messages: ' . $e->getMessage());
        return [];
    }
}

function coach_local_analysis(array $row): array {
    $progress = coach_clamp((int)($row['objectif'] ?? 0), 0, 100);
    $engagement = (int)($row['engagement'] ?? 0);
    $notifications = (int)($row['notifications'] ?? 0);
    $totalDays = coach_days_between($row['date_debut'] ?? null, $row['date_fin'] ?? null);
    $elapsedDays = 0;
    if (!empty($row['date_debut'])) {
        $elapsedDays = max(0, (int)floor((time() - strtotime($row['date_debut'])) / 86400));
    }
    $expected = coach_clamp((int)round(($elapsedDays / $totalDays) * 100), 0, 100);
    $gap = $expected - $progress;

    $risk = 0;
    if ($gap > 0) $risk += (int)round($gap * 0.75);
    if ($progress < 25 && $elapsedDays > 2) $risk += 18;
    if ($engagement !== 1) $risk += 14;
    if ($notifications !== 1) $risk += 8;
    if ($totalDays - $elapsedDays <= 3 && $progress < 80) $risk += 18;
    $risk = coach_clamp($risk, 0, 100);

    if ($risk >= 66) {
        $status = 'Besoin d aide';
        $priority = 'elevee';
    } elseif ($risk >= 34) {
        $status = 'Bon rythme a renforcer';
        $priority = 'moyenne';
    } else {
        $status = 'En bonne voie';
        $priority = 'faible';
    }

    $nextAction = 'Faire une petite action concrete aujourd hui et mettre a jour la progression.';
    if ($progress < $expected) {
        $nextAction = 'Reduire l objectif du jour en une action simple pour rattraper progressivement le rythme.';
    } elseif ($progress >= 80) {
        $nextAction = 'Consolider les habitudes et preparer la fin du defi.';
    }

    return [
        'progress' => $progress,
        'expected_progress' => $expected,
        'gap' => $gap,
        'risk_score' => $risk,
        'status' => $status,
        'priority' => $priority,
        'days_total' => $totalDays,
        'days_elapsed' => coach_clamp($elapsedDays, 0, $totalDays),
        'days_left' => max(0, $totalDays - $elapsedDays),
        'next_action' => $nextAction,
    ];
}

function coach_rule_based_plan(array $row, array $analysis): array {
    $challenge = (string)($row['challenge_titre'] ?? 'ce defi');
    $action = trim((string)($row['action'] ?? ''));
    $baseAction = $action !== '' ? $action : 'realiser une action simple liee au defi';
    return [
        "Aujourd hui: {$baseAction}, meme en version courte.",
        "Demain: bloquer 20 minutes dans l agenda pour avancer sur {$challenge}.",
        "Jour 3: noter la progression et identifier ce qui bloque.",
        "Jour 4: demander un encouragement ou partager l avancee dans le chat du defi.",
        "Jour 5: refaire l action la plus facile pour garder le rythme.",
        "Jour 6: augmenter legerement l effort si la progression est stable.",
        "Jour 7: faire le bilan et ajuster l objectif de la semaine suivante."
    ];
}

function coach_groq(array $row, array $analysis, array $fallbackPlan, array $messages = []): array {
    $apiKey = getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? '');
    if (!$apiKey || !function_exists('curl_init')) {
        return [
            'provider' => 'local',
            'model' => 'rules',
            'coach_text' => "Le participant est classe: {$analysis['status']}. Action conseillee: {$analysis['next_action']}",
            'plan_7_days' => $fallbackPlan,
        ];
    }

    $model = getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile');
    $system = "Tu es un coach IA de progression intégré dans la plateforme GaiaLumen. 
 Tu reçois les données d'un participant à un défi. 
 
 Données participant : 
 - Nom : {$row['nom']} 
 - Défi : {$row['challenge_titre']} (#{$row['id_challenge']}) 
 - Progression : {$row['objectif']}% / {$row['valeur_cible']}% 
 - Engagement : {$row['engagement']} 
 - Motivation déclarée : {$row['motivation']} 
 - Action en cours : {$row['action']} 
 - Jours actifs : {$analysis['days_elapsed']} 
 - Score de risque : {$analysis['risk_score']}% 
 - Jours restants : {$analysis['days_left']} 
 
 Réponds UNIQUEMENT en JSON valide, sans aucun texte avant ou après, sans balises markdown. 
 Format exact : 
 { 
   \"statut\": \"En bonne voie\" | \"À surveiller\" | \"En danger\", 
   \"risque\": <entier 0-100>, 
   \"sentiment\": \"Positif\" | \"Neutre\" | \"Négatif\", 
   \"intention\": \"Prêt à commencer et à progresser\" | \"Besoin de motivation\" | \"Risque d'abandon\", 
   \"message\": \"<message personnalisé 3-4 phrases max, ton encourageant>\", 
   \"conseil_admin\": \"<conseil court pour l'admin, 1 phrase>\", 
   \"plan_7_jours\": [ 
     {\"jour\": \"J1\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J2\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J3\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J4\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J5\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J6\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\"}, 
     {\"jour\": \"J7\", \"action\": \"<action concrète>\", \"conseil\": \"<astuce pratique>\" } 
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
            'temperature' => 0.5,
            'max_tokens' => 1200,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Analyse ce participant et génère le JSON."],
            ],
            'response_format' => ['type' => 'json_object']
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 25,
    ]);

    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = is_string($resp) ? json_decode($resp, true) : null;
    $content = trim((string)($decoded['choices'][0]['message']['content'] ?? ''));
    $coach = json_decode($content, true);

    if ($code < 200 || $code >= 300 || !is_array($coach)) {
        return [
            'provider' => 'local',
            'model' => 'rules',
            'coach_text' => "Analyse locale: {$analysis['status']}. {$analysis['next_action']}",
            'plan_7_days' => $fallbackPlan,
            'admin_tip' => 'Groq indisponible: le plan local a ete utilise.',
        ];
    }

    return [
        'provider' => 'groq',
        'model' => $decoded['model'] ?? $model,
        'statut' => (string)($coach['statut'] ?? $analysis['status']),
        'risque' => (int)($coach['risque'] ?? $analysis['risk_score']),
        'message' => (string)($coach['message'] ?? "Le participant est {$analysis['status']}."),
        'sentiment' => (string)($coach['sentiment'] ?? 'Neutre'),
        'intention' => (string)($coach['intention'] ?? 'Inconnue'),
        'plan_7_jours' => is_array($coach['plan_7_jours'] ?? null) ? array_values($coach['plan_7_jours']) : $fallbackPlan,
        'conseil_admin' => (string)($coach['conseil_admin'] ?? 'Envoyer un encouragement personnalisé.'),
    ];
}

function coach_email_body(array $row, array $analysis, array $coach): string {
    $name = htmlspecialchars((string)($row['nom'] ?? 'Participant'), ENT_QUOTES, 'UTF-8');
    $challenge = htmlspecialchars((string)($row['challenge_titre'] ?? 'votre defi'), ENT_QUOTES, 'UTF-8');
    $icon = htmlspecialchars((string)($row['streak_icon'] ?? '🏆'), ENT_QUOTES, 'UTF-8');
    $coachText = nl2br(htmlspecialchars((string)($coach['message'] ?? $analysis['next_action'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $status = htmlspecialchars((string)($coach['statut'] ?? $analysis['status'] ?? ''), ENT_QUOTES, 'UTF-8');
    $progress = (int)($analysis['progress'] ?? 0);
    $daysLeft = (int)($analysis['days_left'] ?? 0);
    $plan = is_array($coach['plan_7_jours'] ?? null) ? array_slice($coach['plan_7_jours'], 0, 7) : [];
    $items = '';
    foreach ($plan as $i => $item) {
        $day = $i + 1;
        $action = is_array($item) ? ($item['action'] ?? '') : (string)$item;
        $conseil = is_array($item) ? ($item['conseil'] ?? '') : '';
        $safeAction = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
        $safeConseil = htmlspecialchars($conseil, ENT_QUOTES, 'UTF-8');
        $items .= "<li style='margin:8px 0;line-height:1.5;'><strong>Jour {$day}:</strong> {$safeAction}" . ($safeConseil ? " <br><small style='color:#a8b8a0;'>Astuce: {$safeConseil}</small>" : "") . "</li>";
    }

    return "
    <html><body style='margin:0;font-family:Arial,sans-serif;background:#0a1a10;color:#F2E8CF;padding:22px;'>
      <div style='max-width:640px;margin:auto;background:#0f2318;border:1px solid rgba(168,184,160,.28);border-radius:18px;overflow:hidden;'>
        <div style='padding:24px;background:#1F3D2B;border-bottom:1px solid rgba(168,184,160,.18);'>
          <h2 style='margin:0;color:#F2E8CF;font-size:22px;'>{$icon} Coach IA - {$challenge}</h2>
          <p style='margin:8px 0 0;color:#a8b8a0;'>Bonjour <strong style='color:#F2E8CF;'>{$name}</strong>, voici votre plan personnalise.</p>
        </div>
        <div style='padding:24px;'>
          <div style='display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;'>
            <span style='background:rgba(168,184,160,.1);border:1px solid rgba(168,184,160,.22);border-radius:999px;padding:8px 12px;color:#F2E8CF;'>Progression: {$progress}%</span>
            <span style='background:rgba(168,184,160,.1);border:1px solid rgba(168,184,160,.22);border-radius:999px;padding:8px 12px;color:#F2E8CF;'>{$daysLeft} jours restants</span>
            <span style='background:rgba(168,184,160,.1);border:1px solid rgba(168,184,160,.22);border-radius:999px;padding:8px 12px;color:#F2E8CF;'>{$status}</span>
          </div>
          <div style='background:rgba(255,255,255,.04);border:1px solid rgba(168,184,160,.16);border-radius:14px;padding:16px;margin-bottom:18px;color:#F2E8CF;line-height:1.65;'>
            {$coachText}
          </div>
          <h3 style='color:#F2E8CF;margin:0 0 10px;font-size:18px;'>Plan 7 jours</h3>
          <ol style='padding-left:22px;margin:0;color:#F2E8CF;'>{$items}</ol>
          <p style='margin:24px 0 0;color:#a8b8a0;font-size:13px;text-align:center;'>GaiaLumen - Coach IA de progression</p>
        </div>
      </div>
    </body></html>";
}

function coach_send_email(array $row, array $analysis, array $coach): array {
    $email = trim((string)($row['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sent' => false, 'error' => 'Email participant invalide'];
    }

    $fromEmail = getenv('MAIL_FROM') ?: ($_SERVER['MAIL_FROM'] ?? 'noreply@gaialumen.com');
    $fromName = getenv('MAIL_FROM_NAME') ?: ($_SERVER['MAIL_FROM_NAME'] ?? 'GaiaLumen');
    $challenge = (string)($row['challenge_titre'] ?? 'votre defi');
    $subject = 'Votre plan Coach IA - ' . $challenge;
    $headers = implode("\r\n", [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ]);

    $sent = @mail($email, $subject, coach_email_body($row, $analysis, $coach), $headers);
    return ['sent' => $sent, 'error' => $sent ? '' : 'Echec envoi mail'];
}

/**
 * Envoie une alerte au serveur WebSocket (Phase 3).
 */
function coach_ws_alert(int $challengeId, array $alert): void {
    try {
        $host = '127.0.0.1';
        $port = 8081;
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        if (!$fp) return;

        $payload = json_encode(array_merge(['type' => 'ai_alert', 'challenge_id' => $challengeId], $alert));
        
        // Ratchet attend du WebSocket, mais on peut envoyer un message simple 
        // si on avait un port de contrôle. Ici on simule l'envoi.
        // NOTE: En production, on utiliserait un client WebSocket PHP (ex: textalk/websocket).
        // Pour ce projet, on documente que l'alerte est prête à être envoyée.
        @fwrite($fp, $payload);
        @fclose($fp);
    } catch (Throwable $e) {
        error_log('Erreur coach_ws_alert: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    coach_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) $payload = $_POST;
$id = (int)($payload['id_participant'] ?? $payload['id'] ?? 0);
$sendEmail = !empty($payload['send_email']);
if ($id <= 0) {
    coach_json(['success' => false, 'error' => 'id_participant requis'], 400);
}

try {
    $db = Config::getConnexion();
    $q = $db->prepare("
        SELECT p.*, c.titre AS challenge_titre, c.type, c.objectif AS challenge_objectif,
               c.valeur_cible, c.date_debut, c.date_fin, c.statut, c.streak_icon
        FROM participant p
        LEFT JOIN challenge c ON c.id = p.id_challenge
        WHERE p.id = :id
        LIMIT 1
    ");
    $q->execute(['id' => $id]);
    $row = $q->fetch();
    if (!$row) {
        coach_json(['success' => false, 'error' => 'Participant introuvable'], 404);
    }

    $analysis = coach_local_analysis($row);
    $messages = coach_fetch_recent_messages($db, $id, 10);
    $fallbackPlan = coach_rule_based_plan($row, $analysis);
    $ai = coach_groq($row, $analysis, $fallbackPlan, $messages);
    $emailResult = null;
    if ($sendEmail) {
        $emailResult = coach_send_email($row, $analysis, $ai);
    }

    coach_json([
        'success' => true,
        'email' => $emailResult,
        'participant' => [
            'id' => (int)$row['id'],
            'nom' => $row['nom'],
            'email' => $row['email'],
            'challenge' => $row['challenge_titre'],
            'icon' => $row['streak_icon'],
        ],
        'analysis' => $analysis,
        'coach' => $ai,
    ]);
} catch (Throwable $e) {
    error_log('Erreur ai-progress-coach: ' . $e->getMessage());
    coach_json(['success' => false, 'error' => 'Erreur serveur coach IA'], 500);
}
