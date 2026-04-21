<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/DossierMedical.php';
require_once __DIR__ . '/../Model/Regime.php';

class DossierMedicalController {

    public function list() {
        $sql = "SELECT d.*, r.nom_regime, r.type_regime, r.niveau_difficulte, r.apport_calorique_moyen
                FROM dossier_medical d
                LEFT JOIN regimes r ON d.id_regime = r.id_regime
                ORDER BY d.date_creation DESC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id) {
        $sql = "SELECT d.*, r.nom_regime, r.type_regime, r.niveau_difficulte, r.apport_calorique_moyen
                FROM dossier_medical d
                LEFT JOIN regimes r ON d.id_regime = r.id_regime
                WHERE d.id_dossier = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(DossierMedical $d) {
        // Auto-compute IMC if poids & taille provided
        $imc = null;
        if ($d->getPoids() > 0 && $d->getTaille() > 0) {
            $imc = round($d->getPoids() / pow($d->getTaille() / 100, 2), 2);
        }

        $sql = "INSERT INTO dossier_medical
                    (id_utilisateur, id_regime, groupe_sanguin, poids, taille, imc,
                     regime_special, notes_medecin, allergie, gravite_allergie,
                     maladies, traitement, medecin, contact_en_cas_durgence)
                VALUES
                    (:id_user, :id_regime, :groupe, :poids, :taille, :imc,
                     :regime, :notes, :allergie, :gravite,
                     :maladies, :traitement, :medecin, :contact)";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_user'   => $d->getIdUtilisateur() ?? 1,
            ':id_regime' => $d->getIdRegime(),
            ':groupe'    => $d->getGroupeSanguin(),
            ':poids'     => $d->getPoids(),
            ':taille'    => $d->getTaille(),
            ':imc'       => $imc,
            ':regime'    => $d->getRegimeSpecial(),
            ':notes'     => $d->getNotesMedecin(),
            ':allergie'  => $d->getAllergie(),
            ':gravite'   => $d->getGraviteAllergie(),
            ':maladies'  => $d->getMaladies(),
            ':traitement'=> $d->getTraitement(),
            ':medecin'   => $d->getMedecin(),
            ':contact'   => $d->getContactUrgence(),
        ]);
        return (int)$db->lastInsertId();
    }

    public function update(DossierMedical $d, $id) {
        // Auto-compute IMC
        $imc = null;
        if ($d->getPoids() > 0 && $d->getTaille() > 0) {
            $imc = round($d->getPoids() / pow($d->getTaille() / 100, 2), 2);
        }

        $sql = "UPDATE dossier_medical SET
                    id_regime            = :id_regime,
                    groupe_sanguin       = :groupe,
                    poids                = :poids,
                    taille               = :taille,
                    imc                  = :imc,
                    regime_special       = :regime,
                    notes_medecin        = :notes,
                    allergie             = :allergie,
                    gravite_allergie     = :gravite,
                    maladies             = :maladies,
                    traitement           = :traitement,
                    medecin              = :medecin,
                    contact_en_cas_durgence = :contact
                WHERE id_dossier = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id'        => $id,
            ':id_regime' => $d->getIdRegime(),
            ':groupe'    => $d->getGroupeSanguin(),
            ':poids'     => $d->getPoids(),
            ':taille'    => $d->getTaille(),
            ':imc'       => $imc,
            ':regime'    => $d->getRegimeSpecial(),
            ':notes'     => $d->getNotesMedecin(),
            ':allergie'  => $d->getAllergie(),
            ':gravite'   => $d->getGraviteAllergie(),
            ':maladies'  => $d->getMaladies(),
            ':traitement'=> $d->getTraitement(),
            ':medecin'   => $d->getMedecin(),
            ':contact'   => $d->getContactUrgence(),
        ]);
        return true;
    }

    public function delete($id) {
        $sql  = "DELETE FROM dossier_medical WHERE id_dossier = :id";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return true;
    }

    /**
     * Attach an existing regime to an existing dossier.
     */
    public function attachRegime($id_dossier, $id_regime) {
        $sql  = "UPDATE dossier_medical SET id_regime = :id_regime WHERE id_dossier = :id_dossier";
        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_regime' => $id_regime, ':id_dossier' => $id_dossier]);
        return true;
    }

    /**
     * Global statistics for the admin dashboard.
     */
    public function stats() {
        $db = config::getConnexion();

        $r = $db->query("SELECT COUNT(*) AS total_dossiers FROM dossier_medical")->fetch();
        $r2 = $db->query("SELECT AVG(imc) AS avg_imc FROM dossier_medical WHERE imc IS NOT NULL AND imc > 0")->fetch();
        $r3 = $db->query("SELECT COUNT(*) AS allergies_count FROM dossier_medical WHERE allergie IS NOT NULL AND allergie <> ''")->fetch();

        return [
            'total_dossiers'  => (int)($r['total_dossiers']  ?? 0),
            'avg_imc'         => (float)($r2['avg_imc']       ?? 0),
            'allergies_count' => (int)($r3['allergies_count'] ?? 0),
        ];
    }

    /**
     * Search dossiers by optional query + blood group filter.
     */
    public function search($query = '', $groupe_sanguin = '', $sort = 'date_mise_a_jour-DESC') {
        $allowed_sorts = [
            'date_mise_a_jour-DESC' => 'd.date_mise_a_jour DESC',
            'date_mise_a_jour-ASC'  => 'd.date_mise_a_jour ASC',
            'imc-DESC'              => 'd.imc DESC',
            'imc-ASC'               => 'd.imc ASC',
        ];
        $orderBy = $allowed_sorts[$sort] ?? 'd.date_mise_a_jour DESC';

        $sql    = "SELECT d.*, r.nom_regime FROM dossier_medical d LEFT JOIN regimes r ON d.id_regime = r.id_regime WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (d.groupe_sanguin LIKE :q OR d.medecin LIKE :q OR d.maladies LIKE :q)";
            $params[':q'] = '%' . $query . '%';
        }
        if (!empty($groupe_sanguin)) {
            $sql .= " AND d.groupe_sanguin = :groupe";
            $params[':groupe'] = $groupe_sanguin;
        }

        $sql .= " ORDER BY $orderBy";

        $db   = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ──────────────────────────────────────────────────────── */
    /*  HTTP request dispatcher                                 */
    /* ──────────────────────────────────────────────────────── */
    public function handleRequest() {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        try {
            switch ($action) {

                /* ── LIST (admin table) ── */
                case 'list':
                    echo json_encode(['success' => true, 'data' => $this->list()]);
                    break;

                /* ── GET single ── */
                case 'get':
                    $id = $_GET['id'] ?? null;
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID requis']); break; }
                    $result = $this->show($id);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                /* ── READ (alias for list, used by frontend health.html) ── */
                case 'read':
                    echo json_encode(['success' => true, 'data' => $this->list()]);
                    break;

                /* ── STATS (admin dashboard counters) ── */
                case 'stats':
                    echo json_encode(array_merge(['success' => true], $this->stats()));
                    break;

                /* ── SEARCH ── */
                case 'search':
                    $q      = $_GET['q']              ?? $_POST['q']              ?? '';
                    $groupe = $_GET['groupe_sanguin']  ?? $_POST['groupe_sanguin'] ?? '';
                    $sort   = $_GET['sort']            ?? $_POST['sort']           ?? 'date_mise_a_jour-DESC';
                    $result = $this->search($q, $groupe, $sort);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                /* ── ADD ── */
                case 'add':
                    $id_regime_raw = $_POST['id_regime'] ?? null;
                    $id_regime_val = ($id_regime_raw !== null && $id_regime_raw !== '' && is_numeric($id_regime_raw))
                        ? intval($id_regime_raw) : null;

                    $dossier = new DossierMedical(
                        null,
                        intval($_SESSION['user_id'] ?? 1),
                        $id_regime_val,
                        null, null,
                        $_POST['groupe_sanguin']          ?? null,
                        floatval($_POST['poids']          ?? 0),
                        floatval($_POST['taille']         ?? 0),
                        null,
                        $_POST['regime_special']          ?? null,
                        $_POST['notes_medecin']           ?? null,
                        $_POST['allergie']                ?? null,
                        $_POST['gravite_allergie']        ?? null,
                        $_POST['maladies']                ?? null,
                        $_POST['traitement']              ?? null,
                        $_POST['medecin']                 ?? null,
                        $_POST['contact_en_cas_durgence'] ?? null
                    );
                    $newId = $this->add($dossier);
                    echo json_encode(['success' => true, 'message' => 'Dossier enregistré', 'id_dossier' => $newId]);
                    break;

                /* ── UPDATE ── */
                case 'update':
                    $id = $_POST['id'] ?? $_POST['id_dossier'] ?? null;
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID requis']); break; }

                    $id_regime_raw = $_POST['id_regime'] ?? null;
                    $id_regime_val = ($id_regime_raw !== null && $id_regime_raw !== '' && is_numeric($id_regime_raw))
                        ? intval($id_regime_raw) : null;

                    $existing = $this->show($id);
                    $dossier  = new DossierMedical(
                        intval($id),
                        intval($existing['id_utilisateur'] ?? 1),
                        $id_regime_val,
                        null, null,
                        $_POST['groupe_sanguin']          ?? null,
                        floatval($_POST['poids']          ?? 0),
                        floatval($_POST['taille']         ?? 0),
                        null,
                        $_POST['regime_special']          ?? null,
                        $_POST['notes_medecin']           ?? null,
                        $_POST['allergie']                ?? null,
                        $_POST['gravite_allergie']        ?? null,
                        $_POST['maladies']                ?? null,
                        $_POST['traitement']              ?? null,
                        $_POST['medecin']                 ?? null,
                        $_POST['contact_en_cas_durgence'] ?? null
                    );
                    $this->update($dossier, intval($id));
                    echo json_encode(['success' => true, 'message' => 'Dossier mis à jour']);
                    break;

                /* ── ATTACH REGIME ── */
                case 'attachRegime':
                    $id_dossier = $_POST['id_dossier'] ?? null;
                    $id_regime  = $_POST['id_regime']  ?? null;
                    if (!$id_dossier || !$id_regime) {
                        echo json_encode(['success'=>false,'message'=>'id_dossier et id_regime requis']);
                        break;
                    }
                    $this->attachRegime(intval($id_dossier), intval($id_regime));
                    echo json_encode(['success' => true, 'message' => 'Régime associé au dossier']);
                    break;

                /* ── DELETE ── */
                case 'delete':
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID requis']); break; }
                    $this->delete(intval($id));
                    echo json_encode(['success' => true, 'message' => 'Dossier supprimé']);
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
    $controller = new DossierMedicalController();
    $controller->handleRequest();
}
?>
