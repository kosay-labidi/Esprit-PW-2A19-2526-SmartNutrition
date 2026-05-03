<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config_services.php';
require_once __DIR__ . '/../controller/ParticipationController.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

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

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getString();
    }

    public static function genererBase64(int $id_participation, int $size = 300): string
    {
        return base64_encode(self::genererSvg($id_participation, $size));
    }

    public static function verifierToken(int $id_participation, string $token): bool
    {
        return hash_equals(self::genererToken($id_participation), $token);
    }
}