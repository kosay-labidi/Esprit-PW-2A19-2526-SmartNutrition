<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../Model/Challenge.php');

class ChallengeController {
    private bool $paidSchemaReady = false;

    private function ensurePaidChallengeSchema(): void {
        if ($this->paidSchemaReady) return;
        $db = Config::getConnexion();
        try {
            $cols = $db->query("SHOW COLUMNS FROM challenge")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('est_payant', $cols, true)) {
                $db->exec("ALTER TABLE challenge ADD COLUMN est_payant TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = défi payant, 0 = gratuit' AFTER image");
            }
            if (!in_array('prix', $cols, true)) {
                $db->exec("ALTER TABLE challenge ADD COLUMN prix DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix du défi payant' AFTER est_payant");
            }
        } catch (Exception $e) {
            error_log('Erreur ensurePaidChallengeSchema: ' . $e->getMessage());
        }
        $this->paidSchemaReady = true;
    }

    // ═══════════════════════════════════════════════════════════
    // CRUD DE BASE (existants)
    // ═══════════════════════════════════════════════════════════

    public function addChallenge(Challenge $challenge) {
        $this->ensurePaidChallengeSchema();
        $sql = "INSERT INTO challenge (titre, description, type, objectif, valeur_cible,
                date_debut, date_fin, statut, streak_icon, image, est_payant, prix)
                VALUES (:titre, :description, :type, :objectif, :valeur_cible,
                        :date_debut, :date_fin, :statut, :streak_icon, :image, :est_payant, :prix)";
        $db = Config::getConnexion();
        try {
            $estPayant = (int)$challenge->getEstPayant() === 1 ? 1 : 0;
            $prix = $estPayant ? max(0, (float)$challenge->getPrix()) : 0;
            $query = $db->prepare($sql);
            $query->execute([
                'titre'        => Config::sanitizeInput($challenge->getTitre()),
                'description'  => Config::sanitizeInput($challenge->getDescription()),
                'type'         => Config::sanitizeInput($challenge->getType()),
                'objectif'     => Config::sanitizeInput($challenge->getObjectif()),
                'valeur_cible' => (int)$challenge->getValeurCible(),
                'date_debut'   => $challenge->getDateDebut(),
                'date_fin'     => $challenge->getDateFin(),
                'statut'       => Config::sanitizeInput($challenge->getStatut()),
                'streak_icon'  => Config::sanitizeInput($challenge->getStreakIcon()),
                'image'        => Config::sanitizeInput($challenge->getImage()),
                'est_payant'   => $estPayant,
                'prix'         => $prix
            ]);
            $id = (int)$db->lastInsertId();
            if ($id > 0) {
                $this->ensureChatThreadForChallenge($id);
            }
            return true;
        } catch (Exception $e) {
            error_log('Erreur addChallenge: ' . $e->getMessage());
            return false;
        }
    }

    public function listChallenges($userId = 0) {
        $this->ensurePaidChallengeSchema();
        $sql = "SELECT c.*,
                    COUNT(DISTINCT p.id) AS participants_count,
                    (SELECT COUNT(*) FROM challenge_likes cl WHERE cl.id_challenge = c.id AND cl.id_user = :uid) > 0 AS is_liked
                FROM challenge c
                LEFT JOIN participant p ON p.id_challenge = c.id
                GROUP BY c.id
                ORDER BY c.ordre ASC, c.date_debut DESC, c.id DESC";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['uid' => (int)$userId]);
            return $query->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur listChallenges: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteChallenge($id) {
        $sql = "DELETE FROM challenge WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur deleteChallenge: ' . $e->getMessage());
            return false;
        }
    }

    private function ensureChatThreadForChallenge(int $challengeId): void {
        if ($challengeId <= 0) return;
        $db = Config::getConnexion();
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `chat_threads` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `challenge_id` INT NOT NULL,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_chat_threads_challenge` (`challenge_id`),
                  CONSTRAINT `fk_chat_threads_challenge`
                    FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $q = $db->prepare("INSERT IGNORE INTO chat_threads (challenge_id) VALUES (:id)");
            $q->execute(['id' => $challengeId]);
        } catch (Exception $e) {
            error_log('Erreur ensureChatThreadForChallenge: ' . $e->getMessage());
        }
    }

    public function showChallenge($id) {
        $this->ensurePaidChallengeSchema();
        $sql = "SELECT * FROM challenge WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => (int)$id]);
            return $query->fetch();
        } catch (Exception $e) {
            error_log('Erreur showChallenge: ' . $e->getMessage());
            return null;
        }
    }

    public function updateChallenge($challenge, $id) {
        $this->ensurePaidChallengeSchema();
        $sql = "UPDATE challenge SET titre=:titre, description=:description, type=:type,
                objectif=:objectif, valeur_cible=:valeur_cible, date_debut=:date_debut,
                date_fin=:date_fin, statut=:statut, streak_icon=:streak_icon, image=:image,
                est_payant=:est_payant, prix=:prix
                WHERE id = :id";
        $db = Config::getConnexion();
        try {
            $estPayant = (int)$challenge->getEstPayant() === 1 ? 1 : 0;
            $prix = $estPayant ? max(0, (float)$challenge->getPrix()) : 0;
            $query = $db->prepare($sql);
            $query->execute([
                'titre'        => $challenge->getTitre(),
                'description'  => $challenge->getDescription(),
                'type'         => $challenge->getType(),
                'objectif'     => $challenge->getObjectif(),
                'valeur_cible' => $challenge->getValeurCible(),
                'date_debut'   => $challenge->getDateDebut(),
                'date_fin'     => $challenge->getDateFin(),
                'statut'       => $challenge->getStatut(),
                'streak_icon'  => $challenge->getStreakIcon(),
                'image'        => $challenge->getImage(),
                'est_payant'   => $estPayant,
                'prix'         => $prix,
                'id'           => (int)$id
            ]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateChallenge: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 1 — STATISTIQUES
    // ═══════════════════════════════════════════════════════════

    public function getStatistiques(): array {
        $db = Config::getConnexion();
        try {
            // Totaux globaux
            $row = $db->query("
                SELECT
                    COUNT(*)                                             AS total_challenges,
                    SUM(statut = 'actif')                               AS challenges_actifs,
                    SUM(statut = 'termine')                             AS challenges_termines,
                    SUM(statut = 'en_attente')                         AS challenges_en_attente,
                    SUM(statut = 'accepte')                            AS challenges_acceptes,
                    SUM(statut = 'refuse')                             AS challenges_refuses,
                    COALESCE(SUM(nb_vues),  0)                         AS total_vues,
                    COALESCE(SUM(nb_likes), 0)                         AS total_likes,
                    COALESCE(AVG(nb_vues), 0)                          AS avg_vues,
                    COALESCE(AVG(nb_likes), 0)                         AS avg_likes
                FROM challenge
            ")->fetch();

            // Total participants
            $totalPart = $db->query("SELECT COUNT(*) AS total FROM participant")->fetch();
            $row['total_participants'] = (int)($totalPart['total'] ?? 0);

            // Total steakers (participants avec un engagement > 0 ou ayant gagné un badge)
            $totalSteakers = $db->query("SELECT COUNT(*) AS total FROM participant WHERE engagement > 0")->fetch();
            $row['total_steakers'] = (int)($totalSteakers['total'] ?? 0);

            // Tendance : Nouveaux participants ces 7 derniers jours
            $recentPart = $db->query("SELECT COUNT(*) AS total FROM participant WHERE date_inscription > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
            $row['recent_participants_7d'] = (int)($recentPart['total'] ?? 0);

            // Taux de complétion moyen (si objectif >= valeur_cible)
            $completion = $db->query("
                SELECT AVG(p.objectif / NULLIF(c.valeur_cible, 0)) * 100 as avg_completion
                FROM participant p
                JOIN challenge c ON c.id = p.id_challenge
            ")->fetch();
            $row['avg_completion_rate'] = round((float)($completion['avg_completion'] ?? 0), 2);

            // Top 3 défis par participants
            $top3 = $db->query("
                SELECT c.id, c.titre, c.streak_icon, c.statut, c.nb_vues, c.nb_likes,
                       COUNT(p.id) AS nb_participants,
                       COALESCE(AVG(p.objectif / NULLIF(c.valeur_cible, 0)) * 100, 0) as completion_rate
                FROM challenge c
                LEFT JOIN participant p ON p.id_challenge = c.id
                GROUP BY c.id, c.titre, c.streak_icon, c.statut, c.nb_vues, c.nb_likes
                ORDER BY nb_participants DESC
                LIMIT 3
            ")->fetchAll();
            $row['top3_challenges'] = $top3;

            // Top 5 participants les plus engagés
            $top5 = $db->query("
                SELECT p.id, p.nom, p.email, p.engagement, p.objectif,
                       c.titre AS challenge_titre, c.streak_icon AS challenge_icon
                FROM participant p
                LEFT JOIN challenge c ON c.id = p.id_challenge
                ORDER BY p.engagement DESC
                LIMIT 5
            ")->fetchAll();
            $row['top5_participants'] = $top5;

            // Répartition par type de défi
            $byType = $db->query("
                SELECT type, COUNT(*) AS total
                FROM challenge
                GROUP BY type
                ORDER BY total DESC
            ")->fetchAll();
            $row['by_type'] = $byType;

            return $row;
        } catch (Exception $e) {
            error_log('Erreur getStatistiques: ' . $e->getMessage());
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 2 — STATUT ACCEPTÉ / REFUSÉ
    // ═══════════════════════════════════════════════════════════

    public function updateStatut(int $id, string $statut): bool {
        $allowed = ['en_attente', 'actif', 'termine', 'accepte', 'refuse'];
        if (!in_array($statut, $allowed, true)) return false;

        $db = Config::getConnexion();
        try {
            $q = $db->prepare("UPDATE challenge SET statut = :statut WHERE id = :id");
            $q->execute(['statut' => $statut, 'id' => $id]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateStatut: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 3 — NB_VUES / NB_LIKES
    // ═══════════════════════════════════════════════════════════

    public function incrementVues(int $id): void {
        $db = Config::getConnexion();
        try {
            $db->prepare("UPDATE challenge SET nb_vues = nb_vues + 1 WHERE id = :id")
               ->execute(['id' => $id]);
        } catch (Exception $e) {
            error_log('Erreur incrementVues: ' . $e->getMessage());
        }
    }

    /**
     * Toggle like — retourne ['liked' => bool, 'count' => int]
     * Utilise la table challenge_likes pour garantir 1 like par user.
     */
    public function toggleLike(int $id_challenge, int $id_user): array {
        $db = Config::getConnexion();
        try {
            // Vérifier si le like existe déjà
            $check = $db->prepare(
                "SELECT id FROM challenge_likes WHERE id_challenge=:c AND id_user=:u LIMIT 1"
            );
            $check->execute(['c' => $id_challenge, 'u' => $id_user]);
            $existing = $check->fetch();

            if ($existing) {
                // Retirer le like
                $db->prepare("DELETE FROM challenge_likes WHERE id_challenge=:c AND id_user=:u")
                   ->execute(['c' => $id_challenge, 'u' => $id_user]);
                // Décrémenter (trigger ou ici)
                $db->prepare("UPDATE challenge SET nb_likes = GREATEST(nb_likes-1,0) WHERE id=:id")
                   ->execute(['id' => $id_challenge]);
                $liked = false;
            } else {
                // Ajouter le like
                $db->prepare("INSERT INTO challenge_likes (id_challenge, id_user) VALUES (:c,:u)")
                   ->execute(['c' => $id_challenge, 'u' => $id_user]);
                $db->prepare("UPDATE challenge SET nb_likes = nb_likes+1 WHERE id=:id")
                   ->execute(['id' => $id_challenge]);
                $liked = true;
            }

            // Retourner le nouveau total
            $total = $db->prepare("SELECT nb_likes FROM challenge WHERE id=:id");
            $total->execute(['id' => $id_challenge]);
            $count = (int)($total->fetch()['nb_likes'] ?? 0);

            return ['liked' => $liked, 'count' => $count];
        } catch (Exception $e) {
            error_log('Erreur toggleLike: ' . $e->getMessage());
            return ['liked' => false, 'count' => 0];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 4 — DRAG & DROP (ordre)
    // ═══════════════════════════════════════════════════════════

    /**
     * $ordreData = [['id' => 3, 'ordre' => 0], ['id' => 1, 'ordre' => 1], ...]
     */
    public function updateOrdre(array $ordreData): bool {
        $db = Config::getConnexion();
        try {
            $q = $db->prepare("UPDATE challenge SET ordre = :ordre WHERE id = :id");
            foreach ($ordreData as $item) {
                $q->execute([
                    'ordre' => (int)($item['ordre'] ?? 0),
                    'id'    => (int)($item['id']    ?? 0)
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log('Erreur updateOrdre: ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 5 — NOTIFICATIONS EMAIL
    // ═══════════════════════════════════════════════════════════

    public function notifierParticipants(int $id_challenge, string $sujet, string $message): array {
        $db = Config::getConnexion();
        try {
            $q = $db->prepare("
                SELECT p.nom, p.email, c.titre
                FROM participant p
                JOIN challenge c ON c.id = p.id_challenge
                WHERE p.id_challenge = :id AND p.notifications = 1
            ");
            $q->execute(['id' => $id_challenge]);
            $participants = $q->fetchAll();

            $sent = 0; $failed = 0;

            foreach ($participants as $p) {
                $headers = implode("\r\n", [
                    'From: GaiaLumen <noreply@gaialumen.com>',
                    'Content-Type: text/html; charset=UTF-8',
                    'MIME-Version: 1.0'
                ]);

                $htmlMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                $htmlMessage = nl2br($htmlMessage);

                $body = "
                <html><body style='font-family:Arial,sans-serif;background:#0f0f1a;color:#e2e8f0;padding:20px;'>
                  <div style='max-width:600px;margin:auto;background:#1e1e2e;border-radius:16px;
                              border:1px solid #6366f1;overflow:hidden;'>
                    <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:24px;text-align:center;'>
                      <h2 style='margin:0;color:#fff;font-size:1.5rem;'>
                        🏆 {$p['titre']}
                      </h2>
                    </div>
                    <div style='padding:24px;'>
                      <p style='color:#94a3b8;margin-bottom:16px;'>
                        Bonjour <strong style='color:#e2e8f0;'>{$p['nom']}</strong>,
                      </p>
                      <div style='background:#2d2d44;border-radius:10px;padding:16px;
                                  border-left:4px solid #6366f1;margin-bottom:20px;'>
                        {$htmlMessage}
                      </div>
                      <hr style='border:none;border-top:1px solid #3d3d5c;margin:20px 0;'>
                      <p style='text-align:center;color:#6366f1;font-size:0.8rem;margin:0;'>
                        GaiaLumen — Plateforme de Défis Collaboratifs
                      </p>
                    </div>
                  </div>
                </body></html>";

                if (mail($p['email'], $sujet, $body, $headers)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }

            return [
                'success' => true,
                'sent'    => $sent,
                'failed'  => $failed,
                'total'   => count($participants)
            ];
        } catch (Exception $e) {
            error_log('Erreur notifierParticipants: ' . $e->getMessage());
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'total' => 0];
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 6 — EXPORT CSV
    // ═══════════════════════════════════════════════════════════

    public function exportCSV(): void {
        $this->ensurePaidChallengeSchema();
        $data = $this->listChallenges();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="defis_gaialumen_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel
        fwrite($output, "\xEF\xBB\xBF");

        // En-têtes colonnes
        fputcsv($output, [
            'ID', 'Titre', 'Type', 'Objectif', 'Valeur Cible (%)',
            'Date Début', 'Date Fin', 'Statut',
            'Payant', 'Prix', 'Nb Participants', 'Nb Vues', 'Nb Likes', 'Ordre'
        ], ';');

        foreach ($data as $row) {
            fputcsv($output, [
                $row['id']                ?? '',
                $row['titre']             ?? '',
                $row['type']              ?? '',
                $row['objectif']          ?? '',
                $row['valeur_cible']      ?? 0,
                $row['date_debut']        ?? '',
                $row['date_fin']          ?? '',
                $row['statut']            ?? '',
                ((int)($row['est_payant'] ?? 0) === 1) ? 'Oui' : 'Non',
                $row['prix']              ?? 0,
                $row['participants_count']?? 0,
                $row['nb_vues']           ?? 0,
                $row['nb_likes']          ?? 0,
                $row['ordre']             ?? 0,
            ], ';');
        }

        fclose($output);
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    // MÉTIER SIMPLE 7 — EXPORT PDF (HTML → PDF via HTML pur)
    // ═══════════════════════════════════════════════════════════

    public function exportPDF(): void {
        $data  = $this->listChallenges();
        $stats = $this->getStatistiques();
        $date  = date('d/m/Y à H:i');

        // Statut → couleur
        $badgeColors = [
            'actif'      => '#22c55e',
            'termine'    => '#6b7280',
            'en_attente' => '#f59e0b',
            'accepte'    => '#3b82f6',
            'refuse'     => '#ef4444',
        ];

        $rows = '';
        foreach ($data as $i => $c) {
            $color  = $badgeColors[$c['statut'] ?? ''] ?? '#94a3b8';
            $rows  .= "<tr style='background:" . ($i % 2 === 0 ? '#1e1e2e' : '#252535') . ";'>
                <td style='padding:8px 12px;'>" . htmlspecialchars($c['streak_icon'] ?? '') . " " . htmlspecialchars($c['titre'] ?? '') . "</td>
                <td style='padding:8px 12px;'>" . htmlspecialchars($c['type'] ?? '') . "</td>
                <td style='padding:8px 12px;'>" . (int)($c['valeur_cible'] ?? 0) . "%</td>
                <td style='padding:8px 12px;'>
                  <span style='background:{$color};color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;'>
                    " . htmlspecialchars($c['statut'] ?? '') . "
                  </span>
                </td>
                <td style='padding:8px 12px;text-align:center;'>" . (int)($c['participants_count'] ?? 0) . "</td>
                <td style='padding:8px 12px;'>" . htmlspecialchars($c['date_debut'] ?? '') . "</td>
                <td style='padding:8px 12px;'>" . htmlspecialchars($c['date_fin'] ?? '') . "</td>
                <td style='padding:8px 12px;text-align:center;'>👁 " . (int)($c['nb_vues'] ?? 0) . " ❤️ " . (int)($c['nb_likes'] ?? 0) . "</td>
            </tr>";
        }

        $html = "<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='utf-8'>
<title>Rapport Défis — GaiaLumen</title>
<style>
  body  { margin:0; font-family: Arial, sans-serif; background:#0f0f1a; color:#e2e8f0; }
  .hdr  { background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:30px 40px; display:flex; justify-content:space-between; align-items:center; }
  .hdr h1 { margin:0; font-size:24px; color:#fff; }
  .hdr p  { margin:0; color:rgba(255,255,255,0.8); font-size:13px; }
  .stats  { display:flex; gap:16px; padding:24px 40px; background:#1a1a2e; }
  .stat   { flex:1; background:#2d2d44; border-radius:10px; padding:16px; text-align:center; border-left:4px solid #6366f1; }
  .stat b { display:block; font-size:2rem; color:#818cf8; }
  .stat s { font-size:11px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; }
  .tbl    { padding:24px 40px; }
  table   { width:100%; border-collapse:collapse; }
  thead th { background:#6366f1; color:#fff; padding:10px 12px; text-align:left; font-size:12px; text-transform:uppercase; }
  td      { font-size:13px; color:#cbd5e1; border-bottom:1px solid rgba(255,255,255,0.05); }
  .ftr    { text-align:center; padding:20px; color:#6366f1; font-size:12px; border-top:1px solid #3d3d5c; }
  @media print { body { background:#fff; color:#000; } }
</style>
</head>
<body>
  <div class='hdr'>
    <div>
      <h1>🏆 Rapport des Défis</h1>
      <p>GaiaLumen — Gestion des Défis Collaboratifs</p>
    </div>
    <div style='text-align:right; color:rgba(255,255,255,0.8); font-size:13px;'>
      Généré le {$date}<br>
      " . count($data) . " défis au total
    </div>
  </div>

  <div class='stats'>
    <div class='stat'>
      <b>" . (int)($stats['total_challenges'] ?? 0) . "</b>
      <s>Total défis</s>
    </div>
    <div class='stat'>
      <b>" . (int)($stats['challenges_actifs'] ?? 0) . "</b>
      <s>Actifs</s>
    </div>
    <div class='stat'>
      <b>" . (int)($stats['total_participants'] ?? 0) . "</b>
      <s>Participants</s>
    </div>
    <div class='stat'>
      <b>" . (int)($stats['total_vues'] ?? 0) . "</b>
      <s>Vues</s>
    </div>
    <div class='stat'>
      <b>" . (int)($stats['total_likes'] ?? 0) . "</b>
      <s>Likes</s>
    </div>
  </div>

  <div class='tbl'>
    <table>
      <thead>
        <tr>
          <th>Défi</th><th>Type</th><th>Cible</th><th>Statut</th>
          <th>Participants</th><th>Début</th><th>Fin</th><th>Vues / Likes</th>
        </tr>
      </thead>
      <tbody>
        {$rows}
      </tbody>
    </table>
  </div>

  <div class='ftr'>
    GaiaLumen · Rapport exporté le {$date} · " . count($data) . " défis
  </div>

  <script>window.onload = function(){ window.print(); }</script>
</body>
</html>";

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
?>
