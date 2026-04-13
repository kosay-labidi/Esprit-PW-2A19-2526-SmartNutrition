<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Regime.php';

class RegimeController {

    public function list() {
        $sql = "SELECT * FROM regimes ORDER BY nom_regime";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Statistics and analytics
    public function getStatistics() {
        $db = config::getConnexion();

        $stats = [];

        // Total regimes
        $stmt = $db->query("SELECT COUNT(*) as total FROM regimes");
        $stats['total_regimes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Type distribution
        $stmt = $db->query("
            SELECT type_regime, COUNT(*) as count
            FROM regimes
            GROUP BY type_regime
            ORDER BY count DESC
        ");
        $stats['type_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Difficulty distribution
        $stmt = $db->query("
            SELECT niveau_difficulte, COUNT(*) as count
            FROM regimes
            GROUP BY niveau_difficulte
            ORDER BY count DESC
        ");
        $stats['difficulty_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average calories
        $stmt = $db->query("SELECT AVG(apport_calorique_moyen) as avg_calories FROM regimes WHERE apport_calorique_moyen IS NOT NULL");
        $stats['avg_calories'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_calories'], 0);

        return $stats;
    }

    // Search regimes
    public function search($query, $filters = []) {
        $sql = "SELECT * FROM regimes WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (nom_regime LIKE :query OR description LIKE :query)";
            $params[':query'] = '%' . $query . '%';
        }

        // Apply filters
        if (!empty($filters['type_regime'])) {
            $sql .= " AND type_regime = :type_regime";
            $params[':type_regime'] = $filters['type_regime'];
        }

        if (!empty($filters['niveau_difficulte'])) {
            $sql .= " AND niveau_difficulte = :niveau_difficulte";
            $params[':niveau_difficulte'] = $filters['niveau_difficulte'];
        }

        if (!empty($filters['calories_min'])) {
            $sql .= " AND apport_calorique_moyen >= :calories_min";
            $params[':calories_min'] = $filters['calories_min'];
        }

        if (!empty($filters['calories_max'])) {
            $sql .= " AND apport_calorique_moyen <= :calories_max";
            $params[':calories_max'] = $filters['calories_max'];
        }

        $sql .= " ORDER BY nom_regime";

        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Sort regimes
    public function sort($field = 'nom_regime', $direction = 'ASC') {
        $allowedFields = ['nom_regime', 'type_regime', 'niveau_difficulte', 'apport_calorique_moyen', 'date_creation'];
        $allowedDirections = ['ASC', 'DESC'];

        if (!in_array($field, $allowedFields)) {
            $field = 'nom_regime';
        }

        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'ASC';
        }

        $sql = "SELECT * FROM regimes ORDER BY {$field} {$direction}";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Export to PDF
    public function exportToPdf() {
        $regimes = $this->list();

        $html = "<h1>Régimes Alimentaires - Export PDF</h1>";
        $html .= "<table border='1' cellpadding='5'>";
        $html .= "<tr><th>Nom</th><th>Type</th><th>Difficulté</th><th>Calories</th><th>Aliments Interdits</th><th>Aliments Recommandés</th></tr>";

        foreach ($regimes as $r) {
            $interdits = json_decode($r['aliments_interdits'] ?: '[]', true);
            $recommandes = json_decode($r['aliments_recommandes'] ?: '[]', true);

            $html .= "<tr>";
            $html .= "<td>{$r['nom_regime']}</td>";
            $html .= "<td>{$r['type_regime']}</td>";
            $html .= "<td>{$r['niveau_difficulte']}</td>";
            $html .= "<td>{$r['apport_calorique_moyen']}</td>";
            $html .= "<td>" . implode(', ', $interdits) . "</td>";
            $html .= "<td>" . implode(', ', $recommandes) . "</td>";
            $html .= "</tr>";
        }

        $html .= "</table>";
        return $html;
    }

    // Health assistant methods
    public function suggestRegime($userProfile) {
        // Simple recommendation logic based on user profile
        $suggestions = [];

        if (!empty($userProfile['goal'])) {
            switch ($userProfile['goal']) {
                case 'lose_weight':
                    $suggestions = ['perte_de_poids', 'equilibre'];
                    break;
                case 'gain_weight':
                    $suggestions = ['prise_de_masse', 'sportif'];
                    break;
                case 'maintain':
                    $suggestions = ['equilibre', 'alimentaire'];
                    break;
            }
        }

        if (!empty($userProfile['restrictions'])) {
            if (in_array('gluten', $userProfile['restrictions'])) {
                $suggestions[] = 'sans_gluten';
            }
            if (in_array('lactose', $userProfile['restrictions'])) {
                $suggestions[] = 'vegetarien'; // Often lactose-free
            }
        }

        $sql = "SELECT * FROM regimes WHERE type_regime IN ('" . implode("','", $suggestions) . "') LIMIT 5";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get regime by slug
    public function getBySlug($slug) {
        $sql = "SELECT * FROM regimes WHERE slug = :slug";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle API requests
    public function handleRequest() {
        header('Content-Type: application/json');

        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'list':
                    $result = $this->list();
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'stats':
                    $stats = $this->getStatistics();
                    echo json_encode($stats);
                    break;

                case 'search':
                    $query = $_GET['q'] ?? '';
                    $filters = [
                        'type_regime' => $_GET['type_regime'] ?? '',
                        'niveau_difficulte' => $_GET['niveau_difficulte'] ?? '',
                        'calories_min' => $_GET['calories_min'] ?? '',
                        'calories_max' => $_GET['calories_max'] ?? ''
                    ];
                    $result = $this->search($query, $filters);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'sort':
                    $field = $_GET['field'] ?? 'nom_regime';
                    $direction = $_GET['direction'] ?? 'ASC';
                    $result = $this->sort($field, $direction);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'suggest':
                    $profile = [
                        'goal' => $_GET['goal'] ?? '',
                        'restrictions' => explode(',', $_GET['restrictions'] ?? '')
                    ];
                    $result = $this->suggestRegime($profile);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'export_pdf':
                    $html = $this->exportToPdf();
                    echo json_encode(['success' => true, 'html' => $html]);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Action not found']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function show($id) {
        $sql = "SELECT * FROM regimes WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(Regime $r) {
        // Ensure JSON fields are properly formatted
        $alimentsInterdits = $r->getAlimentsInterdits();
        $alimentsRecommandes = $r->getAlimentsRecommandes();

        // Convert to JSON if not already
        if (!is_string($alimentsInterdits)) {
            $alimentsInterdits = json_encode($alimentsInterdits ?: []);
        }
        if (!is_string($alimentsRecommandes)) {
            $alimentsRecommandes = json_encode($alimentsRecommandes ?: []);
        }

        $sql = "INSERT INTO regimes (nom_regime, slug, description, type_regime, niveau_difficulte, aliments_interdits, aliments_recommandes, apport_calorique_moyen)
                VALUES (:nom, :slug, :desc, :type, :niveau, :interdits, :recommandes, :cal)";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':nom' => $r->getNomRegime(),
                ':slug' => $r->getSlug(),
                ':desc' => $r->getDescription(),
                ':type' => $r->getTypeRegime(),
                ':niveau' => $r->getNiveauDifficulte(),
                ':interdits' => $alimentsInterdits,
                ':recommandes' => $alimentsRecommandes,
                ':cal' => $r->getApportCalorique()
            ]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new Exception("Ce régime existe déjà dans la base de données: " . $r->getNomRegime());
            }
            throw $e;
        }
    }

    public function update(Regime $r, $id) {
        // Ensure JSON fields are properly formatted
        $alimentsInterdits = $r->getAlimentsInterdits();
        $alimentsRecommandes = $r->getAlimentsRecommandes();

        // Convert to JSON if not already
        if (!is_string($alimentsInterdits)) {
            $alimentsInterdits = json_encode($alimentsInterdits ?: []);
        }
        if (!is_string($alimentsRecommandes)) {
            $alimentsRecommandes = json_encode($alimentsRecommandes ?: []);
        }

        $sql = "UPDATE regimes SET nom_regime=:nom, slug=:slug, description=:desc, type_regime=:type, niveau_difficulte=:niveau, aliments_interdits=:interdits, aliments_recommandes=:recommandes, apport_calorique_moyen=:cal WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nom' => $r->getNomRegime(),
            ':slug' => $r->getSlug(),
            ':desc' => $r->getDescription(),
            ':type' => $r->getTypeRegime(),
            ':niveau' => $r->getNiveauDifficulte(),
            ':interdits' => $alimentsInterdits,
            ':recommandes' => $alimentsRecommandes,
            ':cal' => $r->getApportCalorique()
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM regimes WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}

// Handle API requests
if (isset($_GET['action'])) {
    $controller = new RegimeController();
    $controller->handleRequest();
}
?>