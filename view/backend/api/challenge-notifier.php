<?php
/**
 * Send notification emails to challenge participants.
 * This endpoint mirrors the JSON API style used by ai-challenge-generator.php.
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

function challenge_notifier_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function challenge_notifier_limit(string $value, int $limit): string {
    $value = trim($value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function challenge_notifier_email_body(string $challengeTitle, string $participantName, string $message): string {
    $title = htmlspecialchars($challengeTitle, ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8');
    $htmlMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    return "
    <html><body style='font-family:Arial,sans-serif;background:#0f0f1a;color:#e2e8f0;padding:20px;'>
      <div style='max-width:600px;margin:auto;background:#1e1e2e;border-radius:16px;border:1px solid #6366f1;overflow:hidden;'>
        <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:24px;text-align:center;'>
          <h2 style='margin:0;color:#fff;font-size:1.5rem;'>🏆 {$title}</h2>
        </div>
        <div style='padding:24px;'>
          <p style='color:#94a3b8;margin-bottom:16px;'>Bonjour <strong style='color:#e2e8f0;'>{$name}</strong>,</p>
          <div style='background:#2d2d44;border-radius:10px;padding:16px;border-left:4px solid #6366f1;margin-bottom:20px;'>
            {$htmlMessage}
          </div>
          <hr style='border:none;border-top:1px solid #3d3d5c;margin:20px 0;'>
          <p style='text-align:center;color:#6366f1;font-size:0.8rem;margin:0;'>GaiaLumen - Plateforme de Defis Collaboratifs</p>
        </div>
      </div>
    </body></html>";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    challenge_notifier_json(['success' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        challenge_notifier_json(['success' => false, 'error' => 'JSON invalide'], 400);
    }

    $challengeId = (int)($payload['id_challenge'] ?? 0);
    $subject = challenge_notifier_limit((string)($payload['sujet'] ?? ''), 160);
    $message = challenge_notifier_limit((string)($payload['message'] ?? ''), 2000);

    if ($challengeId <= 0 || $subject === '' || $message === '') {
        challenge_notifier_json(['success' => false, 'error' => 'Données manquantes'], 400);
    }

    $db = Config::getConnexion();
    $query = $db->prepare("
        SELECT p.nom, p.email, c.titre
        FROM participant p
        JOIN challenge c ON c.id = p.id_challenge
        WHERE p.id_challenge = :id
          AND p.notifications = 1
          AND p.email IS NOT NULL
          AND p.email <> ''
    ");
    $query->execute(['id' => $challengeId]);
    $participants = $query->fetchAll();

    $sent = 0;
    $failed = 0;
    $invalid = 0;

    $fromEmail = getenv('MAIL_FROM') ?: ($_SERVER['MAIL_FROM'] ?? 'noreply@gaialumen.com');
    $fromName = getenv('MAIL_FROM_NAME') ?: ($_SERVER['MAIL_FROM_NAME'] ?? 'GaiaLumen');
    $headers = implode("\r\n", [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ]);

    foreach ($participants as $participant) {
        $email = trim((string)($participant['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid++;
            continue;
        }

        $body = challenge_notifier_email_body(
            (string)($participant['titre'] ?? 'Défi GaiaLumen'),
            (string)($participant['nom'] ?? ''),
            $message
        );

        if (@mail($email, $subject, $body, $headers)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    challenge_notifier_json([
        'success' => true,
        'sent' => $sent,
        'failed' => $failed,
        'invalid' => $invalid,
        'total' => count($participants),
    ]);
} catch (Throwable $e) {
    error_log('Erreur challenge-notifier: ' . $e->getMessage());
    challenge_notifier_json([
        'success' => false,
        'error' => 'Erreur serveur',
        'detail' => $e->getMessage(),
    ], 500);
}
