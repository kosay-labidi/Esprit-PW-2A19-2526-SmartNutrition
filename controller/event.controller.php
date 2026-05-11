<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/EvenementController.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_GET['action'] ?? 'list';
    $controller = new EvenementController();

    if (in_array($action, ['list', 'read', 'getall'], true)) {
        $type = trim($_GET['type'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $sort = trim($_GET['sort'] ?? 'date ASC');
        $allowedSort = ['date ASC', 'date DESC', 'titre ASC', 'titre DESC'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'date ASC';
        }

        $db = Config::getConnexion();
        $conditions = [];
        $params = [];

        if ($type !== '') {
            $conditions[] = 'type = :type';
            $params[':type'] = $type;
        }

        if ($search !== '') {
            $conditions[] = '(titre LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $db->prepare("SELECT * FROM evenement $where ORDER BY $sort");
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format compatible avec l'ancien module frontend events.html.
        $data = array_map(static function (array $event): array {
            return [
                'id' => (int) $event['id_event'],
                'id_event' => (int) $event['id_event'],
                'titre' => $event['titre'] ?? '',
                'description' => $event['description'] ?? '',
                'date' => $event['date'] ?? '',
                'heure' => $event['heure'] ?? '',
                'date_debut' => $event['date'] ?? '',
                'heure_debut' => $event['heure'] ?? '',
                'type' => $event['type'] ?? '',
                'lieu' => $event['lieu'] ?? 'GaiaLumen',
                'organisateur' => $event['organisateur'] ?? 'GaiaLumen',
                'capacite_max' => $event['capacite_max'] ?? null,
                'participants_count' => $event['participants_count'] ?? 0,
                'image' => $event['image'] ?? null,
            ];
        }, $events);

        if ($action === 'read') {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode($data);
        }
        exit;
    }

    if ($action === 'getstats') {
        $stats = $controller->getStats();
        if (isset($_GET['wrapped']) && $_GET['wrapped'] === '1') {
            echo json_encode(['success' => true, 'data' => $stats]);
        } else {
            echo json_encode($stats);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
