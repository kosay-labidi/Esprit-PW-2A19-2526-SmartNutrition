<?php
// tri.php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../controller/user.controller.php';

// Vérification des droits admin
requireAdmin();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = config::getConnexion();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit();
}

function normalizeStatus(?string $status): string
{
    $status = trim((string)$status);
    if ($status === 'active') return 'actif';
    if ($status === 'inactive') return 'inactif';
    if ($status === 'suspended') return 'suspendu';
    return in_array($status, ['actif', 'inactif', 'suspendu'], true) ? $status : 'actif';
}

$order = strtolower((string)($_GET['order'] ?? 'desc'));
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
$sqlOrder = $order === 'asc' ? 'ASC' : 'DESC';

$search = trim((string)($_GET['search'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));
$status = normalizeStatus($_GET['status'] ?? '');
$allowedRoles = ['', 'utilisateur', 'admin', 'nutritionniste', 'ecologiste'];
if (!in_array($role, $allowedRoles, true)) $role = '';
if (!isset($_GET['status']) || !in_array($status, ['actif', 'inactif', 'suspendu'], true)) $status = '';

$_SESSION['user_sort_order'] = $order;
$_SESSION['user_role_filter'] = $role;
$_SESSION['user_status_filter'] = $status;

try {
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(nom LIKE :search OR prenom LIKE :search OR email LIKE :search OR CONCAT(prenom, ' ', nom) LIKE :search OR CONCAT(nom, ' ', prenom) LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($role !== '') {
        $where[] = 'role = :role';
        $params[':role'] = $role;
    }

    if ($status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }

    $query = "SELECT id_utilisateur, nom, prenom, email, role, status, date_creation, date_mise_a_jour
              FROM utilisateurs";
    if ($where) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }
    $query .= " ORDER BY date_creation $sqlOrder, id_utilisateur $sqlOrder";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedUsers = [];
    foreach ($users as $user) {
        $normalizedStatus = normalizeStatus($user['status'] ?? 'actif');
        
        $formattedUsers[] = [
            'id' => $user['id_utilisateur'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $normalizedStatus,
            'date_creation_raw' => $user['date_creation'],
            'date_creation' => date('d/m/Y H:i', strtotime($user['date_creation'])),
            'date_mise_a_jour' => date('d/m/Y H:i', strtotime($user['date_mise_a_jour']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedUsers,
        'order' => $order,
        'filters' => ['search' => $search, 'role' => $role, 'status' => $status],
        'count' => count($formattedUsers),
        'message' => "Utilisateurs triés par date d'inscription ($order)"
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du tri des utilisateurs: ' . $e->getMessage()
    ]);
}
?>
