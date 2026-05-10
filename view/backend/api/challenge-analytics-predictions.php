<?php
/**
 * Advanced analytics and prediction API for GaiaLumen challenges.
 *
 * GET parameters:
 * - challenge_id: optional, limit detailed challenge output to one challenge
 * - limit: optional, max rows for rankings/recommendations (default 10)
 *
 * Output sections:
 * - global: global historical statistics
 * - challenges: detailed statistics and predictions per challenge
 * - participants: participant behavior analysis
 * - predictions: success probability, future engagement, forecast ranking
 * - recommendations: decision-support actions for admins
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once(__DIR__ . '/../../../config.php');

function analytics_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function analytics_float($value, int $precision = 2): float {
    return round((float)$value, $precision);
}

function analytics_clamp(float $value, float $min, float $max): float {
    return max($min, min($max, $value));
}

function analytics_percent(float $value): float {
    return analytics_float(analytics_clamp($value, 0, 100));
}

function analytics_engagement_percent($value): float {
    $value = (float)$value;
    if ($value > 0 && $value <= 1) {
        $value *= 100;
    }
    return analytics_percent($value);
}

function analytics_days_between(?string $start, ?string $end): int {
    if (!$start || !$end) return 1;
    $a = strtotime($start);
    $b = strtotime($end);
    if (!$a || !$b || $b <= $a) return 1;
    return max(1, (int)ceil(($b - $a) / 86400));
}

function analytics_days_elapsed(?string $start): int {
    if (!$start || !strtotime($start)) return 0;
    return max(0, (int)floor((time() - strtotime($start)) / 86400));
}

function analytics_status_factor(string $status): float {
    $status = strtolower(trim($status));
    if ($status === 'actif' || $status === 'accepte') return 1.0;
    if ($status === 'en_attente') return 0.72;
    if ($status === 'termine') return 0.88;
    if ($status === 'refuse') return 0.25;
    return 0.65;
}

function analytics_success_label(float $probability): string {
    if ($probability >= 78) return 'forte';
    if ($probability >= 55) return 'moyenne';
    if ($probability >= 35) return 'fragile';
    return 'faible';
}

function analytics_risk_label(float $risk): string {
    if ($risk >= 70) return 'critique';
    if ($risk >= 45) return 'a_surveille';
    return 'faible';
}

function analytics_participant_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $out .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }
    return strtoupper($out ?: 'P');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    analytics_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$challengeId = max(0, (int)($_GET['challenge_id'] ?? 0));
$limit = max(3, min(50, (int)($_GET['limit'] ?? 10)));

try {
    $db = Config::getConnexion();

    $challengeWhere = $challengeId > 0 ? 'WHERE c.id = :challenge_id' : '';
    $challengeSql = "
        SELECT
            c.id, c.titre, c.description, c.type, c.objectif, c.valeur_cible,
            c.date_debut, c.date_fin, c.statut, c.streak_icon, c.image,
            COALESCE(c.nb_vues, 0) AS nb_vues,
            COALESCE(c.nb_likes, 0) AS nb_likes,
            COALESCE(c.ordre, 0) AS ordre,
            c.created_at,
            COUNT(p.id) AS participants_count,
            COALESCE(AVG(p.objectif), 0) AS avg_progress,
            COALESCE(AVG(p.engagement), 0) AS avg_engagement,
            COALESCE(AVG(p.notifications), 0) * 100 AS notification_rate,
            SUM(CASE WHEN p.objectif >= c.valeur_cible THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN p.motivation IS NOT NULL AND CHAR_LENGTH(TRIM(p.motivation)) >= 10 THEN 1 ELSE 0 END) AS motivated_count,
            SUM(CASE WHEN p.action IS NOT NULL AND CHAR_LENGTH(TRIM(p.action)) >= 5 THEN 1 ELSE 0 END) AS action_count,
            MAX(p.date_inscription) AS last_participation_at
        FROM challenge c
        LEFT JOIN participant p ON p.id_challenge = c.id
        $challengeWhere
        GROUP BY
            c.id, c.titre, c.description, c.type, c.objectif, c.valeur_cible,
            c.date_debut, c.date_fin, c.statut, c.streak_icon, c.image,
            c.nb_vues, c.nb_likes, c.ordre, c.created_at
        ORDER BY c.ordre ASC, c.date_debut DESC, c.id DESC
    ";
    $challengeStmt = $db->prepare($challengeSql);
    if ($challengeId > 0) {
        $challengeStmt->bindValue(':challenge_id', $challengeId, PDO::PARAM_INT);
    }
    $challengeStmt->execute();
    $challengeRows = $challengeStmt->fetchAll();

    $participantRows = $db->query("
        SELECT
            p.id, p.nom, p.email, p.id_challenge, p.objectif, p.engagement,
            p.notifications, p.motivation, p.action, p.date_inscription,
            c.titre AS challenge_titre, c.valeur_cible, c.statut, c.type, c.streak_icon
        FROM participant p
        LEFT JOIN challenge c ON c.id = p.id_challenge
        ORDER BY p.date_inscription DESC, p.id DESC
    ")->fetchAll();

    $totalChallenges = count($challengeRows);
    $allChallengeCount = (int)$db->query("SELECT COUNT(*) FROM challenge")->fetchColumn();
    $allParticipantCount = (int)$db->query("SELECT COUNT(*) FROM participant")->fetchColumn();

    $maxParticipants = 1;
    $maxViews = 1;
    $maxLikes = 1;
    foreach ($challengeRows as $row) {
        $maxParticipants = max($maxParticipants, (int)$row['participants_count']);
        $maxViews = max($maxViews, (int)$row['nb_vues']);
        $maxLikes = max($maxLikes, (int)$row['nb_likes']);
    }

    $challenges = [];
    $successProbabilities = [];
    $engagementForecasts = [];
    $recommendations = [];

    foreach ($challengeRows as $row) {
        $participants = (int)$row['participants_count'];
        $target = max(1, (int)$row['valeur_cible']);
        $avgProgress = analytics_percent((float)$row['avg_progress']);
        $avgEngagement = analytics_engagement_percent($row['avg_engagement']);
        $notificationRate = analytics_percent((float)$row['notification_rate']);
        $successCount = (int)$row['success_count'];
        $successRate = $participants > 0 ? analytics_percent(($successCount / $participants) * 100) : 0.0;
        $motivationRate = $participants > 0 ? analytics_percent(((int)$row['motivated_count'] / $participants) * 100) : 0.0;
        $actionRate = $participants > 0 ? analytics_percent(((int)$row['action_count'] / $participants) * 100) : 0.0;
        $durationDays = analytics_days_between($row['date_debut'], $row['date_fin']);
        $elapsedDays = min($durationDays, analytics_days_elapsed($row['date_debut']));
        $daysLeft = max(0, $durationDays - $elapsedDays);
        $expectedProgress = analytics_percent(($elapsedDays / max(1, $durationDays)) * 100);
        $progressVsExpected = analytics_float($avgProgress - $expectedProgress);
        $participantIndex = ($participants / $maxParticipants) * 100;
        $viewIndex = ((int)$row['nb_vues'] / $maxViews) * 100;
        $likeIndex = ((int)$row['nb_likes'] / $maxLikes) * 100;
        $conversionRate = ((int)$row['nb_vues'] > 0) ? analytics_percent(($participants / (int)$row['nb_vues']) * 100) : 0.0;
        $likeRate = ((int)$row['nb_vues'] > 0) ? analytics_percent(((int)$row['nb_likes'] / (int)$row['nb_vues']) * 100) : 0.0;
        $statusFactor = analytics_status_factor((string)$row['statut']);

        $popularityScore = analytics_percent(
            $participantIndex * 0.45
            + $viewIndex * 0.25
            + $likeIndex * 0.20
            + $conversionRate * 0.10
        );

        $paceScore = analytics_percent(50 + $progressVsExpected);
        $completionSignal = $successRate * 0.34 + min(100, ($avgProgress / $target) * 100) * 0.24;
        $engagementSignal = $avgEngagement * 0.12 + $notificationRate * 0.08 + $motivationRate * 0.07 + $actionRate * 0.06;
        $marketSignal = $popularityScore * 0.07 + $paceScore * 0.02;
        $successProbability = analytics_percent(($completionSignal + $engagementSignal + $marketSignal) * $statusFactor);

        $futureEngagement = analytics_percent(
            $popularityScore * 0.30
            + $avgProgress * 0.22
            + $avgEngagement * 0.18
            + $notificationRate * 0.12
            + $motivationRate * 0.10
            + $actionRate * 0.08
        );
        $predictedParticipants30d = (int)round($participants + max(0, ($futureEngagement / 100) * max(2, $participants + 1)));
        $riskScore = analytics_percent(100 - $successProbability + max(0, -$progressVsExpected) * 0.35);

        $challengeData = [
            'id' => (int)$row['id'],
            'titre' => $row['titre'],
            'type' => $row['type'],
            'objectif' => $row['objectif'],
            'streak_icon' => $row['streak_icon'],
            'statut' => $row['statut'],
            'duration_days' => $durationDays,
            'days_elapsed' => $elapsedDays,
            'days_left' => $daysLeft,
            'participants_count' => $participants,
            'views' => (int)$row['nb_vues'],
            'likes' => (int)$row['nb_likes'],
            'target_value' => $target,
            'avg_progress' => $avgProgress,
            'expected_progress' => $expectedProgress,
            'progress_vs_expected' => $progressVsExpected,
            'success_count' => $successCount,
            'success_rate' => $successRate,
            'notification_rate' => $notificationRate,
            'motivation_rate' => $motivationRate,
            'action_rate' => $actionRate,
            'conversion_rate' => $conversionRate,
            'like_rate' => $likeRate,
            'popularity_score' => $popularityScore,
            'prediction' => [
                'success_probability' => $successProbability,
                'success_level' => analytics_success_label($successProbability),
                'future_engagement_score' => $futureEngagement,
                'predicted_participants_30d' => $predictedParticipants30d,
                'risk_score' => $riskScore,
                'risk_level' => analytics_risk_label($riskScore),
            ],
        ];

        $challenges[] = $challengeData;
        $successProbabilities[] = $successProbability;
        $engagementForecasts[] = $futureEngagement;

        if ($riskScore >= 45) {
            $recommendations[] = [
                'type' => 'challenge_risk',
                'priority' => $riskScore >= 70 ? 'haute' : 'moyenne',
                'challenge_id' => (int)$row['id'],
                'title' => 'Renforcer le défi: ' . $row['titre'],
                'reason' => 'Risque ' . analytics_risk_label($riskScore) . ', probabilité de réussite ' . $successProbability . '%.',
                'action' => 'Envoyer une relance ciblée, publier une astuce dans le chat et simplifier la prochaine action.',
            ];
        }
    }

    $participantGroups = [];
    foreach ($participantRows as $row) {
        $key = strtolower(trim((string)$row['email']));
        if ($key === '') $key = 'participant-' . (int)$row['id'];
        if (!isset($participantGroups[$key])) {
            $participantGroups[$key] = [
                'nom' => $row['nom'],
                'email' => $row['email'],
                'participations' => 0,
                'progress_sum' => 0,
                'engagement_sum' => 0,
                'success_count' => 0,
                'notifications_count' => 0,
                'motivation_count' => 0,
                'action_count' => 0,
                'last_activity' => $row['date_inscription'],
                'challenge_titles' => [],
            ];
        }
        $g =& $participantGroups[$key];
        $progress = max(0, min(100, (int)$row['objectif']));
        $target = max(1, (int)$row['valeur_cible']);
        $g['participations']++;
        $g['progress_sum'] += $progress;
        $g['engagement_sum'] += analytics_engagement_percent($row['engagement']);
        if ($progress >= $target) $g['success_count']++;
        if ((int)$row['notifications'] === 1) $g['notifications_count']++;
        if (strlen(trim((string)$row['motivation'])) >= 10) $g['motivation_count']++;
        if (strlen(trim((string)$row['action'])) >= 5) $g['action_count']++;
        if (strtotime((string)$row['date_inscription']) > strtotime((string)$g['last_activity'])) {
            $g['last_activity'] = $row['date_inscription'];
        }
        if (!empty($row['challenge_titre'])) $g['challenge_titles'][] = $row['challenge_titre'];
        unset($g);
    }

    $participants = [];
    foreach ($participantGroups as $group) {
        $n = max(1, (int)$group['participations']);
        $avgProgress = analytics_percent($group['progress_sum'] / $n);
        $avgEngagement = analytics_percent($group['engagement_sum'] / $n);
        $successRate = analytics_percent(($group['success_count'] / $n) * 100);
        $activityFrequency = analytics_percent(min(100, $n * 20));
        $qualitySignal = analytics_percent(
            ($group['motivation_count'] / $n) * 35
            + ($group['action_count'] / $n) * 35
            + ($group['notifications_count'] / $n) * 30
        );
        $score = analytics_percent(
            $avgProgress * 0.36
            + $successRate * 0.24
            + $avgEngagement * 0.16
            + $activityFrequency * 0.14
            + $qualitySignal * 0.10
        );
        $futureEngagement = analytics_percent($score * 0.72 + $activityFrequency * 0.18 + $qualitySignal * 0.10);

        $participants[] = [
            'nom' => $group['nom'],
            'email' => $group['email'],
            'initials' => analytics_participant_initials((string)$group['nom']),
            'participations_count' => $n,
            'avg_progress' => $avgProgress,
            'avg_engagement' => $avgEngagement,
            'success_count' => (int)$group['success_count'],
            'success_rate' => $successRate,
            'activity_frequency_score' => $activityFrequency,
            'quality_signal' => $qualitySignal,
            'performance_score' => $score,
            'predicted_future_engagement' => $futureEngagement,
            'last_activity' => $group['last_activity'],
            'challenge_titles' => array_values(array_unique(array_slice($group['challenge_titles'], 0, 5))),
        ];
    }

    usort($participants, fn($a, $b) => ($b['performance_score'] <=> $a['performance_score']) ?: strcmp($a['nom'], $b['nom']));
    foreach ($participants as $i => &$p) {
        $p['predicted_rank'] = $i + 1;
    }
    unset($p);

    usort($challenges, fn($a, $b) => $b['prediction']['success_probability'] <=> $a['prediction']['success_probability']);
    $likelySuccess = array_slice($challenges, 0, $limit);
    $atRisk = array_values(array_filter($challenges, fn($c) => $c['prediction']['risk_score'] >= 45));
    usort($atRisk, fn($a, $b) => $b['prediction']['risk_score'] <=> $a['prediction']['risk_score']);

    $globalSuccessRate = $allParticipantCount > 0
        ? analytics_percent(array_sum(array_map(fn($c) => $c['success_count'], $challenges)) / $allParticipantCount * 100)
        : 0.0;
    $globalAvgSuccessPrediction = count($successProbabilities) > 0
        ? analytics_percent(array_sum($successProbabilities) / count($successProbabilities))
        : 0.0;
    $globalAvgEngagementPrediction = count($engagementForecasts) > 0
        ? analytics_percent(array_sum($engagementForecasts) / count($engagementForecasts))
        : 0.0;

    $byStatus = $db->query("SELECT statut, COUNT(*) AS total FROM challenge GROUP BY statut ORDER BY total DESC")->fetchAll();
    $byType = $db->query("SELECT type, COUNT(*) AS total FROM challenge GROUP BY type ORDER BY total DESC")->fetchAll();

    // Statistiques de paiement
    $paymentStats = [
        'total_revenue' => 0.0,
        'by_status' => [],
        'by_method' => []
    ];
    try {
        $paymentStats['total_revenue'] = (float)$db->query("SELECT SUM(montant) FROM paiement_defi WHERE statut = 'paye'")->fetchColumn();
        $paymentStats['by_status'] = $db->query("SELECT statut, COUNT(*) AS total, SUM(montant) AS revenue FROM paiement_defi GROUP BY statut")->fetchAll();
        $paymentStats['by_method'] = $db->query("SELECT methode, COUNT(*) AS total, SUM(montant) AS revenue FROM paiement_defi WHERE statut = 'paye' GROUP BY methode")->fetchAll();
    } catch (Exception $e) {
        error_log('Erreur stats paiement: ' . $e->getMessage());
    }

    if ($globalAvgSuccessPrediction < 50) {
        $recommendations[] = [
            'type' => 'global_optimization',
            'priority' => 'haute',
            'title' => 'Améliorer la réussite globale',
            'reason' => 'La probabilité moyenne de réussite est de ' . $globalAvgSuccessPrediction . '%.',
            'action' => 'Prioriser les défis courts, clarifier les actions et activer des relances automatiques.',
        ];
    }

    analytics_json([
        'ok' => true,
        'generated_at' => date('c'),
        'methodology' => [
            'success_rate' => 'participant.objectif >= challenge.valeur_cible',
            'popularity_score' => 'participants, vues, likes et conversion vues -> participants',
            'success_probability' => 'score heuristique combinant réussite historique, progression, engagement, notifications, motivation, actions et statut',
            'future_engagement' => 'prévision basée sur popularité, progression, engagement et qualité comportementale',
            'payments' => 'données financières réelles issues de la table paiement_defi',
        ],
        'global' => [
            'total_challenges_scope' => $totalChallenges,
            'total_challenges_all' => $allChallengeCount,
            'total_participants_all' => $allParticipantCount,
            'unique_participants' => count($participants),
            'global_success_rate' => $globalSuccessRate,
            'avg_success_probability' => $globalAvgSuccessPrediction,
            'avg_future_engagement' => $globalAvgEngagementPrediction,
            'by_status' => $byStatus,
            'by_type' => $byType,
            'payment_stats' => $paymentStats,
        ],
        'challenges' => array_values($challenges),
        'participants' => array_slice($participants, 0, $limit),
        'predictions' => [
            'likely_success_challenges' => $likelySuccess,
            'at_risk_challenges' => array_slice($atRisk, 0, $limit),
            'forecast_ranking' => array_slice($participants, 0, $limit),
        ],
        'recommendations' => array_slice($recommendations, 0, $limit),
    ]);
} catch (Throwable $e) {
    error_log('challenge-analytics-predictions: ' . $e->getMessage());
    analytics_json(['ok' => false, 'error' => 'Erreur serveur analytics'], 500);
}
