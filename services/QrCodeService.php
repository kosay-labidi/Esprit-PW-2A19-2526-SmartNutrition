<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config_services.php';
require_once __DIR__ . '/../controller/ParticipationController.php';

class QrCodeService
{
    public static function genererToken(int $id_participation): string
    {
        return hash('sha256', $id_participation . APP_SECRET_KEY);
    }

    public static function construireUrl(int $id_participation): string
    {
        $ctrl = new ParticipationController();
        $data = $ctrl->showParticipation($id_participation);

        return "NOM: "       . ($data['nom_complet']     ?? '') . "\n"
             . "EMAIL: "     . ($data['email']            ?? '') . "\n"
             . "TEL: "       . ($data['telephone']        ?? '') . "\n"
             . "EVENEMENT: " . ($data['evenement_titre']  ?? '') . "\n"
             . "STATUT: "    . ($data['statut']           ?? '');
    }

    public static function genererSvg(int $id_participation, int $size = 300): string
    {
        $data = self::construireUrl($id_participation);

        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            $qrCode = new \Endroid\QrCode\QrCode(
                data: $data,
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
                size: $size,
                margin: 10,
                roundBlockSizeMode: \Endroid\QrCode\RoundBlockSizeMode::Margin
            );

            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            return $result->getString();
        }

        return self::genererSvgFallback($id_participation, $size);
    }

    public static function genererBase64(int $id_participation, int $size = 300): string
    {
        return base64_encode(self::genererSvg($id_participation, $size));
    }

    public static function genererImageSrc(int $id_participation, int $size = 300): string
    {
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            return 'data:image/svg+xml;base64,' . self::genererBase64($id_participation, $size);
        }

        return self::genererApiUrl($id_participation, $size, 'png');
    }

    public static function genererDownloadUrl(int $id_participation, int $size = 400): string
    {
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            return 'qrcode.php?id=' . $id_participation . '&dl=1';
        }

        return self::genererApiUrl($id_participation, $size, 'svg');
    }

    public static function verifierToken(int $id_participation, string $token): bool
    {
        return hash_equals(self::genererToken($id_participation), $token);
    }

    private static function genererApiUrl(int $id_participation, int $size = 300, string $format = 'png'): string
    {
        $size = max(120, min(1000, $size));
        $format = $format === 'svg' ? 'svg' : 'png';

        return 'https://api.qrserver.com/v1/create-qr-code/?size='
            . $size . 'x' . $size
            . '&format=' . $format
            . '&data=' . rawurlencode(self::construireUrl($id_participation));
    }

    private static function genererSvgFallback(int $id_participation, int $size = 300): string
    {
        $apiUrl = htmlspecialchars(self::genererApiUrl($id_participation, $size, 'svg'), ENT_QUOTES, 'UTF-8');
        $safeId = htmlspecialchars((string)$id_participation, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <rect width="100%" height="100%" fill="#ffffff"/>
  <image href="{$apiUrl}" x="0" y="0" width="{$size}" height="{$size}" preserveAspectRatio="xMidYMid meet"/>
  <title>QR Code participant {$safeId}</title>
</svg>
SVG;
    }
}
