<?php
require_once __DIR__ . '/../../config.php';
include __DIR__ . '/../Controller/EvenementController.php';

$evenementC = new EvenementController();
$list = $evenementC->listEvenements();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Événements - Héritage de Gaia</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-5" style="color: #006400;">📅 Nos Événements à venir</h2>

    <div class="row">
        <?php foreach ($list as $e): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($e['titre']) ?></h5>
                    <p class="text-muted">
                        📅 <?= htmlspecialchars($e['date']) ?> à <?= htmlspecialchars($e['heure']) ?>
                    </p>
                    <p class="card-text"><?= nl2br(htmlspecialchars($e['description'] ?? '')) ?></p>
                    <span class="badge bg-success"><?= htmlspecialchars($e['type']) ?></span>
                </div>
                <div class="card-footer">
                    <a href="participation/add.php?event_id=<?= $e['id_event'] ?>" 
                       class="btn btn-outline-success w-100">Je participe</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>