<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/DossierMedical.php';
require_once __DIR__ . '/../Model/Regime.php';

class DossierMedicalController {
    public function currentUserId(): int {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!empty($_SESSION['user']['id_utilisateur'])) {
            return (int) $_SESSION['user']['id_utilisateur'];
        }
        if (!empty($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }
        return 0;
    }

    public function currentUserRole(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return (string)($_SESSION['user']['role'] ?? $_SESSION['role'] ?? '');
    }

    public function canAccessDossier(array $dossier, int $userId = 0): bool {
        $currentUserId = $userId > 0 ? $userId : $this->currentUserId();
        if ($this->currentUserRole() === 'admin') {
            return true;
        }
        return $currentUserId > 0 && (int)($dossier['id_utilisateur'] ?? 0) === $currentUserId;
    }

    private function resolveUserId(?int $userId): int {
        $db = config::getConnexion();
        if ($userId && $userId > 0) {
            $stmt = $db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE id_utilisateur = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $found = $stmt->fetchColumn();
            if ($found) {
                return (int) $found;
            }
        }

        $currentUserId = $this->currentUserId();
        if ($currentUserId > 0) {
            return $currentUserId;
        }

        return 0;
    }

    public function list() {
        $sql = "SELECT d.*, r.nom_regime, r.type_regime as regime_type, r.niveau_difficulte as regime_niveau
                FROM dossier_medical d
                LEFT JOIN regimes r ON d.id_regime = r.id_regime
                ORDER BY d.date_creation DESC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id) {
        $sql = "SELECT * FROM dossier_medical WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(DossierMedical $d) {
        $sql = "INSERT INTO dossier_medical (id_utilisateur, id_regime, groupe_sanguin, poids, taille, regime_special, notes_medecin, allergie, gravite_allergie, maladies, traitement, medecin, contact_en_cas_durgence) 
                VALUES (:id_user, :id_regime, :groupe, :poids, :taille, :regime, :notes, :allergie, :gravite, :maladies, :traitement, :medecin, :contact)";
        $db = config::getConnexion();
        $idUser = $this->resolveUserId($d->getIdUtilisateur());
        if ($idUser <= 0) {
            throw new RuntimeException("Aucun utilisateur valide pour ce dossier médical.");
        }
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_user' => $idUser,
            ':id_regime' => $d->getIdRegime() ?? null,
            ':groupe' => $d->getGroupeSanguin(),
            ':poids' => $d->getPoids(),
            ':taille' => $d->getTaille(),
            ':regime' => $d->getRegimeSpecial(),
            ':notes' => $d->getNotesMedecin(),
            ':allergie' => $d->getAllergie(),
            ':gravite' => $d->getGraviteAllergie(),
            ':maladies' => $d->getMaladies(),
            ':traitement' => $d->getTraitement(),
            ':medecin' => $d->getMedecin(),
            ':contact' => $d->getContactUrgence()
        ]);
        return true;
    }

    public function update(DossierMedical $d, $id) {
        $sql = "UPDATE dossier_medical SET id_regime=:id_regime, groupe_sanguin=:groupe, poids=:poids, taille=:taille, regime_special=:regime, notes_medecin=:notes, allergie=:allergie, gravite_allergie=:gravite, maladies=:maladies, traitement=:traitement, medecin=:medecin, contact_en_cas_durgence=:contact WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':id_regime' => $d->getIdRegime() ?? null,
            ':groupe' => $d->getGroupeSanguin(),
            ':poids' => $d->getPoids(),
            ':taille' => $d->getTaille(),
            ':regime' => $d->getRegimeSpecial(),
            ':notes' => $d->getNotesMedecin(),
            ':allergie' => $d->getAllergie(),
            ':gravite' => $d->getGraviteAllergie(),
            ':maladies' => $d->getMaladies(),
            ':traitement' => $d->getTraitement(),
            ':medecin' => $d->getMedecin(),
            ':contact' => $d->getContactUrgence()
        ]);
        return true;
    }

    public function delete($id) {
        $sql = "DELETE FROM dossier_medical WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return true;
    }

    // Statistics and analytics
    public function getStatistics() {
        $db = config::getConnexion();

        // Basic stats
        $stats = [];

        // Total dossiers
        $stmt = $db->query("SELECT COUNT(*) as total FROM dossier_medical");
        $stats['total_dossiers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Average IMC
        $stmt = $db->query("SELECT AVG(imc) as avg_imc FROM dossier_medical WHERE imc IS NOT NULL");
        $stats['avg_imc'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_imc'], 1);

        // IMC categories distribution
        $stmt = $db->query("
            SELECT
                CASE
                    WHEN imc < 18.5 THEN 'underweight'
                    WHEN imc < 25 THEN 'normal'
                    WHEN imc < 30 THEN 'overweight'
                    ELSE 'obese'
                END as category,
                COUNT(*) as count
            FROM dossier_medical
            WHERE imc IS NOT NULL
            GROUP BY category
        ");
        $stats['imc_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Blood type distribution
        $stmt = $db->query("
            SELECT groupe_sanguin, COUNT(*) as count
            FROM dossier_medical
            WHERE groupe_sanguin IS NOT NULL
            GROUP BY groupe_sanguin
            ORDER BY count DESC
        ");
        $stats['blood_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Allergies count
        $stmt = $db->query("SELECT COUNT(*) as count FROM dossier_medical WHERE allergie IS NOT NULL AND allergie != ''");
        $stats['allergies_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $stats;
    }

    // Search dossiers
    public function search($query, $filters = []) {
        $sql = "SELECT * FROM dossier_medical WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (allergie LIKE :query OR maladies LIKE :query OR traitement LIKE :query OR medecin LIKE :query)";
            $params[':query'] = '%' . $query . '%';
        }

        // Apply filters
        if (!empty($filters['groupe_sanguin'])) {
            $sql .= " AND groupe_sanguin = :groupe_sanguin";
            $params[':groupe_sanguin'] = $filters['groupe_sanguin'];
        }

        if (!empty($filters['imc_min'])) {
            $sql .= " AND imc >= :imc_min";
            $params[':imc_min'] = $filters['imc_min'];
        }

        if (!empty($filters['imc_max'])) {
            $sql .= " AND imc <= :imc_max";
            $params[':imc_max'] = $filters['imc_max'];
        }

        $sql .= " ORDER BY date_mise_a_jour DESC";

        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Sort dossiers
    public function sort($field = 'date_mise_a_jour', $direction = 'DESC') {
        $allowedFields = ['id_dossier', 'poids', 'taille', 'imc', 'groupe_sanguin', 'date_mise_a_jour', 'date_creation'];
        $allowedDirections = ['ASC', 'DESC'];

        if (!in_array($field, $allowedFields)) {
            $field = 'date_mise_a_jour';
        }

        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'DESC';
        }

        $sql = "SELECT * FROM dossier_medical ORDER BY {$field} {$direction}";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Export to PDF (basic implementation)
    public function exportToPdf() {
        $dossiers = $this->list();

        // Basic HTML for PDF generation
        $html = "<h1>Dossiers Médicaux - Export PDF</h1>";
        $html .= "<table border='1' cellpadding='5'>";
        $html .= "<tr><th>ID</th><th>Groupe Sanguin</th><th>Poids</th><th>Taille</th><th>IMC</th><th>Allergies</th></tr>";

        foreach ($dossiers as $d) {
            $html .= "<tr>";
            $html .= "<td>{$d['id_dossier']}</td>";
            $html .= "<td>{$d['groupe_sanguin']}</td>";
            $html .= "<td>{$d['poids']} kg</td>";
            $html .= "<td>{$d['taille']} cm</td>";
            $html .= "<td>" . number_format($d['imc'], 1) . "</td>";
            $html .= "<td>{$d['allergie']}</td>";
            $html .= "</tr>";
        }

        $html .= "</table>";
        return $html;
    }

    // Health assistant methods
    public function validateAliment($aliment, $userId = 1) {
        // Get user's medical dossier
        $dossier = $this->getByUserId($userId);
        if (!$dossier) {
            return ['allowed' => true, 'warnings' => [], 'alternatives' => []];
        }

        $warnings = [];
        $alternatives = [];

        // Check allergies
        if ($dossier['allergie'] && stripos($dossier['allergie'], $aliment) !== false) {
            $warnings[] = "Allergie détectée pour: {$aliment}";
        }

        // Check against user's authorized regimes
        $authorizedRegimes = $this->getAuthorizedRegimes($userId);
        foreach ($authorizedRegimes as $regime) {
            if (!$regime->isAlimentAllowed($aliment)) {
                $warnings[] = "Aliment interdit selon le régime: {$regime->getNomRegime()}";
                $alternatives = array_merge($alternatives, $regime->getAlimentsRecommandesArray());
            }
        }

        return [
            'allowed' => empty($warnings),
            'warnings' => $warnings,
            'alternatives' => array_unique($alternatives)
        ];
    }

    public function getByUserId($userId) {
        $sql = "SELECT d.*, r.nom_regime, r.description, r.type_regime, r.niveau_difficulte, r.aliments_interdits, r.aliments_recommandes, r.apport_calorique_moyen
                FROM dossier_medical d
                LEFT JOIN regimes r ON d.id_regime = r.id_regime
                WHERE d.id_utilisateur = :user_id
                ORDER BY d.id_dossier DESC
                LIMIT 1";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAuthorizedRegimes($userId) {
        $sql = "
            SELECT r.* FROM regimes r
            INNER JOIN dossier_medical d ON d.id_regime = r.id_regime
            WHERE d.id_utilisateur = :user_id
        ";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $regimes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $regime = new Regime(
                $row['id_regime'],
                $row['nom_regime'],
                $row['slug'],
                $row['description'],
                $row['type_regime'],
                $row['niveau_difficulte'],
                $row['aliments_interdits'],
                $row['aliments_recommandes'],
                $row['apport_calorique_moyen']
            );
            $regimes[] = $regime;
        }
        return $regimes;
    }

    // Attach a regime to a dossier
    public function attachRegime($id_dossier, $id_regime, $userId = 0) {
        $sql = "UPDATE dossier_medical SET id_regime = :id_regime WHERE id_dossier = :id_dossier";
        $params = [':id_regime' => $id_regime, ':id_dossier' => $id_dossier];
        if ($userId > 0 && $this->currentUserRole() !== 'admin') {
            $sql .= " AND id_utilisateur = :id_utilisateur";
            $params[':id_utilisateur'] = $userId;
        }
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    // Get regimes for selection (all available regimes)
    public function getAvailableRegimes() {
        $sql = "SELECT id_regime, nom_regime, description, type_regime, niveau_difficulte, apport_calorique_moyen FROM regimes ORDER BY nom_regime ASC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get dossier with its associated regime details
    public function getDossierWithRegime($id_dossier) {
        $sql = "SELECT d.*, r.id_regime, r.nom_regime, r.description, r.type_regime, r.niveau_difficulte, r.aliments_interdits, r.aliments_recommandes, r.apport_calorique_moyen
                FROM dossier_medical d
                LEFT JOIN regimes r ON d.id_regime = r.id_regime
                WHERE d.id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id_dossier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle API requests
    public function handleRequest() {
        header('Content-Type: application/json');

        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'read':
                    $userId = $this->currentUserId();
                    if ($userId <= 0) {
                        $userId = (int)($_GET['user_id'] ?? 0);
                    }
                    $result = $userId > 0 ? $this->getByUserId($userId) : null;
                    echo json_encode(['success' => true, 'data' => $result ? [$result] : []]);
                    break;

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
                        'groupe_sanguin' => $_GET['groupe_sanguin'] ?? '',
                        'imc_min' => $_GET['imc_min'] ?? '',
                        'imc_max' => $_GET['imc_max'] ?? ''
                    ];
                    $result = $this->search($query, $filters);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'sort':
                    $field = $_GET['field'] ?? 'date_mise_a_jour';
                    $direction = $_GET['direction'] ?? 'DESC';
                    $result = $this->sort($field, $direction);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'validate_aliment':
                    $aliment = $_GET['aliment'] ?? '';
                    $userId = (int)($_GET['user_id'] ?? 0);
                    if ($userId <= 0) {
                        $userId = $this->currentUserId();
                    }
                    $result = $this->validateAliment($aliment, $userId);
                    echo json_encode($result);
                    break;

                case 'export_pdf':
                    $html = $this->exportToPdf();
                    echo json_encode(['success' => true, 'html' => $html]);
                    break;

                case 'delete':
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID requis']);
                        break;
                    }
                    $this->delete($id);
                    echo json_encode(['success' => true, 'message' => 'Dossier supprimé avec succès']);
                    break;

                case 'add':
                    $id_utilisateur = (int)($_POST['id_utilisateur'] ?? 0);
                    if ($id_utilisateur <= 0) {
                        $id_utilisateur = $this->currentUserId();
                    }
                    if ($id_utilisateur <= 0) {
                        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
                        break;
                    }
                    $dossier = new DossierMedical(
                        null,                                           // id_dossier
                        $id_utilisateur,                                // id_utilisateur
                        null,                                           // id_regime
                        null,                                           // date_creation
                        null,                                           // date_mise_a_jour
                        $_POST['groupe_sanguin'] ?? null,               // groupe_sanguin
                        floatval($_POST['poids'] ?? 0),                 // poids
                        floatval($_POST['taille'] ?? 0),                // taille
                        null,                                           // imc
                        $_POST['regime_special'] ?? null,               // regime_special
                        $_POST['notes_medecin'] ?? null,                // notes_medecin
                        $_POST['allergie'] ?? null,                     // allergie
                        $_POST['gravite_allergie'] ?? null,             // gravite_allergie
                        $_POST['maladies'] ?? null,                     // maladies
                        $_POST['traitement'] ?? null,                   // traitement
                        $_POST['medecin'] ?? null,                      // medecin
                        $_POST['contact_en_cas_durgence'] ?? null       // contact_en_cas_durgence
                    );
                    if (!empty($_POST['id_regime'])) {
                        $dossier->setIdRegime((int)$_POST['id_regime']);
                    }
                    $this->add($dossier);
                    echo json_encode(['success' => true, 'message' => 'Dossier enregistré']);
                    break;

                case 'update':
                    $id_dossier = (int)($_POST['id_dossier'] ?? 0);
                    if ($id_dossier <= 0) {
                        echo json_encode(['success' => false, 'message' => 'ID dossier requis']);
                        break;
                    }
                    $userId = $this->currentUserId();
                    if ($userId <= 0) {
                        $userId = (int)($_POST['id_utilisateur'] ?? $_POST['user_id'] ?? 0);
                    }
                    $existing = $this->show($id_dossier);
                    if (!$existing || !$this->canAccessDossier($existing, $userId)) {
                        echo json_encode(['success' => false, 'message' => 'Dossier introuvable pour cet utilisateur']);
                        break;
                    }
                    $dossier = new DossierMedical(
                        $id_dossier,
                        (int)$existing['id_utilisateur'],
                        !empty($_POST['id_regime']) ? (int)$_POST['id_regime'] : (!empty($existing['id_regime']) ? (int)$existing['id_regime'] : null),
                        $existing['date_creation'] ?? null,
                        null,
                        $_POST['groupe_sanguin'] ?? null,
                        floatval($_POST['poids'] ?? 0),
                        floatval($_POST['taille'] ?? 0),
                        null,
                        $_POST['regime_special'] ?? null,
                        $_POST['notes_medecin'] ?? null,
                        $_POST['allergie'] ?? null,
                        $_POST['gravite_allergie'] ?? null,
                        $_POST['maladies'] ?? null,
                        $_POST['traitement'] ?? null,
                        $_POST['medecin'] ?? null,
                        $_POST['contact_en_cas_durgence'] ?? null
                    );
                    $this->update($dossier, $id_dossier);
                    echo json_encode(['success' => true, 'message' => 'Dossier mis à jour']);
                    break;

                case 'attachRegime':
                    $id_dossier = $_POST['id_dossier'] ?? null;
                    $id_regime = $_POST['id_regime'] ?? null;
                    if (!$id_dossier || !$id_regime) {
                        echo json_encode(['success' => false, 'message' => 'IDs manquants']);
                        break;
                    }
                    $userId = $this->currentUserId();
                    if ($userId <= 0) {
                        $userId = (int)($_POST['user_id'] ?? 0);
                    }
                    $attached = $this->attachRegime($id_dossier, $id_regime, $userId);
                    echo json_encode([
                        'success' => $attached,
                        'message' => $attached ? 'Régime attaché' : 'Dossier introuvable pour cet utilisateur'
                    ]);
                    break;

                case 'get':
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID requis']);
                        break;
                    }
                    $result = $this->getDossierWithRegime($id);
                    echo json_encode(['success' => true, 'message' => 'Dossier récupéré', 'data' => $result]);
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
}

// Handle API requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $controller = new DossierMedicalController();
    $controller->handleRequest();
}
?>
