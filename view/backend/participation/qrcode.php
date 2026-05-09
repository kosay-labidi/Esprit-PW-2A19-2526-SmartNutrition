<?php
// =============================================================
//  view/backoffice/participation/qrcode.php
//  Page admin : affiche et permet de télécharger le QR Code
//  d'un participant
// =============================================================

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../config_services.php';
require_once __DIR__ . '/../../../controller/ParticipationController.php';
require_once __DIR__ . '/../../../services/QrCodeService.php';

$participationC = new ParticipationController();

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id   = (int)$_GET['id'];
$data = $participationC->showParticipation($id);

if (!$data) {
    die("Participation introuvable.");
}

// ── Si ?dl=1 → on envoie directement le SVG pour téléchargement
if (isset($_GET['dl']) && $_GET['dl'] == '1') {
    $svg = QrCodeService::genererSvg($id, 400);
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="qrcode_participant_' . $id . '.svg"');
    header('Content-Length: ' . strlen($svg));
    echo $svg;
    exit();
}

// ── Génération base64 pour affichage inline
$qrBase64 = QrCodeService::genererBase64($id, 350);
$qrUrl    = QrCodeService::construireUrl($id);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR Code – <?= htmlspecialchars($data['nom_complet']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green:   #1F3D2B;
            --sand:    #F2E8CF;
            --violet:  #5B3E96;
            --blue:    #3A86C4;
            --bg:      #0a1a10;
            --surface: #0f2318;
            --text:    #F2E8CF;
            --muted:   #a8b8a0;
            --card-bg: rgba(15, 35, 24, 0.80);
            --glass:   rgba(31, 61, 43, 0.45);
            --shadow:  0 8px 32px rgba(0,0,0,.4);
            --radius:  18px;
            --tr:      0.4s cubic-bezier(.4,0,.2,1);
        }
        [data-theme="light"] {
            --bg:#F2E8CF; --surface:#fff; --text:#1F3D2B; --muted:#5a6b5a;
            --card-bg:rgba(255,255,255,.9); --glass:rgba(45,90,63,.1); --shadow:0 8px 32px rgba(0,0,0,.1);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); font-family:'Lato',sans-serif; min-height:100vh; color:var(--text); transition:all var(--tr); }
        h1,h2,h3,h4,h5,.navbar-brand { font-family:'Cormorant Garamond',serif; font-weight:700; }

        .navbar-custom { background:var(--green); box-shadow:var(--shadow); padding:1rem 0; position:sticky; top:0; z-index:1000; }
        .navbar-brand  { font-size:1.8rem; color:var(--sand)!important; transition:all var(--tr); }
        .navbar-brand i{ color:var(--violet); margin-right:8px; }
        .navbar-brand:hover { transform:scale(1.05); }
        .theme-toggle { background:var(--glass); border:1px solid var(--sand); border-radius:50px; padding:8px 20px; color:var(--sand); cursor:pointer; transition:all var(--tr); }
        .theme-toggle:hover { background:var(--violet); border-color:var(--violet); }

        /* ── Carte principale ── */
        .qr-card {
            background:var(--card-bg);
            backdrop-filter:blur(12px);
            border:1px solid rgba(91,62,150,.15);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            overflow:hidden;
            animation:fadeUp .6s ease;
        }
        @keyframes fadeUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

        .qr-card-header {
            background:var(--green);
            padding:28px 35px;
            display:flex;
            align-items:center;
            gap:18px;
        }
        .qr-card-header h2 { color:var(--sand); margin:0; font-size:1.9rem; }
        .qr-card-header h2 i { color:var(--violet); }
        .qr-card-header p  { color:var(--muted); margin:4px 0 0; font-size:.9rem; }

        .qr-card-body { padding:40px 35px; display:grid; grid-template-columns:auto 1fr; gap:40px; align-items:start; }

        /* ── Zone QR ── */
        .qr-zone {
            text-align:center;
        }
        .qr-frame {
            background:#fff;
            border-radius:16px;
            padding:18px;
            display:inline-block;
            box-shadow:0 4px 20px rgba(91,62,150,.25);
            position:relative;
            transition:all var(--tr);
        }
        .qr-frame:hover { transform:scale(1.03); box-shadow:0 8px 32px rgba(91,62,150,.4); }
        .qr-frame img { display:block; width:250px; height:250px; }
        .qr-badge {
            position:absolute;
            bottom:-14px;
            left:50%;
            transform:translateX(-50%);
            background:var(--violet);
            color:#fff;
            padding:5px 16px;
            border-radius:50px;
            font-size:.75rem;
            font-weight:700;
            white-space:nowrap;
            letter-spacing:.5px;
        }
        .qr-ref {
            margin-top:28px;
            font-size:.8rem;
            color:var(--muted);
        }
        .qr-ref code {
            background:var(--glass);
            color:var(--violet);
            padding:3px 10px;
            border-radius:8px;
            font-size:.85rem;
        }

        /* ── Infos participant ── */
        .info-panel {}
        .info-title {
            font-family:'Cormorant Garamond',serif;
            font-size:1.4rem;
            font-weight:700;
            color:var(--text);
            margin-bottom:20px;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .info-title i { color:var(--violet); }

        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:30px; }
        .info-item {
            background:var(--surface);
            border-left:4px solid var(--violet);
            border-radius:12px;
            padding:14px 18px;
            transition:all var(--tr);
        }
        .info-item:hover { transform:translateX(4px); }
        .info-label { font-size:.7rem; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin-bottom:6px; display:flex; align-items:center; gap:5px; }
        .info-label i { color:var(--violet); }
        .info-value { font-size:.95rem; font-weight:600; color:var(--text); word-break:break-word; }
        .badge-statut {
            display:inline-block;
            padding:4px 12px;
            border-radius:20px;
            font-size:.78rem;
            font-weight:600;
        }
        .bs-confirmée  { background:rgba(46,204,113,.2);  color:#2ecc71; border:1px solid rgba(46,204,113,.3); }
        .bs-annulée    { background:rgba(231,76,60,.2);   color:#e74c3c; border:1px solid rgba(231,76,60,.3); }
        .bs-en_attente { background:rgba(241,196,15,.2);  color:#f1c40f; border:1px solid rgba(241,196,15,.3); }

        /* ── Boutons action ── */
        .action-btns { display:flex; gap:14px; flex-wrap:wrap; }
        .btn-dl {
            background:linear-gradient(135deg,var(--violet),var(--blue));
            color:#fff;
            padding:13px 28px;
            border-radius:50px;
            text-decoration:none;
            font-weight:700;
            display:inline-flex;
            align-items:center;
            gap:10px;
            transition:all var(--tr);
            box-shadow:0 4px 15px rgba(91,62,150,.3);
            border:none;
            cursor:pointer;
        }
        .btn-dl:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(91,62,150,.5); color:#fff; }
        .btn-back {
            background:var(--glass);
            border:1px solid var(--sand);
            color:var(--sand);
            padding:13px 28px;
            border-radius:50px;
            text-decoration:none;
            font-weight:600;
            display:inline-flex;
            align-items:center;
            gap:10px;
            transition:all var(--tr);
        }
        .btn-back:hover { background:var(--green); color:var(--sand); transform:translateY(-3px); }

        /* ── URL du lien QR ── */
        .qr-url-box {
            background:var(--surface);
            border:1px solid rgba(91,62,150,.2);
            border-radius:12px;
            padding:16px 20px;
            margin-top:20px;
        }
        .qr-url-box label { font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:var(--violet); font-weight:700; display:block; margin-bottom:8px; }
        .qr-url-box .url-text { font-size:.8rem; color:var(--muted); word-break:break-all; font-family:monospace; }
        .btn-copy { background:var(--glass); border:1px solid var(--sand); color:var(--sand); padding:5px 14px; border-radius:50px; font-size:.78rem; cursor:pointer; transition:all var(--tr); float:right; margin-left:10px; }
        .btn-copy:hover { background:var(--violet); border-color:var(--violet); }

        .footer { background:var(--surface); color:var(--muted); padding:25px 0; margin-top:60px; text-align:center; border-top:1px solid var(--glass); }

        @media (max-width:768px) {
            .qr-card-body { grid-template-columns:1fr; }
            .info-grid { grid-template-columns:1fr; }
            .action-btns { flex-direction:column; }
            .qr-card-header { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body data-theme="dark">

<nav class="navbar navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="../admin.html">
            <i class="fas fa-leaf"></i> GaiaLumen
        </a>
        <div class="d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle"><i class="fas fa-sun me-1"></i> Mode clair</button>
            <a href="list.php" class="text-white" style="text-decoration:none;opacity:.9;">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5" style="max-width:1000px;">

    <div class="qr-card">
        <!-- En-tête -->
        <div class="qr-card-header">
            <div>
                <h2><i class="fas fa-qrcode"></i> QR Code Participant</h2>
                <p>Scannez ce code pour afficher les coordonnées de <?= htmlspecialchars($data['nom_complet']) ?></p>
            </div>
        </div>

        <!-- Corps -->
        <div class="qr-card-body">

            <!-- ── QR CODE ── -->
            <div class="qr-zone">
                <div class="qr-frame">
                    <img src="data:image/svg+xml;base64,<?= $qrBase64 ?>"
                         alt="QR Code de <?= htmlspecialchars($data['nom_complet']) ?>">
                    <div class="qr-badge">
                        <i class="fas fa-qrcode me-1"></i> PART-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
                <div class="qr-ref mt-4">
                    Référence : <code>PART-<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></code>
                </div>

                <!-- URL encodée dans le QR -->
                <div class="qr-url-box mt-3">
                    <label><i class="fas fa-link me-1"></i> URL encodée dans le QR</label>
                    <button class="btn-copy" onclick="copierUrl()">
                        <i class="fas fa-copy me-1"></i> Copier
                    </button>
                    <div class="url-text" id="qrUrlText"><?= htmlspecialchars($qrUrl) ?></div>
                </div>
            </div>

            <!-- ── INFOS PARTICIPANT ── -->
            <div class="info-panel">
                <div class="info-title">
                    <i class="fas fa-user-circle"></i> Coordonnées du participant
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-user"></i> NOM COMPLET</div>
                        <div class="info-value"><?= htmlspecialchars($data['nom_complet']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-envelope"></i> EMAIL</div>
                        <div class="info-value"><?= htmlspecialchars($data['email']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-phone"></i> TÉLÉPHONE</div>
                        <div class="info-value"><?= htmlspecialchars($data['telephone'] ?? 'Non renseigné') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-heart"></i> CENTRE D'INTÉRÊT</div>
                        <div class="info-value"><?= htmlspecialchars($data['centre_interet'] ?? 'Non spécifié') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar-alt"></i> ÉVÉNEMENT</div>
                        <div class="info-value"><?= htmlspecialchars($data['evenement_titre'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-flag-checkered"></i> STATUT</div>
                        <div class="info-value">
                            <?php
                            $s = $data['statut'] ?? 'en_attente';
                            $labels = ['confirmée'=>'✅ Confirmée','annulée'=>'❌ Annulée','en_attente'=>'⏳ En attente'];
                            ?>
                            <span class="badge-statut bs-<?= $s ?>"><?= $labels[$s] ?? $s ?></span>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="action-btns">
                    <a href="qrcode.php?id=<?= $id ?>&dl=1" class="btn-dl">
                        <i class="fas fa-download"></i> Télécharger le QR Code (.svg)
                    </a>
                    <a href="list.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                </div>

                <div style="margin-top:20px;padding:14px 18px;background:var(--glass);border-radius:12px;border-left:4px solid var(--blue);font-size:.85rem;color:var(--muted);">
                    <i class="fas fa-info-circle me-2" style="color:var(--blue);"></i>
                    <strong style="color:var(--text);">Comment utiliser ce QR Code ?</strong><br>
                    Scannez-le avec n'importe quelle application de lecture QR. La fiche complète du participant s'affichera automatiquement.
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container"><p>&copy; 2026 GaiaLumen - Héritage de Gaia. Tous droits réservés.</p></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Thème
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme  = localStorage.getItem('theme');
    if (savedTheme) { document.body.setAttribute('data-theme', savedTheme); updateBtn(savedTheme); }
    themeToggle.addEventListener('click', () => {
        const t = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', t);
        localStorage.setItem('theme', t);
        updateBtn(t);
    });
    function updateBtn(t) {
        themeToggle.innerHTML = t === 'dark'
            ? '<i class="fas fa-sun me-1"></i> Mode clair'
            : '<i class="fas fa-moon me-1"></i> Mode sombre';
    }

    // Copier l'URL
    function copierUrl() {
        const url = document.getElementById('qrUrlText').textContent;
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.querySelector('.btn-copy');
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Copié !';
            btn.style.background = '#2ecc71';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy me-1"></i> Copier';
                btn.style.background = '';
            }, 2000);
        });
    }
</script>
</body>
</html>
