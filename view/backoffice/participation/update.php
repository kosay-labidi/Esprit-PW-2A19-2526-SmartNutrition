<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/ParticipationController.php';

$participationC = new ParticipationController();

if (!isset($_GET['id'])) { header("Location: list.php"); exit(); }

$id      = $_GET['id'];
$idEvent = isset($_GET['id_event']) ? (int)$_GET['id_event'] : null;
$retour  = $idEvent ? "list.php?id_event={$idEvent}" : "list.php";
$data    = $participationC->showParticipation($id);
if (!$data) { die("Participation non trouvée"); }

$error    = "";
$hasEmail = !empty(trim($data['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveauStatut = $_POST['statut'];

    // ── MÉTIER AVANCÉ : mise à jour + email HTML ───────────────
    $result = $participationC->updateStatusWithEmailNotification((int)$id, $nouveauStatut);
    // ──────────────────────────────────────────────────────────

    if ($result['success']) {
        $sep   = strpos($retour, '?') !== false ? '&' : '?';
        $flash = $result['email_sent'] ? 'email_sent' : 'updated';
        header("Location: {$retour}{$sep}success={$flash}");
        exit();
    } else {
        $error = $result['message'];
    }
}

$statusText = [
    'en_attente' => '⏳ En attente',
    'confirmée'  => '✅ Confirmée',
    'annulée'    => '❌ Annulée'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Participation - GaiaLumen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    <style>
        :root{--green:#1F3D2B;--sand:#F2E8CF;--violet:#5B3E96;--blue:#3A86C4;--bg:#0a1a10;--surface:#0f2318;--text:#F2E8CF;--muted:#a8b8a0;--card-bg:rgba(15,35,24,.72);--glass:rgba(31,61,43,.45);--shadow:0 8px 32px rgba(0,0,0,.4);--radius:18px;--tr:.4s cubic-bezier(.4,0,.2,1);}
        [data-theme="light"]{--green:#2d5a3f;--bg:#F2E8CF;--surface:#fff;--text:#1F3D2B;--muted:#5a6b5a;--card-bg:rgba(255,255,255,.9);--glass:rgba(45,90,63,.1);--shadow:0 8px 32px rgba(0,0,0,.1);}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--bg);font-family:'Lato',sans-serif;min-height:100vh;transition:all var(--tr);color:var(--text);}
        h1,h2,h3,h4,h5,h6,.navbar-brand{font-family:'Cormorant Garamond',serif;font-weight:700;}
        .navbar-custom{background:var(--green);backdrop-filter:blur(10px);box-shadow:var(--shadow);padding:1rem 0;}
        .navbar-brand{font-size:1.8rem;font-weight:700;letter-spacing:1px;color:var(--sand)!important;transition:all var(--tr);}
        .navbar-brand i{font-size:1.8rem;margin-right:10px;color:var(--violet);}
        .navbar-brand:hover{transform:scale(1.05);}
        .theme-toggle{background:var(--glass);border:1px solid var(--sand);border-radius:50px;padding:8px 20px;color:var(--sand);cursor:pointer;transition:all var(--tr);font-weight:500;}
        .theme-toggle:hover{background:var(--violet);border-color:var(--violet);}
        .header-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:40px;flex-wrap:wrap;gap:20px;}
        .header-section h2{color:var(--text);font-weight:800;margin:0;display:flex;align-items:center;gap:15px;font-size:2rem;}
        .header-section h2 i{color:var(--violet);}
        .form-card{border-radius:var(--radius);border:1px solid rgba(91,62,150,.1);box-shadow:var(--shadow);overflow:hidden;background:var(--card-bg);backdrop-filter:blur(12px);}
        .card-header-custom{background:var(--green);padding:25px 30px;}
        .card-header-custom h2{margin:0;font-size:1.8rem;font-weight:700;display:flex;align-items:center;gap:12px;color:var(--sand);}
        .card-header-custom h2 i{color:var(--violet);}
        .card-header-custom p{margin:8px 0 0;opacity:.9;font-size:.9rem;color:var(--sand);}
        .card-body-custom{padding:35px;}
        .info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:30px;}
        .info-item{background:var(--surface);border-radius:12px;padding:18px 20px;border-left:3px solid var(--violet);transition:all var(--tr);}
        .info-item:hover{transform:translateX(5px);}
        .info-label{font-size:.7rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px;}
        .info-label i{color:var(--violet);}
        .info-value{font-size:1rem;font-weight:600;color:var(--text);margin:0;word-break:break-word;}
        .badge-en_attente{background:rgba(241,196,15,.2);color:#f1c40f;border:1px solid rgba(241,196,15,.3);padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;}
        .badge-confirm-e{background:rgba(46,204,113,.2);color:#2ecc71;border:1px solid rgba(46,204,113,.3);padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;}
        .badge-annul-e{background:rgba(231,76,60,.2);color:#e74c3c;border:1px solid rgba(231,76,60,.3);padding:6px 14px;border-radius:20px;font-size:.8rem;font-weight:600;}
        .form-label{font-weight:600;color:var(--text);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
        .form-label i{color:var(--violet);}
        .form-select{border-radius:12px;border:1px solid rgba(91,62,150,.2);padding:12px 16px;background-color:var(--surface);color:var(--text);transition:all var(--tr);cursor:pointer;width:100%;font-size:1rem;}
        .form-select:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(91,62,150,.2);outline:none;}
        .form-select option{background:var(--surface);color:var(--text);}
        .alert-err{background:rgba(231,76,60,.15);border-left:4px solid #e74c3c;color:#e74c3c;border-radius:12px;padding:15px 20px;margin-bottom:25px;}
        .email-notice{border-radius:12px;padding:15px 18px;margin-top:20px;font-size:.88rem;display:flex;align-items:flex-start;gap:12px;}
        .email-notice.active{background:rgba(46,204,113,.1);border:1px solid rgba(46,204,113,.3);}
        .email-notice.inactive{background:rgba(241,196,15,.1);border:1px solid rgba(241,196,15,.3);color:#f1c40f;}
        .email-notice i{font-size:1.3rem;margin-top:2px;}
        .email-notice.active i{color:#2ecc71;}
        .email-notice strong{display:block;margin-bottom:3px;color:var(--text);}
        .btn-update{background:linear-gradient(135deg,var(--violet),var(--blue));color:#fff;padding:14px 32px;border-radius:50px;font-weight:700;border:none;display:inline-flex;align-items:center;gap:10px;box-shadow:0 4px 15px rgba(91,62,150,.3);cursor:pointer;font-size:1rem;font-family:'Lato',sans-serif;transition:all var(--tr);}
        .btn-update:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(91,62,150,.5);}
        .btn-cancel{background:var(--glass);border:1px solid var(--sand);color:var(--sand);padding:14px 32px;border-radius:50px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:10px;transition:all var(--tr);}
        .btn-cancel:hover{background:var(--violet);color:var(--sand);transform:translateY(-3px);}
        .footer{background:var(--surface);color:var(--muted);padding:25px 0;margin-top:60px;text-align:center;border-top:1px solid var(--glass);}
        @media(max-width:768px){.info-grid{grid-template-columns:1fr;}.card-body-custom{padding:20px;}}
    </style>
</head>
<body data-theme="dark">

<nav class="navbar navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="../../../view/backoffice/modules/dashboard-admin.html">
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

<div class="container mt-4 mb-5">
    <div class="header-section">
        <h2><i class="fas fa-edit"></i> Modifier la Participation</h2>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="card-header-custom">
                    <h2><i class="fas fa-user-check"></i> Participation n° <?= htmlspecialchars($id) ?></h2>
                    <p><i class="fas fa-envelope me-1"></i> Un email HTML sera envoyé automatiquement au participant</p>
                </div>
                <div class="card-body-custom">

                    <?php if ($error): ?>
                    <div class="alert-err mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user"></i> Nom complet</div>
                            <p class="info-value"><?= htmlspecialchars($data['nom_complet']) ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                            <p class="info-value"><?= htmlspecialchars($data['email']) ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Téléphone</div>
                            <p class="info-value"><?= htmlspecialchars($data['telephone'] ?? 'Non renseigné') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-heart"></i> Centre d'intérêt</div>
                            <p class="info-value"><?= htmlspecialchars($data['centre_interet'] ?? 'Non spécifié') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Événement</div>
                            <p class="info-value"><?= htmlspecialchars($data['evenement_titre'] ?? '—') ?></p>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-flag-checkered"></i> Statut actuel</div>
                            <p class="info-value">
                                <?= $statusText[$data['statut']] ?? htmlspecialchars($data['statut']) ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="update.php?id=<?= htmlspecialchars($id) ?><?= $idEvent ? '&id_event='.$idEvent : '' ?>">
                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-exchange-alt"></i> Nouveau statut</label>
                            <select name="statut" class="form-select" required>
                                <option value="en_attente" <?= $data['statut']==='en_attente'?'selected':'' ?>>⏳ En attente</option>
                                <option value="confirmée"  <?= $data['statut']==='confirmée' ?'selected':'' ?>>✅ Confirmée</option>
                                <option value="annulée"    <?= $data['statut']==='annulée'   ?'selected':'' ?>>❌ Annulée</option>
                            </select>
                        </div>

                        <?php if ($hasEmail): ?>
                        <div class="email-notice active">
                            <i class="fas fa-envelope-circle-check"></i>
                            <div>
                                <strong>Email automatique activé</strong>
                                Un email HTML sera envoyé à
                                <strong style="color:var(--violet);"><?= htmlspecialchars($data['email']) ?></strong>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="email-notice inactive">
                            <i class="fas fa-envelope-open"></i>
                            <div>
                                <strong>Pas d'adresse email</strong>
                                Ce participant n'a pas d'email — aucun email ne sera envoyé.
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= htmlspecialchars($retour) ?>" class="btn-cancel">
                                <i class="fas fa-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn-update">
                                <i class="fas fa-paper-plane"></i>
                                Enregistrer<?= $hasEmail ? ' &amp; Envoyer Email' : '' ?>
                            </button>
                        </div>
                    </form>

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
    const btn = document.getElementById('themeToggle');
    const t0  = localStorage.getItem('theme') || 'dark';
    document.body.setAttribute('data-theme', t0); maj(t0);
    btn.addEventListener('click', () => {
        const t = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-theme', t); localStorage.setItem('theme', t); maj(t);
    });
    function maj(t) {
        btn.innerHTML = t === 'dark'
            ? '<i class="fas fa-sun me-1"></i> Mode clair'
            : '<i class="fas fa-moon me-1"></i> Mode sombre';
    }
</script>
</body>
</html>
