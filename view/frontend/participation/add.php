<?php
// Depuis frontoffice/participation/ → 3 niveaux → u/
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/EvenementController.php';

$error   = "";
$success = false;

// Récupérer l'événement pour afficher son nom
$event_id    = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$event_titre = "cet événement";
if ($event_id) {
    $ec = new EvenementController();
    $ev = $ec->showEvenement($event_id);
    if ($ev) $event_titre = $ev['titre'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['nom_complet']) && !empty($_POST['email']) && !empty($_POST['id_event'])) {
        $db  = config::getConnexion();
        $sql = "INSERT INTO participation (id_event, nom_complet, email, telephone, centre_interet, statut)
                VALUES (:id_event, :nom_complet, :email, :telephone, :centre_interet, 'en_attente')";
        try {
            $stmt   = $db->prepare($sql);
            $result = $stmt->execute([
                ':id_event'       => (int)$_POST['id_event'],
                ':nom_complet'    => trim($_POST['nom_complet']),
                ':email'          => trim($_POST['email']),
                ':telephone'      => trim($_POST['telephone'] ?? ''),
                ':centre_interet' => trim($_POST['centre_interet'] ?? '')
            ]);
            if ($result) {
                $success = true;
            } else {
                $error = "Erreur lors de l'enregistrement.";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir les champs obligatoires (Nom, Email).";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
<meta charset="UTF-8">
<title>Participer - GaiaLumen</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
:root{--green:#1F3D2B;--sand:#F2E8CF;--violet:#5B3E96;--blue:#3A86C4;--bg:#0a1a10;--surface:#0f2318;--text:#F2E8CF;--muted:#a8b8a0;--card-bg:rgba(15,35,24,0.85);--glass:rgba(31,61,43,0.45);--shadow:0 8px 32px rgba(0,0,0,.4);--radius:18px;--tr:0.4s cubic-bezier(.4,0,.2,1);}
[data-theme="light"]{--bg:#f5f0e8;--surface:#ede5d0;--text:#1F3D2B;--muted:#5a6e5a;--card-bg:rgba(242,232,207,.85);--glass:rgba(242,232,207,.6);}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--bg);font-family:'Lato',sans-serif;min-height:100vh;color:var(--text);display:flex;align-items:center;justify-content:center;padding:20px;}
h1,h2,h3{font-family:'Cormorant Garamond',serif;}
.container{max-width:560px;width:100%;}
.card{background:var(--card-bg);backdrop-filter:blur(16px);border:1px solid rgba(91,62,150,.2);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);}
.card-header{background:var(--green);padding:28px 32px;}
.card-header h2{color:var(--sand);font-size:1.8rem;display:flex;align-items:center;gap:12px;margin:0;}
.card-header p{color:rgba(242,232,207,.8);margin:8px 0 0;font-size:.9rem;}
.card-body{padding:32px;}
.form-group{margin-bottom:20px;}
label{display:block;font-weight:600;color:var(--text);margin-bottom:6px;font-size:.9rem;}
label span{color:var(--violet);}
input,textarea,select{width:100%;padding:12px 14px;background:var(--surface);border:1px solid rgba(91,62,150,.3);border-radius:10px;color:var(--text);font-size:.95rem;font-family:'Lato',sans-serif;transition:all var(--tr);}
input:focus,textarea:focus{outline:none;border-color:var(--violet);box-shadow:0 0 0 3px rgba(91,62,150,.15);}
input::placeholder,textarea::placeholder{color:var(--muted);}
.btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,var(--violet),var(--blue));border:none;border-radius:50px;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;transition:all var(--tr);margin-top:8px;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(91,62,150,.4);}
.btn-back{display:inline-flex;align-items:center;gap:8px;color:var(--blue);text-decoration:none;font-size:.9rem;margin-bottom:20px;}
.btn-back:hover{color:var(--violet);}
.alert-error{background:rgba(231,76,60,.15);border:1px solid rgba(231,76,60,.3);border-radius:10px;padding:14px;color:#e74c3c;margin-bottom:20px;font-size:.9rem;}
.success-box{text-align:center;padding:40px 32px;}
.success-icon{font-size:4rem;margin-bottom:16px;}
.success-box h3{font-size:1.8rem;margin-bottom:12px;}
.success-box p{color:var(--muted);margin-bottom:24px;}
.btn-retour{display:inline-block;padding:12px 32px;background:linear-gradient(135deg,var(--green),var(--blue));color:#fff;border-radius:50px;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="container">
  <a href="../dashboard.html" class="btn-back">← Retour au dashboard</a>
  <div class="card">
    <?php if ($success): ?>
      <div class="success-box">
        <div class="success-icon">✅</div>
        <h3>Inscription confirmée !</h3>
        <p>Votre participation à <strong>"<?= htmlspecialchars($event_titre) ?>"</strong><br>a été enregistrée avec succès.<br><small style="color:var(--muted);">Statut : en attente de confirmation</small></p>
        <a href="../dashboard.html" class="btn-retour">Retour aux événements</a>
      </div>
    <?php else: ?>
      <div class="card-header">
        <h2>📝 S'inscrire à un événement</h2>
        <p>📍 <?= htmlspecialchars($event_titre) ?></p>
      </div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return validerParticipation()">
          <input type="hidden" name="id_event" value="<?= $event_id ?>">
          <div class="form-group">
            <label><span>👤</span> Nom complet *</label>
            <input type="text" name="nom_complet" id="nom_complet" placeholder="Ex: Jean Dupont" required>
          </div>
          <div class="form-group">
            <label><span>📧</span> Email *</label>
            <input type="email" name="email" id="email" placeholder="votre@email.com" required>
          </div>
          <div class="form-group">
            <label><span>📞</span> Téléphone</label>
            <input type="tel" name="telephone" id="telephone" placeholder="Ex: 26 664 726">
          </div>
          <div class="form-group">
            <label><span>💚</span> Centre d'intérêt</label>
            <input type="text" name="centre_interet" placeholder="Ex: Yoga, Nutrition, Écologie...">
          </div>
          <button type="submit" class="btn-submit">✅ Confirmer ma participation</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<script>
  // Hériter du thème
  const t = localStorage.getItem('gaialumen-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);

  function validerParticipation() {
    const nom = document.getElementById('nom_complet').value.trim();
    const email = document.getElementById('email').value.trim();
    const tel = document.getElementById('telephone').value.trim();
    if (nom.length < 3) { alert('❌ Le nom doit contenir au moins 3 caractères.'); return false; }
    if (!email.includes('@') || !email.includes('.')) { alert('❌ Email invalide.'); return false; }
    if (tel.length > 0) {
      const chiffres = tel.replace(/\D/g, '');
      if (chiffres.length < 8) { alert('❌ Téléphone invalide (min 8 chiffres).'); return false; }
    }
    return true;
  }
</script>
</body>
</html>
