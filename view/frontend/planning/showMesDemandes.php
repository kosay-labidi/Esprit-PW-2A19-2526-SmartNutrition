<?php
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: listMesDemandes.php');
    exit;
}

$demandeC = new DemandeplanningController();
$d = $demandeC->getDemandeById((int)$_GET['id']);

if (!$d) {
    header('Location: listMesDemandes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Détail demande #<?= $d['id'] ?> - GaiaLumen</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    body {
      background: var(--bg);
      color: var(--text);
      padding: 40px 5%;
    }
    .detail-card {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      border-radius: var(--radius);
      border: 1px solid rgba(91,62,150,.2);
      padding: 32px;
      max-width: 800px;
      margin: 0 auto;
    }
    .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 16px;
    }
    .detail-header h1 {
      margin: 0;
    }
    .btn-back {
      background: var(--glass);
      border: 1px solid rgba(91,62,150,.3);
      border-radius: 30px;
      padding: 8px 20px;
      color: var(--text);
      text-decoration: none;
    }
    .detail-table {
      width: 100%;
      border-collapse: collapse;
    }
    .detail-table th, .detail-table td {
      padding: 16px;
      text-align: left;
      border-bottom: 1px solid rgba(91,62,150,.2);
    }
    .detail-table th {
      background: rgba(91,62,150,.1);
      width: 200px;
      font-weight: 600;
    }
    .badge-type {
      padding: 4px 12px;
      border-radius: 20px;
      background: rgba(58,134,196,.2);
      color: #3A86C4;
      font-size: 0.85rem;
      margin-left: 8px;
    }
  </style>
</head>
<body>
  <div class="detail-card">
    <div class="detail-header">
      <h1>👁️ Détail demande #<?= $d['id'] ?></h1>
      <a href="listMesDemandes.php" class="btn-back">← Retour à la liste</a>
    </div>

    <table class="detail-table">
      <tr>
        <th>🔢 ID Demande</th>
        <td><?= htmlspecialchars($d['id']) ?></td>
      </tr>
      <tr>
        <th>👤 ID Utilisateur</th>
        <td><?= htmlspecialchars($d['id_utilisateur']) ?></td>
      </tr>
      <tr>
        <th>🔥 Calories</th>
        <td><?= htmlspecialchars($d['calories']) ?> kcal / jour</td>
      </tr>
      <tr>
        <th>💰 Budget</th>
        <td>
          <?= number_format((float)$d['budget'], 2) ?> €
          <span class="badge-type"><?= htmlspecialchars($d['type_budget']) ?></span>
        </td>
      </tr>
      <tr>
        <th>⏱️ Durée</th>
        <td>
          <?= htmlspecialchars($d['duree']) ?>
          <span class="badge-type"><?= htmlspecialchars($d['type_duree']) ?></span>
        </td>
      </tr>
      <tr>
        <th>📅 Date demande</th>
        <td><?= htmlspecialchars($d['date_demande'] ?? '—') ?></td>
      </tr>
    </table>
  </div>
</body>
</html>