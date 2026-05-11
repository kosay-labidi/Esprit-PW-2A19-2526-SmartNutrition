<?php
// =============================================================
//  services/EmailService.php
//  Envoi email HTML via PHPMailer + Gmail SMTP
//  Requiert : composer require phpmailer/phpmailer
// =============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config_services.php';

class EmailService
{
    private static function statutConfig(string $statut): array
    {
        return match ($statut) {
            'confirmée'  => ['couleur' => '#2ecc71', 'bg' => '#eafaf1', 'emoji' => '✅', 'libelle' => 'Confirmée'],
            'annulée'    => ['couleur' => '#e74c3c', 'bg' => '#fdedec', 'emoji' => '❌', 'libelle' => 'Annulée'],
            default      => ['couleur' => '#f39c12', 'bg' => '#fef9e7', 'emoji' => '⏳', 'libelle' => 'En attente'],
        };
    }

    private static function buildTemplate(string $nom, string $evenement, string $statut): string
    {
        $cfg = self::statutConfig($statut);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0"
           style="background:#ffffff;border-radius:16px;overflow:hidden;
                  box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:600px;width:100%;">

      <!-- EN-TÊTE VERT -->
      <tr>
        <td style="background:#1F3D2B;padding:36px 40px;text-align:center;">
          <h1 style="margin:0;color:#F2E8CF;font-size:28px;font-weight:700;font-family:Georgia,serif;">
            🌿 GaiaLumen
          </h1>
          <p style="margin:8px 0 0;color:#a8c8a0;font-size:12px;letter-spacing:2px;text-transform:uppercase;">
            Héritage de Gaia
          </p>
        </td>
      </tr>

      <!-- CORPS -->
      <tr>
        <td style="padding:40px 40px 30px;">
          <p style="margin:0 0 16px;color:#2c3e50;font-size:16px;line-height:1.6;">
            Bonjour <strong>{$nom}</strong>,
          </p>
          <p style="margin:0 0 28px;color:#555;font-size:15px;line-height:1.7;">
            Le statut de votre participation à l'événement
            <strong style="color:#1F3D2B;">{$evenement}</strong>
            vient d'être mis à jour.
          </p>

          <!-- BLOC STATUT COLORÉ -->
          <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
            <tr>
              <td style="background:{$cfg['bg']};border:2px solid {$cfg['couleur']};
                          border-radius:12px;padding:24px;text-align:center;">
                <p style="margin:0 0 8px;color:#888;font-size:12px;
                           text-transform:uppercase;letter-spacing:1px;">Nouveau statut</p>
                <p style="margin:0;color:{$cfg['couleur']};font-size:28px;font-weight:700;">
                  {$cfg['emoji']} {$cfg['libelle']}
                </p>
              </td>
            </tr>
          </table>

          <!-- INFOS PARTICIPANT + ÉVÉNEMENT -->
          <table width="100%" cellpadding="0" cellspacing="0"
                 style="background:#f8f9fa;border-radius:10px;overflow:hidden;margin-bottom:28px;">
            <tr>
              <td style="padding:16px 20px;border-bottom:1px solid #eee;">
                <span style="color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px;">
                  Participant
                </span><br>
                <strong style="color:#2c3e50;font-size:15px;">{$nom}</strong>
              </td>
            </tr>
            <tr>
              <td style="padding:16px 20px;">
                <span style="color:#888;font-size:11px;text-transform:uppercase;letter-spacing:1px;">
                  Événement
                </span><br>
                <strong style="color:#2c3e50;font-size:15px;">{$evenement}</strong>
              </td>
            </tr>
          </table>

          <p style="margin:0;color:#666;font-size:14px;line-height:1.7;">
            Pour toute question, contactez-nous directement.<br>
            Merci de votre confiance et à bientôt !
          </p>
        </td>
      </tr>

      <!-- PIED DE PAGE -->
      <tr>
        <td style="background:#1F3D2B;padding:24px 40px;text-align:center;">
          <p style="margin:0;color:#a8c8a0;font-size:12px;line-height:1.6;">
            © 2026 GaiaLumen – Héritage de Gaia<br>
            Email automatique, merci de ne pas y répondre.
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

    public static function envoyerNotificationStatut(
        string $email,
        string $nomComplet,
        string $evenement,
        string $statut
    ): bool {
        if (empty(trim($email))) {
            error_log('[EMAIL] Adresse manquante.');
            return false;
        }

        $cfg  = self::statutConfig($statut);
        $mail = new PHPMailer(true);

        try {
            // ── Configuration SMTP Gmail ───────────────────────────
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = GMAIL_USER;
            $mail->Password   = GMAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // ── Expéditeur et destinataire ─────────────────────────
            $mail->setFrom(GMAIL_USER, 'GaiaLumen');
            $mail->addAddress($email, $nomComplet);

            // ── Contenu HTML ───────────────────────────────────────
            $mail->isHTML(true);
            $mail->Subject = "{$cfg['emoji']} Participation à « {$evenement} » : {$cfg['libelle']}";
            $mail->Body    = self::buildTemplate($nomComplet, $evenement, $statut);
            $mail->AltBody = "Bonjour {$nomComplet}, le statut de votre participation à \"{$evenement}\" est désormais : {$cfg['libelle']}.";

            $mail->send();
            error_log("[EMAIL OK] → {$email} | statut: {$statut}");
            return true;

        } catch (Exception $e) {
            error_log("[EMAIL ERREUR] → {$email} : " . $mail->ErrorInfo);
            return false;
        }
    }
}
