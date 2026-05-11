<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossiers Médicaux - Export PDF</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --vert: #013220; --sable: #CBBD93; --violet: #BA5BED; --bleu: #77B5FE; }
        body {
            font-family: Arial, sans-serif;
            color: var(--vert);
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--vert);
            padding-bottom: 15px;
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { font-size: 12px; color: #666; }
        .export-date { text-align: right; margin-bottom: 20px; font-size: 12px; color: #999; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: var(--vert);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f0f0; }
        .stats {
            background-color: var(--sable);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-item {
            background: white;
            padding: 12px;
            border-left: 4px solid var(--vert);
            border-radius: 4px;
        }
        .stat-item strong { display: block; color: var(--vert); margin-bottom: 5px; }
        .stat-item span { font-size: 18px; font-weight: bold; color: var(--violet); }
        .print-button {
            background: var(--vert);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .print-button:hover { background: #0a1e1c; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        @media print {
            .print-button { display: none; }
            .export-date { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Imprimer / Exporter en PDF</button>
    
    <div class="header">
        <h1>📋 Dossiers Médicaux</h1>
        <p>SmartNutrition - Rapport d'Export</p>
    </div>
    
    <div class="export-date">Export généré le: <?= date('d/m/Y à H:i:s') ?></div>
    
    <?php
    try {
        $ctrl = new DossierMedicalController();
        $dossiers = $ctrl->list();
        $stats = $ctrl->getStatistics();
        
        if (!empty($dossiers)):
    ?>
    <div class="stats">
        <div class="stat-item">
            <strong>Total Dossiers:</strong>
            <span><?= count($dossiers) ?></span>
        </div>
        <div class="stat-item">
            <strong>IMC Moyen:</strong>
            <span><?= number_format($stats['avg_imc'] ?? 0, 1) ?></span>
        </div>
        <div class="stat-item">
            <strong>Dossiers avec Allergies:</strong>
            <span><?= $stats['allergies_count'] ?? 0 ?></span>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Groupe Sanguin</th>
                <th>Poids (kg)</th>
                <th>Taille (cm)</th>
                <th>IMC</th>
                <th>Allergies</th>
                <th>Maladies</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dossiers as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['id_dossier']) ?></td>
                <td><?= htmlspecialchars($d['groupe_sanguin'] ?? '-') ?></td>
                <td><?= number_format($d['poids'], 1) ?></td>
                <td><?= number_format($d['taille'], 1) ?></td>
                <td><?= number_format($d['imc'], 1) ?></td>
                <td><?= htmlspecialchars(substr($d['allergie'] ?? '-', 0, 50)) ?></td>
                <td><?= htmlspecialchars(substr($d['maladies'] ?? '-', 0, 50)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>❌ Aucun dossier à exporter</p>
    </div>
    <?php endif; 
    } catch (Exception $e) { ?>
    <div class="empty-state" style="color: red;">
        <p>❌ Erreur: <?= htmlspecialchars($e->getMessage()) ?></p>
    </div>
    <?php } ?>
</body>
</html>
