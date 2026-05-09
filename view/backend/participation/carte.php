<?php
// =============================================================
//  view/backoffice/participation/carte.php
//  Fiche participant affichée lors du scan du QR Code
//  Accessible via le lien encodé dans le QR
// =============================================================

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../config_services.php';
require_once __DIR__ . '/../../../controller/ParticipationController.php';
require_once __DIR__ . '/../../../services/QrCodeService.php';

// ── Vérification des paramètres
if (!isset($_GET['id'], $_GET['token'])) {
    http_response_code(400);
    die("Lien QR invalide.");
}

$id    = (int)$_GET['id'];
$token = $_GET['token'];

// ── Vérification du token de sécurité
if (!QrCodeService::verifierToken($id, $token)) {
    http_response_code(403);
    die("⛔ Accès non autorisé. Ce QR Code n'est pas valide.");
}

// ── Récupération des données
$participationC = new ParticipationController();
$data = $participationC->showParticipation($id);

if (!$data) {
    http_response_code(404);
    die("Participant introuvable.");
}

// Labels de statut
$statutLabels = [
    'confirmée'  => ['label' => 'Confirmée',  'icon' => '✅', 'class' => 'confirmee'],
    'annulée'    => ['label' => 'Annulée',    'icon' => '❌', 'class' => 'annulee'],
    'en_attente' => ['label' => 'En attente', 'icon' => '⏳', 'class' => 'attente'],
];
$statut = $statutLabels[$data['statut']] ?? ['label' => $data['statut'], 'icon' => '•', 'class' => 'attente'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Participant – GaiaLumen</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            background: linear-gradient(135deg, #0a1a10 0%, #1F3D2B 50%, #0a1a10 100%);
            min-height: 100vh;
            font-family: 'Lato', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: rgba(15, 35, 24, 0.95);
            border: 1px solid rgba(91, 62, 150, 0.3);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,.6), 0 0 0 1px rgba(91,62,150,.1);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            animation: pop .5s cubic-bezier(.175,.885,.32,1.275);
        }
        @keyframes pop {
            from { opacity:0; transform:scale(.85) translateY(20px); }
            to   { opacity:1; transform:scale(1)  translateY(0); }
        }

        /* ── En-tête ── */
        .card-header {
            background: linear-gradient(135deg, #1F3D2B, #2d5a3f);
            padding: 32px 28px 24px;
            text-align: center;
            position: relative;
        }
        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #F2E8CF;
            letter-spacing: 1px;
            margin-bottom: 18px;
            opacity: .9;
        }
        .logo span { color: #5B3E96; }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5B3E96, #3A86C4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 4px 20px rgba(91,62,150,.5);
            font-family: 'Cormorant Garamond', serif;
        }

        .nom {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #F2E8CF;
            line-height: 1.2;
        }

        .ref {
            margin-top: 10px;
            display: inline-block;
            background: rgba(91,62,150,.3);
            border: 1px solid rgba(91,62,150,.5);
            color: #b8a8e8;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* ── Statut ── */
        .statut-bar {
            padding: 14px 28px;
            text-align: center;
            font-weight: 700;
            font-size: .9rem;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .statut-bar.confirmee { background: rgba(46,204,113,.15); color: #2ecc71; border-bottom: 2px solid rgba(46,204,113,.3); }
        .statut-bar.annulee   { background: rgba(231,76,60,.15);  color: #e74c3c; border-bottom: 2px solid rgba(231,76,60,.3); }
        .statut-bar.attente   { background: rgba(241,196,15,.15); color: #f1c40f; border-bottom: 2px solid rgba(241,196,15,.3); }

        /* ── Informations ── */
        .card-body { padding: 24px 28px 28px; }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .info-row:last-child { border-bottom: none; }

        .info-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(91,62,150,.2);
            border: 1px solid rgba(91,62,150,.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .info-content {}
        .info-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #a8b8a0;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: .95rem;
            font-weight: 600;
            color: #F2E8CF;
            word-break: break-word;
        }

        /* ── Footer carte ── */
        .card-footer {
            background: rgba(0,0,0,.2);
            padding: 14px 28px;
            text-align: center;
            font-size: .72rem;
            color: #a8b8a0;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .card-footer span { color: #5B3E96; font-weight: 700; }
    </style>
</head>
<body>

<div class="card">

    <!-- En-tête -->
    <div class="card-header">
        <div class="logo">🌿 Gaia<span>Lumen</span></div>

        <div class="avatar">
            <?= mb_strtoupper(mb_substr($data['nom_complet'], 0, 1)) ?>
        </div>

        <div class="nom"><?= htmlspecialchars($data['nom_complet']) ?></div>
        <div class="ref">PART-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></div>
    </div>

    <!-- Barre de statut -->
    <div class="statut-bar <?= $statut['class'] ?>">
        <?= $statut['icon'] ?>
        Participation <?= htmlspecialchars($statut['label']) ?>
    </div>

    <!-- Informations -->
    <div class="card-body">

        <div class="info-row">
            <div class="info-icon">📧</div>
            <div class="info-content">
                <div class="info-label">Email</div>
                <div class="info-value"><?= htmlspecialchars($data['email']) ?></div>
            </div>
        </div>

        <div class="info-row">
            <div class="info-icon">📞</div>
            <div class="info-content">
                <div class="info-label">Téléphone</div>
                <div class="info-value"><?= htmlspecialchars($data['telephone'] ?? 'Non renseigné') ?></div>
            </div>
        </div>

        <div class="info-row">
            <div class="info-icon">💚</div>
            <div class="info-content">
                <div class="info-label">Centre d'intérêt</div>
                <div class="info-value"><?= htmlspecialchars($data['centre_interet'] ?? 'Non spécifié') ?></div>
            </div>
        </div>

        <div class="info-row">
            <div class="info-icon">📅</div>
            <div class="info-content">
                <div class="info-label">Événement inscrit</div>
                <div class="info-value"><?= htmlspecialchars($data['evenement_titre'] ?? '—') ?></div>
            </div>
        </div>

    </div>

    <!-- Pied de carte -->
    <div class="card-footer">
        Fiche générée par <span>GaiaLumen Admin</span> · <?= date('d/m/Y') ?>
    </div>

</div>

</body>
</html>
