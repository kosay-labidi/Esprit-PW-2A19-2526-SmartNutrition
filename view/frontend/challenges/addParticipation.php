<?php
$challengeId = (int)($_GET['challenge_id'] ?? $_GET['id_challenge'] ?? $_GET['id'] ?? 0);

if ($challengeId > 0) {
    $target = '../dashboard.html?module=challenges&challenge_id=' . $challengeId . '&participer=1&source=direct';
    header('Location: ' . $target);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participation au défi</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #0f2318;
            color: #f2e8cf;
        }
        main {
            width: min(520px, calc(100vw - 32px));
            padding: 28px;
            border: 1px solid rgba(242, 232, 207, .18);
            border-radius: 16px;
            background: rgba(255, 255, 255, .04);
            text-align: center;
        }
        a {
            display: inline-block;
            margin-top: 14px;
            padding: 11px 18px;
            border-radius: 10px;
            color: #fff;
            background: #3a86c4;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
<main>
    <h1>Défi introuvable</h1>
    <p>Le lien de participation ne contient pas l'identifiant du défi.</p>
    <a href="../dashboard.html?module=challenges">Voir les défis</a>
</main>
</body>
</html>
