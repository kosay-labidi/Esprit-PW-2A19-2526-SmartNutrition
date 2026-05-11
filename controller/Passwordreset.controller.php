<?php
/**
 * PasswordResetController
 * Gestion complète de la réinitialisation de mot de passe.
 * Envoi via PHPMailer + SMTP Gmail (gaiaalumen@gmail.com).
 *
 * AVANT UTILISATION :
 *   1. Exécuter password_reset_migration.sql dans phpMyAdmin
 *   2. Installer PHPMailer : composer require phpmailer/phpmailer
 *   3. Remplacer GMAIL_APP_PASSWORD par votre mot de passe d'application Gmail
 *      → Compte Google > Sécurité > Validation 2 étapes > Mots de passe d'application
 */

require_once __DIR__ . '/../config.php';

// ─── CONFIGURATION GMAIL ──────────────────────────────────────
define('GMAIL_USER',       'gaiaalumen@gmail.com');
define('GMAIL_APP_PASS',   'prvb bmbm hlmz ghzp'); // ← Remplacer ici
define('APP_NAME',         'GaiaLumen');
define('TOKEN_EXPIRE_MIN', 60);                    // Durée du token en minutes
// ──────────────────────────────────────────────────────────────

class PasswordResetController
{
    /* ═══════════════════════════════════════════════
     *  1. TOKENS
     * ═══════════════════════════════════════════════ */

    public function createResetToken(string $email): ?string
    {
        $email = trim($email);
        if ($email === '') return null;
        try {
            $db = config::getConnexion();
            $db->prepare('UPDATE password_resets SET used = 1 WHERE email = :email AND used = 0')
               ->execute(['email' => $email]);
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + TOKEN_EXPIRE_MIN * 60);
            $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)')
               ->execute(['email' => $email, 'token' => $token, 'expires_at' => $expiresAt]);
            return $token;
        } catch (Exception $e) {
            error_log('[PasswordReset] createResetToken: ' . $e->getMessage());
            return null;
        }
    }

    public function validateToken(string $token): ?string
    {
        if ($token === '') return null;
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare('SELECT email FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW() LIMIT 1');
            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['email'] : null;
        } catch (Exception $e) {
            error_log('[PasswordReset] validateToken: ' . $e->getMessage());
            return null;
        }
    }

    public function getTokenStatus(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['status' => 'missing', 'email' => null];
        }

        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare('SELECT email, used, expires_at FROM password_resets WHERE token = :token LIMIT 1');
            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['status' => 'invalid', 'email' => null];
            }

            if ((int)($row['used'] ?? 0) === 1) {
                return ['status' => 'used', 'email' => $row['email'] ?? null];
            }

            $expiresAt = strtotime((string)($row['expires_at'] ?? ''));
            if (!$expiresAt || $expiresAt <= time()) {
                return ['status' => 'expired', 'email' => $row['email'] ?? null];
            }

            return ['status' => 'valid', 'email' => $row['email'] ?? null];
        } catch (Throwable $e) {
            error_log('[PasswordReset] getTokenStatus: ' . $e->getMessage());
            return ['status' => 'error', 'email' => null];
        }
    }

    public function consumeToken(string $token): bool
    {
        try {
            return config::getConnexion()
                ->prepare('UPDATE password_resets SET used = 1 WHERE token = :token')
                ->execute(['token' => $token]);
        } catch (Exception $e) {
            error_log('[PasswordReset] consumeToken: ' . $e->getMessage());
            return false;
        }
    }

    /* ═══════════════════════════════════════════════
     *  2. MISE À JOUR MOT DE PASSE
     * ═══════════════════════════════════════════════ */

    public function updatePassword(string $email, string $newMdp): bool
    {
        if ($email === '' || strlen($newMdp) < 8) return false;
        try {
            $hash = password_hash($newMdp, PASSWORD_DEFAULT);
            return config::getConnexion()
                ->prepare('UPDATE utilisateurs SET mdp = :mdp, date_mise_a_jour = NOW() WHERE email = :email')
                ->execute(['mdp' => $hash, 'email' => $email]);
        } catch (Exception $e) {
            error_log('[PasswordReset] updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    /* ═══════════════════════════════════════════════
     *  3. ENVOI EMAIL GMAIL (PHPMailer)
     * ═══════════════════════════════════════════════ */

    public function sendResetEmail(string $toEmail, string $token, string $toName = ''): bool
    {
        $resetLink = $this->buildResetLink($token);
        $subject   = '🔑 Réinitialisation de votre mot de passe — ' . APP_NAME;
        $name      = $toName ?: $toEmail;

        // ── PHPMailer SMTP Gmail ──────────────────────────────
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = GMAIL_USER;
                $mail->Password   = GMAIL_APP_PASS;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(GMAIL_USER, APP_NAME);
                $mail->addAddress($toEmail, $name);
                $mail->addReplyTo(GMAIL_USER, APP_NAME);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $this->buildEmailHtml($name, $resetLink);
                $mail->AltBody = $this->buildEmailText($name, $resetLink);
                $mail->send();
                error_log('[PasswordReset] Email envoyé via PHPMailer à ' . $toEmail);
                return true;
            } catch (PHPMailer\PHPMailer\Exception $e) {
                error_log('[PasswordReset] PHPMailer error: ' . $e->getMessage());
            }
        } else {
            error_log('[PasswordReset] PHPMailer introuvable → composer require phpmailer/phpmailer');
        }

        // ── Fallback pour développement : renvoyer le lien dans la réponse ────────────────
        error_log('[PasswordReset] 🔗 Lien test (dev) : ' . $resetLink);
        // Stocker le lien dans une variable de session pour l'afficher
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['reset_link_dev'] = $resetLink;
        return true; // toujours true en dev pour ne pas bloquer l'UX
    }

    /* ═══════════════════════════════════════════════
     *  4. HELPERS PRIVÉS
     * ═══════════════════════════════════════════════ */

    private function buildResetLink(string $token): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$proto://$host/Mainn/view/backend/users/Reset_password.php?token=" . urlencode($token);
    }

    private function buildEmailHtml(string $name, string $link): string
    {
        $expiry   = TOKEN_EXPIRE_MIN;
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/></head>
<body style="margin:0;padding:0;background:#0a1a10;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a1a10;padding:40px 20px;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#0f2318;border-radius:16px;overflow:hidden;max-width:580px;width:100%;">

  <!-- HEADER -->
  <tr>
    <td style="background:linear-gradient(135deg,#1F3D2B,#5B3E96);padding:36px 40px;text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:10px;">🌿</div>
      <h1 style="margin:0;color:#F2E8CF;font-size:1.6rem;font-family:Georgia,serif;">GaiaLumen</h1>
      <p style="margin:8px 0 0;color:rgba(242,232,207,.7);font-size:.9rem;">Réinitialisation de mot de passe</p>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="padding:36px 40px;">
      <p style="color:#a8b8a0;line-height:1.7;margin:0 0 16px;">
        Bonjour <strong style="color:#F2E8CF;">{$safeName}</strong>,
      </p>
      <p style="color:#a8b8a0;line-height:1.7;margin:0 0 24px;">
        Nous avons reçu une demande de réinitialisation du mot de passe de votre compte GaiaLumen.
        Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
      </p>
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" style="padding:8px 0 28px;">
            <a href="{$safeLink}"
               style="display:inline-block;padding:16px 40px;
                      background:linear-gradient(135deg,#5B3E96,#3A86C4);
                      color:#fff;text-decoration:none;border-radius:10px;
                      font-weight:700;font-size:1rem;">
              🔑 Réinitialiser mon mot de passe
            </a>
          </td>
        </tr>
      </table>
      <p style="color:#6a7e6a;font-size:.82rem;line-height:1.6;margin:0 0 20px;">
        Si le bouton ne fonctionne pas, copiez ce lien :<br/>
        <a href="{$safeLink}" style="color:#3A86C4;word-break:break-all;">{$safeLink}</a>
      </p>
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="background:rgba(58,134,196,.1);border-left:4px solid #3A86C4;padding:16px;border-radius:6px;">
            <p style="margin:0;color:#a8b8a0;font-size:.85rem;line-height:1.7;">
              ⏰ Ce lien expire dans <strong style="color:#F2E8CF;">{$expiry} minutes</strong>.<br/>
              🔒 Il ne peut être utilisé <strong style="color:#F2E8CF;">qu'une seule fois</strong>.<br/>
              ❌ Si vous n'avez pas fait cette demande, ignorez cet email.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#050e08;padding:20px 40px;text-align:center;">
      <p style="margin:0;color:rgba(168,184,160,.5);font-size:.78rem;">
        © 2025 GaiaLumen &mdash; Email automatique, ne pas répondre.<br/>
        <a href="mailto:gaiaalumen@gmail.com" style="color:rgba(58,134,196,.6);">gaiaalumen@gmail.com</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function buildEmailText(string $name, string $link): string
    {
        $expiry = TOKEN_EXPIRE_MIN;
        return "Bonjour {$name},\n\nRéinitialisez votre mot de passe GaiaLumen ici :\n{$link}\n\nCe lien expire dans {$expiry} minutes (usage unique).\n\nSi vous n'avez pas fait cette demande, ignorez cet email.\n\n— GaiaLumen (gaiaalumen@gmail.com)";
    }
    /**
 * Vérifie si le nouveau mot de passe est différent de l'ancien
 */
public function isSameAsOldPassword(string $email, string $newMdp): bool
{
    try {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT mdp FROM utilisateurs WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) return false;
        
        // Vérifier si le nouveau mot de passe correspond à l'ancien
        return password_verify($newMdp, $user['mdp']);
    } catch (Exception $e) {
        error_log('[PasswordReset] isSameAsOldPassword: ' . $e->getMessage());
        return false;
    }
}
}
