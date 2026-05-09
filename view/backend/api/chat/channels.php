<?php
require_once(__DIR__ . '/_db.php');

chat_require_method('GET');

$db = chat_db();

$rows = $db->query("
    SELECT
        c.id,
        c.titre,
        c.description,
        c.statut,
        c.streak_icon,
        c.date_debut,
        c.date_fin,
        COUNT(p.id) AS participants_count
    FROM challenge c
    LEFT JOIN participant p ON p.id_challenge = c.id
    GROUP BY c.id
    ORDER BY c.ordre ASC, c.date_debut DESC, c.id DESC
")->fetchAll(PDO::FETCH_ASSOC) ?: [];

$ensure = $db->prepare("INSERT IGNORE INTO chat_threads (challenge_id) VALUES (:id)");
foreach ($rows as $row) {
    $ensure->execute(['id' => (int)$row['id']]);
}

$channels = array_map(function (array $row): array {
    return [
        'id' => 'defi_' . (int)$row['id'],
        'challenge_id' => (int)$row['id'],
        'icon' => $row['streak_icon'] ?: '🏆',
        'name' => $row['titre'] ?: 'Défi',
        'desc' => $row['description'] ?: '',
        'statut' => $row['statut'] ?: '',
        'date_debut' => $row['date_debut'] ?? null,
        'date_fin' => $row['date_fin'] ?? null,
        'participants_count' => (int)($row['participants_count'] ?? 0),
    ];
}, $rows);

chat_json(['ok' => true, 'channels' => $channels]);
