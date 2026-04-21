<?php
require_once '../config.php';
require_once 'dossierMedical.controller.php';
require_once 'regime.controller.php';
require_once '../Model/DossierMedical.php';
require_once '../Model/Regime.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
// DEBUG
error_log('Handler Action: [' . $action . '] GET: ' . json_encode($_GET) . ' POST: ' . json_encode($_POST));
$response = [
    'success' => false,
    'message' => 'Unknown action',
    'debug' => [
        'action' => $action,
        'get' => $_GET,
        'post' => $_POST,
        'method' => $_SERVER['REQUEST_METHOD']
    ],
    'data' => null
];

try {
    $dossierCtrl = new DossierMedicalController();
    $regimeCtrl = new RegimeController();

    switch ($action) {
        // Dossier actions
        case 'dossier-list':
            $result = $dossierCtrl->list();
            $response = [
                'success' => true,
                'message' => 'Dossiers récupérés',
                'data' => $result
            ];
            break;

        case 'dossier-get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                $response['message'] = 'ID du dossier manquant';
                break;
            }
            $result = $dossierCtrl->getDossierWithRegime($id);
            if ($result) {
                $result = [$result];
            } else {
                $result = [];
            }
            $response = [
                'success' => true,
                'message' => 'Dossier récupéré',
                'data' => $result
            ];
            break;

        case 'dossier-add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $id_utilisateur = $_SESSION['user_id'] ?? 1;
            $dossier = new DossierMedical(
                null,
                $id_utilisateur,
                null,
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

            $result = $dossierCtrl->add($dossier);
            $response = [
                'success' => true,
                'message' => 'Dossier enregistré',
                'data' => $result
            ];
            break;

        case 'dossier-update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                $response['message'] = 'ID du dossier manquant';
                break;
            }

            $id_utilisateur = $_SESSION['user_id'] ?? 1;
            $dossier = new DossierMedical(
                $id,
                $id_utilisateur,
                null,
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

            $result = $dossierCtrl->update($dossier, $id);
            $response = [
                'success' => true,
                'message' => 'Dossier mis à jour',
                'data' => $result
            ];
            break;

        case 'dossier-delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                $response['message'] = 'ID du dossier manquant';
                break;
            }

            $result = $dossierCtrl->delete($id);
            $response = [
                'success' => true,
                'message' => 'Dossier supprimé',
                'data' => $result
            ];
            break;

        case 'dossier-attach-regime':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $id_dossier = $_POST['id_dossier'] ?? null;
            $id_regime = $_POST['id_regime'] ?? null;

            if (!$id_dossier || !$id_regime) {
                http_response_code(400);
                $response['message'] = 'IDs manquants';
                break;
            }

            $result = $dossierCtrl->attachRegime($id_dossier, $id_regime);
            $response = [
                'success' => true,
                'message' => 'Régime attaché',
                'data' => $result
            ];
            break;

        case 'regime-list':
            $result = $regimeCtrl->list();
            $response = [
                'success' => true,
                'message' => 'Régimes récupérés',
                'data' => $result
            ];
            break;

        case 'regime-add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $regime = new Regime(
                null,
                $_POST['nom_regime'] ?? null,
                null,
                $_POST['description'] ?? null,
                $_POST['type_regime'] ?? null,
                $_POST['niveau_difficulte'] ?? null,
                null,
                null,
                $_POST['apport_calorique_moyen'] ?? null
            );

            $result = $regimeCtrl->add($regime);
            $response = [
                'success' => true,
                'message' => 'Régime créé',
                'data' => $result
            ];
            break;

        case 'regime-delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response['message'] = 'POST method required';
                break;
            }

            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                $response['message'] = 'ID du régime manquant';
                break;
            }

            $result = $regimeCtrl->delete($id);
            $response = [
                'success' => true,
                'message' => 'Régime supprimé',
                'data' => $result
            ];
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Action non reconnue: ' . $action;
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'data' => null
    ];
}

echo json_encode($response);
exit;
?>
