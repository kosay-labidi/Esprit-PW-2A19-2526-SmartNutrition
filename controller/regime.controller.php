<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Regime.php';

class RegimeController {

    public function list() {
        $sql = "SELECT * FROM regimes ORDER BY nom_regime";
        $db  = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id) {
        $sql  = "SELECT * FROM regimes WHERE id_regime = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug) {
        $sql  = "SELECT * FROM regimes WHERE slug = :slug";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ── STATS ─────────────────────────────────────────────── */

    public function getStatistics() {
        $db    = config::getConnexion();
        $stats = [];

        $stmt = $db->query("SELECT COUNT(*) as total FROM regimes");
        $stats['total_regimes'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmt = $db->query("
            SELECT type_regime, COUNT(*) as count
            FROM regimes GROUP BY type_regime ORDER BY count DESC");
        $stats['type_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->query("
            SELECT niveau_difficulte, COUNT(*) as count
            FROM regimes GROUP BY niveau_difficulte ORDER BY count DESC");
        $stats['difficulty_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->query("SELECT AVG(apport_calorique_moyen) as avg_calories FROM regimes WHERE apport_calorique_moyen IS NOT NULL");
        $stats['avg_calories'] = round((float)($stmt->fetch(PDO::FETCH_ASSOC)['avg_calories'] ?? 0), 0);

        return $stats;
    }

    /* ── SEARCH / SORT ─────────────────────────────────────── */

    public function search($query, $filters = []) {
        $sql    = "SELECT * FROM regimes WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (nom_regime LIKE :query OR description LIKE :query)";
            $params[':query'] = '%' . $query . '%';
        }
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

        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sort($field = 'nom_regime', $direction = 'ASC') {
        $allowedFields     = ['nom_regime','type_regime','niveau_difficulte','apport_calorique_moyen','date_creation'];
        $allowedDirections = ['ASC','DESC'];
        if (!in_array($field, $allowedFields))              $field     = 'nom_regime';
        if (!in_array(strtoupper($direction), $allowedDirections)) $direction = 'ASC';

        $sql = "SELECT * FROM regimes ORDER BY {$field} {$direction}";
        return config::getConnexion()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ── CRUD ───────────────────────────────────────────────── */

    public function add(Regime $r) {
        $alimentsInterdits   = $r->getAlimentsInterdits();
        $alimentsRecommandes = $r->getAlimentsRecommandes();
        if (!is_string($alimentsInterdits))   $alimentsInterdits   = json_encode($alimentsInterdits   ?: []);
        if (!is_string($alimentsRecommandes)) $alimentsRecommandes = json_encode($alimentsRecommandes ?: []);

        $sql  = "INSERT INTO regimes
                    (nom_regime, slug, description, type_regime, niveau_difficulte,
                     aliments_interdits, aliments_recommandes, apport_calorique_moyen)
                 VALUES
                    (:nom, :slug, :desc, :type, :niveau,
                     :interdits, :recommandes, :cal)";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        try {
            $stmt->execute([
                ':nom'        => $r->getNomRegime(),
                ':slug'       => $r->getSlug(),
                ':desc'       => $r->getDescription(),
                ':type'       => $r->getTypeRegime(),
                ':niveau'     => $r->getNiveauDifficulte(),
                ':interdits'  => $alimentsInterdits,
                ':recommandes'=> $alimentsRecommandes,
                ':cal'        => $r->getApportCalorique(),
            ]);
            // Return the new ID so front-end can immediately select it
            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new Exception("Ce régime existe déjà : " . $r->getNomRegime());
            }
            throw $e;
        }
    }

    public function update(Regime $r, $id) {
        $alimentsInterdits   = $r->getAlimentsInterdits();
        $alimentsRecommandes = $r->getAlimentsRecommandes();
        if (!is_string($alimentsInterdits))   $alimentsInterdits   = json_encode($alimentsInterdits   ?: []);
        if (!is_string($alimentsRecommandes)) $alimentsRecommandes = json_encode($alimentsRecommandes ?: []);

        $sql  = "UPDATE regimes SET
                    nom_regime=:nom, slug=:slug, description=:desc,
                    type_regime=:type, niveau_difficulte=:niveau,
                    aliments_interdits=:interdits, aliments_recommandes=:recommandes,
                    apport_calorique_moyen=:cal
                 WHERE id_regime = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id'         => $id,
            ':nom'        => $r->getNomRegime(),
            ':slug'       => $r->getSlug(),
            ':desc'       => $r->getDescription(),
            ':type'       => $r->getTypeRegime(),
            ':niveau'     => $r->getNiveauDifficulte(),
            ':interdits'  => $alimentsInterdits,
            ':recommandes'=> $alimentsRecommandes,
            ':cal'        => $r->getApportCalorique(),
        ]);
        return true;
    }

    public function delete($id) {
        $sql  = "DELETE FROM regimes WHERE id_regime = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return true;
    }

    /* ── DOSSIER-REGIME ASSOCIATIONS ───────────────────────── */

    /**
     * Get the regime linked to a given dossier via dossier_medical.id_regime.
     */
    public function getRegimeByDossier($id_dossier) {
        $sql  = "SELECT r.* FROM regimes r
                 INNER JOIN dossier_medical d ON r.id_regime = d.id_regime
                 WHERE d.id_dossier = :id_dossier";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_dossier' => $id_dossier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function afficherRegimes($idDossier) {
        return $this->getRegimeByDossier($idDossier) ?: [];
    }

    public function afficherTousRegimes() {
        return $this->list();
    }

    public function associerRegimeToDossier($idDossier, $idRegime) {
        $sql  = "UPDATE dossier_medical SET id_regime = :id_regime WHERE id_dossier = :id_dossier";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_dossier' => $idDossier, ':id_regime' => $idRegime]);
        return true;
    }

    public function dissocierRegimeFromDossier($idDossier, $idRegime) {
        $sql  = "UPDATE dossier_medical SET id_regime = NULL
                 WHERE id_dossier = :id_dossier AND id_regime = :id_regime";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_dossier' => $idDossier, ':id_regime' => $idRegime]);
        return true;
    }

    /* ── ASSISTANT / SCORES ─────────────────────────────────── */

    public function suggestRegime($userProfile) {
        $suggestions = [];
        if (!empty($userProfile['goal'])) {
            switch ($userProfile['goal']) {
                case 'lose_weight': $suggestions = ['perte_de_poids','alimentaire']; break;
                case 'gain_weight': $suggestions = ['prise_de_masse','sportif'];     break;
                case 'maintain':    $suggestions = ['alimentaire'];                  break;
            }
        }
        if (empty($suggestions)) return [];

        $placeholders = implode("','", array_map('addslashes', $suggestions));
        $sql = "SELECT * FROM regimes WHERE type_regime IN ('$placeholders') LIMIT 5";
        return config::getConnexion()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isAlimentAllowedForRegime($id_regime, $aliment) {
        $regime = $this->show($id_regime);
        if (!$regime) return true;
        $interdits    = json_decode($regime['aliments_interdits'] ?: '[]', true) ?: [];
        $alimentLower = strtolower($aliment);
        foreach ($interdits as $i) {
            if (strtolower($i) === $alimentLower) return false;
        }
        return true;
    }

    public function getAlternatives($id_regime) {
        $regime = $this->show($id_regime);
        if (!$regime) return [];
        return json_decode($regime['aliments_recommandes'] ?: '[]', true) ?: [];
    }

    public function calculateScores($regime) {
        $scores = ['ecological' => 50, 'nutritional' => 50];
        if (empty($regime)) return $scores;

        $type     = $regime['type_regime']           ?? '';
        $calories = floatval($regime['apport_calorique_moyen'] ?? 0);
        $niveau   = $regime['niveau_difficulte']     ?? '';

        // Ecological
        if ($type === 'alimentaire')    $scores['ecological'] += 20;
        elseif ($type === 'perte_de_poids') $scores['ecological'] += 10;
        elseif ($type === 'sportif')    $scores['ecological'] += 15;
        if ($calories > 0 && $calories < 1500)  $scores['ecological'] += 10;
        elseif ($calories > 3000)                $scores['ecological'] -= 10;
        if ($niveau === 'facile')       $scores['ecological'] += 10;
        elseif ($niveau === 'avance')   $scores['ecological'] -= 5;

        // Nutritional
        if ($type === 'medical')        $scores['nutritional'] += 15;
        elseif ($type === 'sportif')    $scores['nutritional'] += 10;
        elseif ($type === 'perte_de_poids') $scores['nutritional'] += 5;
        if ($calories >= 1500 && $calories <= 2500) $scores['nutritional'] += 10;
        elseif ($calories > 3000)       $scores['nutritional'] -= 10;

        $scores['ecological']  = max(0, min(100, $scores['ecological']));
        $scores['nutritional'] = max(0, min(100, $scores['nutritional']));
        return $scores;
    }

    public function exportToPdf() {
        $regimes = $this->list();
        $html    = "<h1>Régimes Alimentaires</h1><table border='1' cellpadding='5'>";
        $html   .= "<tr><th>Nom</th><th>Type</th><th>Difficulté</th><th>Calories</th></tr>";
        foreach ($regimes as $r) {
            $html .= "<tr><td>{$r['nom_regime']}</td><td>{$r['type_regime']}</td><td>{$r['niveau_difficulte']}</td><td>{$r['apport_calorique_moyen']}</td></tr>";
        }
        $html .= "</table>";
        return $html;
    }

    /* ── HTTP DISPATCHER ────────────────────────────────────── */

    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        try {
            switch ($action) {

                case 'list':
                    echo json_encode(['success' => true, 'data' => $this->list()]);
                    break;

                case 'get':
                case 'read':
                    $id = $_GET['id'] ?? null;
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID requis']); break; }
                    echo json_encode(['success' => true, 'data' => $this->show($id)]);
                    break;

                case 'stats':
                    $stats = $this->getStatistics();
                    // Health-admin expects a flat object with 'total_regimes'
                    echo json_encode(array_merge(['success' => true], $stats));
                    break;

                case 'search':
                    $query   = $_GET['q'] ?? $_POST['q'] ?? '';
                    $filters = [
                        'type_regime'       => $_GET['type_regime']       ?? $_POST['type_regime']       ?? '',
                        'niveau_difficulte' => $_GET['niveau_difficulte'] ?? $_POST['niveau_difficulte'] ?? '',
                        'calories_min'      => $_GET['calories_min']      ?? '',
                        'calories_max'      => $_GET['calories_max']      ?? '',
                    ];
                    echo json_encode(['success' => true, 'data' => $this->search($query, $filters)]);
                    break;

                case 'sort':
                    $field     = $_GET['field']     ?? 'nom_regime';
                    $direction = $_GET['direction']  ?? 'ASC';
                    echo json_encode(['success' => true, 'data' => $this->sort($field, $direction)]);
                    break;

                /* ── ADD: returns new id_regime so JS can auto-select it ── */
                case 'add':
                    $nom = trim($_POST['nom_regime'] ?? '');
                    if (!$nom) { echo json_encode(['success'=>false,'message'=>'Nom du régime requis']); break; }

                    $slug   = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom), '-'));
                    $regime = new Regime(
                        null, $nom, $slug,
                        $_POST['description']          ?? null,
                        $_POST['type_regime']          ?? null,
                        $_POST['niveau_difficulte']    ?? null,
                        null, null,
                        $_POST['apport_calorique_moyen'] ?? null
                    );
                    $newId = $this->add($regime);   // returns lastInsertId
                    echo json_encode([
                        'success'   => true,
                        'message'   => 'Régime créé avec succès',
                        'id_regime' => $newId,       // ← used by frontend to auto-select
                    ]);
                    break;

                case 'update':
                    $id  = $_POST['id_regime'] ?? $_POST['id'] ?? null;
                    $nom = trim($_POST['nom_regime'] ?? '');
                    if (!$id)  { echo json_encode(['success'=>false,'message'=>'ID régime requis']); break; }
                    if (!$nom) { echo json_encode(['success'=>false,'message'=>'Nom requis']);        break; }

                    $slug   = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom), '-'));
                    $regime = new Regime(
                        (int)$id, $nom, $slug,
                        $_POST['description']             ?? null,
                        $_POST['type_regime']             ?? null,
                        $_POST['niveau_difficulte']       ?? null,
                        $_POST['aliments_interdits']      ?? null,
                        $_POST['aliments_recommandes']    ?? null,
                        $_POST['apport_calorique_moyen']  ?? null
                    );
                    $this->update($regime, $id);
                    echo json_encode(['success' => true, 'message' => 'Régime mis à jour']);
                    break;

                case 'delete':
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID requis']); break; }
                    $this->delete($id);
                    echo json_encode(['success' => true, 'message' => 'Régime supprimé']);
                    break;

                case 'getByDossier':
                    $id_dossier = $_GET['id_dossier'] ?? null;
                    if (!$id_dossier) { echo json_encode(['success'=>false,'message'=>'ID dossier requis']); break; }
                    echo json_encode(['success' => true, 'data' => $this->getRegimeByDossier($id_dossier)]);
                    break;

                case 'suggest':
                    $profile = ['goal' => $_GET['goal'] ?? '', 'restrictions' => explode(',', $_GET['restrictions'] ?? '')];
                    echo json_encode(['success' => true, 'data' => $this->suggestRegime($profile)]);
                    break;

                case 'calculate_scores':
                    $regimeData = json_decode($_POST['regime'] ?? '{}', true);
                    echo json_encode(['success' => true, 'scores' => $this->calculateScores($regimeData)]);
                    break;

                case 'export_pdf':
                    echo json_encode(['success' => true, 'html' => $this->exportToPdf()]);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => "Action '$action' non reconnue"]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// ── Entry point ──────────────────────────────────────────────
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new RegimeController();
    $controller->handleRequest();
}
?>
