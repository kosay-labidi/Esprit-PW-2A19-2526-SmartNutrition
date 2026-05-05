<?php
// controller/NotificationController.php
// Role: Send real email notifications triggered by the Python AI composer.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- 1. Load environment variables (if using vlucas/phpdotenv) ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// --- 2. Security check using environment variable ---
$headers = getallheaders();
$key = $headers['X-API-Key'] ?? '';
$expectedKey = getenv('API_KEY') ?: 'sunpicnic'; // Fallback for local dev

if ($key !== $expectedKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- 3. Parse incoming request ---
$input = json_decode(file_get_contents('php://input'), true);
$channel = $input['channel'] ?? '';
$to = $input['to'] ?? '';
$subject = $input['subject'] ?? '';
$body = $input['body'] ?? '';

if ($channel !== 'email' || empty($to)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing channel or recipient']);
    exit;
}

// --- 4. Send email using Brevo SMTP (credentials from env) ---
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = getenv('BREVO_SMTP_HOST') ?: 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('BREVO_SMTP_USER');
    $mail->Password = getenv('BREVO_SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)(getenv('BREVO_SMTP_PORT') ?: 587);

    // Sender email – must be verified in Brevo
    $senderEmail = getenv('SENDER_EMAIL');
    $mail->setFrom($senderEmail, 'Health Assistant');
    $mail->addAddress($to);
    $mail->addReplyTo(getenv('REPLY_TO_EMAIL') ?: $senderEmail, 'Health Assistant');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br($body);
    $mail->AltBody = strip_tags($body);

    $mail->send();
    echo json_encode(['sent' => true]);
} catch (Exception $e) {
    error_log("Brevo email failed: " . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['sent' => false, 'error' => $mail->ErrorInfo]);
}
?>