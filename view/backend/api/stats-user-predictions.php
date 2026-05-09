<?php
/**
 * statsUser prediction API.
 *
 * Predicts user-level challenge statistics from participant history:
 * - engagement forecast
 * - completion probability
 * - churn/drop-off risk
 * - recommended admin action
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once(__DIR__ . '/../../../config.php');

function su_json(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function su_clamp(float $value, float $min = 0, float $max = 100): float {
    return max($min, min($max, $value));
}

function su_percent(float $value): float {
    return round(su_clamp($value), 2);
}

function su_days_since(?string $date): int {
    if (!$date || !strtotime($date)) return 999;
    return max(0, (int)floor((time() - strtotime($date)) / 86400));
}

function su_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $out .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }
    return strtoupper($out ?: 'U');
}

function su_risk_label(float $risk): string {
    if ($risk >= 70) return 'critique';
    if ($risk >= 45) return 'à surveiller';
    return 'faible';
}

function su_action(float $risk, float $completion, float $engagement): string {
    if ($risk >= 70) return 'Envoyer une relance personnalisée et proposer un objectif plus simple.';
    if ($completion < 45) return 'Recommander un défi court avec une action quotidienne mesurable.';
    if ($engagement >= 75) return 'Proposer un rôle d’ambassadeur ou un défi avancé.';
    return 'Envoyer un encouragement et une astuce adaptée au dernier défi rejoint.';
}

$limit = max(3, min(100, (int)($_GET['limit'] ?? 20)));
$emailFilter = strtolower(trim((string)($_GET['email'] ?? '')));

try {
    $db = Config::getConnexion();

    $sql = "
        SELECT
            p.id,
            p.nom,
            p.email,
            p.objectif AS participant_progress,
            p.engagement,
            p.notifications,
            p.motivation,
            p.action,
            p.date_inscription,
            c.id AS challenge_id,
            c.titre AS challenge_titre,
            c.type,
            c.objectif AS challenge_objectif,
            c.valeur_cible,
            c.statut,
            c.date_debut,
            c.date_fin,
            c.nb_vues,
            c.nb_likes
        FROM participant p
        JOIN challenge c ON c.id = p.id_challenge
        " . ($emailFilter !== '' ? "WHERE LOWER(p.email) = :email" : "") . "
        ORDER BY p.date_inscription DESC, p.id DESC
    ";
    $q = $db->prepare($sql);
    if ($emailFilter !== '') $q->bindValue(':email', $emailFilter);
    $q->execute();
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $groups = [];
    foreach ($rows as $row) {
        $email = strtolower(trim((string)$row['email']));
        if ($email === '') $email = 'participant-' . (int)$row['id'];
        if (!isset($groups[$email])) {
            $groups[$email] = [
                'nom' => (string)$row['nom'],
                'email' => $email,
                'rows' => [],
            ];
        }
        $groups[$email]['rows'][] = $row;
    }

    $users = [];
    foreach ($groups as $group) {
        $items = $group['rows'];
        $n = max(1, count($items));
        $progressSum = 0.0;
        $engagementSum = 0.0;
        $completionCount = 0;
        $activeCount = 0;
        $finishedCount = 0;
        $notifCount = 0;
        $qualityCount = 0;
        $viewSignal = 0.0;
        $likeSignal = 0.0;
        $types = [];
        $lastActivity = null;

        foreach ($items as $row) {
            $target = max(1, (int)$row['valeur_cible']);
            $progress = su_percent(((float)$row['participant_progress'] / $target) * 100);
            $engagement = (float)$row['engagement'];
            if ($engagement > 0 && $engagement <= 1) $engagement *= 100;
            $engagement = su_percent($engagement);

            $progressSum += $progress;
            $engagementSum += $engagement;
            if ($progress >= 100 || (int)$row['participant_progress'] >= $target) $completionCount++;
            if (in_array((string)$row['statut'], ['actif', 'accepte'], true)) $activeCount++;
            if ((string)$row['statut'] === 'termine') $finishedCount++;
            if ((int)$row['notifications'] === 1) $notifCount++;
            if (strlen(trim((string)$row['motivation'])) >= 10 || strlen(trim((string)$row['action'])) >= 5) $qualityCount++;
            $viewSignal += min(100, ((int)$row['nb_vues']) / 10);
            $likeSignal += min(100, ((int)$row['nb_likes']) * 4);
            $types[] = (string)$row['type'];
            if ($lastActivity === null || strtotime((string)$row['date_inscription']) > strtotime((string)$lastActivity)) {
                $lastActivity = (string)$row['date_inscription'];
            }
        }

        $avgProgress = su_percent($progressSum / $n);
        $avgEngagement = su_percent($engagementSum / $n);
        $completionRate = su_percent(($completionCount / $n) * 100);
        $notificationRate = su_percent(($notifCount / $n) * 100);
        $qualityRate = su_percent(($qualityCount / $n) * 100);
        $activityFrequency = su_percent(min(100, $n * 18));
        $daysInactive = su_days_since($lastActivity);
        $recencyScore = su_percent(100 - min(100, $daysInactive * 4));
        $popularityAffinity = su_percent(($viewSignal / $n) * 0.45 + ($likeSignal / $n) * 0.55);

        $engagementForecast = su_percent(
            $avgEngagement * 0.28
            + $avgProgress * 0.20
            + $completionRate * 0.16
            + $activityFrequency * 0.16
            + $qualityRate * 0.10
            + $recencyScore * 0.10
        );

        $completionProbability = su_percent(
            $completionRate * 0.34
            + $avgProgress * 0.24
            + $avgEngagement * 0.16
            + $notificationRate * 0.08
            + $qualityRate * 0.10
            + $recencyScore * 0.08
        );

        $churnRisk = su_percent(
            100
            - $engagementForecast * 0.45
            - $completionProbability * 0.25
            - $recencyScore * 0.20
            + min(35, $daysInactive * 1.35)
        );

        $next30 = max(0, (int)round(($engagementForecast / 100) * max(1, min(6, $n + 1)) - ($churnRisk >= 70 ? 1 : 0)));
        $preferredTypes = array_count_values(array_filter($types));
        arsort($preferredTypes);

        $users[] = [
            'nom' => $group['nom'],
            'email' => $group['email'],
            'initials' => su_initials((string)$group['nom']),
            'historical' => [
                'participations_count' => $n,
                'active_challenges_count' => $activeCount,
                'finished_challenges_count' => $finishedCount,
                'completion_count' => $completionCount,
                'avg_progress' => $avgProgress,
                'avg_engagement' => $avgEngagement,
                'completion_rate' => $completionRate,
                'notification_rate' => $notificationRate,
                'quality_signal' => $qualityRate,
                'days_inactive' => $daysInactive,
                'last_activity' => $lastActivity,
                'preferred_types' => array_slice(array_keys($preferredTypes), 0, 3),
            ],
            'prediction' => [
                'engagement_forecast' => $engagementForecast,
                'completion_probability' => $completionProbability,
                'churn_risk' => $churnRisk,
                'risk_label' => su_risk_label($churnRisk),
                'predicted_challenges_next_30d' => $next30,
                'popularity_affinity' => $popularityAffinity,
                'recommended_action' => su_action($churnRisk, $completionProbability, $engagementForecast),
            ],
        ];
    }

    usort($users, fn($a, $b) =>
        ($b['prediction']['churn_risk'] <=> $a['prediction']['churn_risk'])
        ?: ($b['prediction']['engagement_forecast'] <=> $a['prediction']['engagement_forecast'])
    );

    $scoped = array_slice($users, 0, $limit);
    $count = count($users);
    $avgEngagement = $count ? su_percent(array_sum(array_map(fn($u) => $u['prediction']['engagement_forecast'], $users)) / $count) : 0;
    $avgCompletion = $count ? su_percent(array_sum(array_map(fn($u) => $u['prediction']['completion_probability'], $users)) / $count) : 0;
    $avgRisk = $count ? su_percent(array_sum(array_map(fn($u) => $u['prediction']['churn_risk'], $users)) / $count) : 0;
    $critical = count(array_filter($users, fn($u) => $u['prediction']['churn_risk'] >= 70));

    su_json([
        'ok' => true,
        'generated_at' => date('c'),
        'scope' => [
            'email' => $emailFilter !== '' ? $emailFilter : null,
            'limit' => $limit,
        ],
        'global' => [
            'users_count' => $count,
            'avg_engagement_forecast' => $avgEngagement,
            'avg_completion_probability' => $avgCompletion,
            'avg_churn_risk' => $avgRisk,
            'critical_users_count' => $critical,
        ],
        'statsUser' => $scoped,
        'methodology' => [
            'engagement_forecast' => 'engagement historique, progression, complétion, fréquence, qualité et récence',
            'completion_probability' => 'complétions passées, progression moyenne, engagement, notifications et récence',
            'churn_risk' => 'risque calculé depuis inactivité, engagement prévu et probabilité de complétion',
        ],
    ]);
} catch (Throwable $e) {
    error_log('stats-user-predictions: ' . $e->getMessage());
    su_json(['ok' => false, 'error' => 'Erreur serveur statsUser'], 500);
}
