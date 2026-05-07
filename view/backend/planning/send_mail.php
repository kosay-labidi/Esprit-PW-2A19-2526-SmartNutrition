<?php
/**
 * send_mail.php — GaiaLumen | Notification mail approbation/rejet de planning
 * Appelé en interne après updateStatut() dans listDemandeplanning.php
 *
 * Paramètres attendus (POST JSON ou GET) :
 *   id_demande  : int    — ID de la demande de planning
 *   statut      : string — 'approuve' ou 'rejete'
 */

require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json; charset=utf-8');

/* ── 1. Récupérer les paramètres ───────────────────────────────────────── */
$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$id_demande = (int)   ($input['id_demande'] ?? $_GET['id_demande'] ?? $_POST['id_demande'] ?? 0);
$statut     = trim(    $input['statut']     ?? $_GET['statut']     ?? $_POST['statut']     ?? '');

if ($id_demande <= 0 || !in_array($statut, ['approuve', 'rejete'], true)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

/* ── 2. Charger la demande depuis la DB ────────────────────────────────── */
try {
    $db   = config::getConnexion();
    $stmt = $db->prepare("SELECT * FROM demandeplanning WHERE id = :id");
    $stmt->execute([':id' => $id_demande]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur DB demande : ' . $e->getMessage()]);
    exit;
}

if (!$demande) {
    echo json_encode(['success' => false, 'error' => 'Demande introuvable']);
    exit;
}

/* ── 3. Récupérer l'email de l'utilisateur (essaie plusieurs noms de table) ── */
$email    = null;
$userName = null;
$userId   = (int) $demande['id_utilisateur'];

$tablesToTry = ['utilisateur', 'user', 'users', 'membre', 'members'];
foreach ($tablesToTry as $table) {
    try {
        $stmt = $db->prepare("SELECT * FROM `$table` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Cherche le champ email (email, mail, e_mail…)
            foreach (['email', 'mail', 'e_mail', 'adresse_email', 'adresse_mail'] as $emailField) {
                if (!empty($user[$emailField])) {
                    $email = $user[$emailField];
                    break;
                }
            }
            // Cherche un nom (nom, name, prenom, username…)
            foreach (['nom', 'prenom', 'name', 'username', 'pseudo', 'fullname', 'full_name'] as $nameField) {
                if (!empty($user[$nameField])) {
                    $userName = $user[$nameField];
                    break;
                }
            }
            // Prénom + Nom si disponibles séparément
            if (isset($user['prenom']) && isset($user['nom'])) {
                $userName = trim($user['prenom'] . ' ' . $user['nom']);
            }
            break; // table trouvée, on sort
        }
    } catch (PDOException $e) {
        // cette table n'existe pas, on essaie la suivante
        continue;
    }
}

if (!$email) {
    echo json_encode([
        'success' => false,
        'error'   => "Email introuvable pour l'utilisateur #$userId (tables essayées : " . implode(', ', $tablesToTry) . ')'
    ]);
    exit;
}

$userName = $userName ?: "Utilisateur #$userId";

/* ── 4. Construire le mail HTML dark-theme GaiaLumen ──────────────────── */
$isApprouve  = ($statut === 'approuve');
$statusLabel = $isApprouve ? 'APPROUVÉE'   : 'REJETÉE';
$statusColor = $isApprouve ? '#2ecc71'     : '#e74c3c';
$statusBg    = $isApprouve ? '#0d2e1c'     : '#2e0d0d';
$statusEmoji = $isApprouve ? '✅'          : '❌';
$messageUser = $isApprouve
    ? "Votre demande de planning nutritionnel a été <strong>approuvée</strong> par notre équipe. Votre planning personnalisé est maintenant disponible sur votre tableau de bord."
    : "Votre demande de planning nutritionnel a été <strong>rejetée</strong> par notre équipe. Vous pouvez soumettre une nouvelle demande depuis votre tableau de bord.";

$ref        = str_pad($demande['id'], 5, '0', STR_PAD_LEFT);
$calories   = htmlspecialchars($demande['calories'] ?? '—') . ' kcal';
$budget     = number_format((float)($demande['budget'] ?? 0), 2) . ' € / ' . htmlspecialchars($demande['type_budget'] ?? '');
$duree      = htmlspecialchars($demande['duree'] ?? '—') . ' ' . htmlspecialchars($demande['type_duree'] ?? '');
$dateDemandeRaw = $demande['date_demande'] ?? null;
$dateFmt    = $dateDemandeRaw ? date('d/m/Y à H:i', strtotime($dateDemandeRaw)) : date('d/m/Y à H:i');
$dateDecision = date('d/m/Y à H:i');

// URL dashboard — adapte si besoin
$dashboardUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/SmartNutrition/view/frontend/dashboard.html';

$subject = $isApprouve
    ? "✅ Votre planning GaiaLumen #$ref a été approuvé !"
    : "❌ Votre planning GaiaLumen #$ref a été rejeté";

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#0a0a0f;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0f;padding:40px 0">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

      <!-- HEADER -->
      <tr>
        <td style="background:linear-gradient(135deg,#1a0a2e 0%,#16213e 100%);border-radius:16px 16px 0 0;padding:36px 40px;text-align:center">
          <div style="font-size:2rem;margin-bottom:8px">🌿</div>
          <div style="color:#fff;font-size:1.6rem;font-weight:800;letter-spacing:2px">GaiaLumen</div>
          <div style="color:rgba(255,255,255,.5);font-size:.85rem;margin-top:4px">Nutrition &amp; Bien-être personnalisé</div>
        </td>
      </tr>

      <!-- STATUS BANNER -->
      <tr>
        <td style="background:#111118;padding:28px 40px;text-align:center">
          <div style="display:inline-block;background:{$statusBg};border:2px solid {$statusColor};border-radius:50px;padding:10px 32px">
            <span style="color:{$statusColor};font-size:1.1rem;font-weight:800;letter-spacing:1px">
              {$statusEmoji} DEMANDE {$statusLabel}
            </span>
          </div>
          <p style="color:rgba(255,255,255,.75);margin:18px 0 0;font-size:.95rem;line-height:1.6">
            Bonjour <strong style="color:#fff">{$userName}</strong>,<br>
            {$messageUser}
          </p>
        </td>
      </tr>

      <!-- RECAP TABLE -->
      <tr>
        <td style="background:#0d0d15;padding:24px 40px">
          <div style="color:rgba(255,255,255,.4);font-size:.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:14px">
            Récapitulatif de votre demande
          </div>
          <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:0 6px">
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px;width:40%">📋 Référence</td>
              <td style="color:#fff;font-size:.85rem;font-weight:700;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">#REF-{$ref}</td>
            </tr>
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px">🔥 Calories</td>
              <td style="color:#fff;font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">{$calories}</td>
            </tr>
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px">💰 Budget</td>
              <td style="color:#fff;font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">{$budget}</td>
            </tr>
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px">⏱️ Durée</td>
              <td style="color:#fff;font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">{$duree}</td>
            </tr>
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px">📅 Date demande</td>
              <td style="color:#fff;font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">{$dateFmt}</td>
            </tr>
            <tr>
              <td style="color:rgba(255,255,255,.5);font-size:.85rem;padding:10px 14px;background:#13131f;border-radius:6px 0 0 6px">🕐 Décision le</td>
              <td style="color:{$statusColor};font-size:.85rem;font-weight:700;padding:10px 14px;background:#13131f;border-radius:0 6px 6px 0">{$dateDecision}</td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- CTA -->
      <tr>
        <td style="background:#111118;padding:28px 40px;text-align:center">
          <a href="{$dashboardUrl}"
             style="display:inline-block;background:linear-gradient(135deg,#5b3e96,#3a1f6e);color:#fff;text-decoration:none;padding:14px 36px;border-radius:50px;font-weight:700;font-size:.95rem;letter-spacing:.5px">
            🏠 Accéder à mon tableau de bord
          </a>
        </td>
      </tr>

      <!-- FOOTER -->
      <tr>
        <td style="background:#0a0a12;border-radius:0 0 16px 16px;padding:20px 40px;text-align:center;border-top:1px solid rgba(255,255,255,.05)">
          <p style="color:rgba(255,255,255,.3);font-size:.75rem;margin:0">
            🌿 GaiaLumen — Nutrition &amp; Bien-être<br>
            Ce mail est envoyé automatiquement, merci de ne pas y répondre.
          </p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;

/* ── 5. Envoyer le mail ────────────────────────────────────────────────── */
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: GaiaLumen <no-reply@gaialumen.com>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($email, $subject, $htmlBody, $headers);

echo json_encode([
    'success'  => $sent,
    'email'    => $email,
    'statut'   => $statut,
    'message'  => $sent
        ? "📧 Mail envoyé à $email"
        : "⚠️ Échec envoi mail à $email (vérifiez la config SMTP de PHP/XAMPP)"
]);