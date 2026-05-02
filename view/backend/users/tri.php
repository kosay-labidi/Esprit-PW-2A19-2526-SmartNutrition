<?php
// tri.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../Model/User.php';
require_once __DIR__ . '/../../../controller/user.controller.php';

try {
    // Correction: utiliser config::getConnexion() au lieu de getConnexion()
    $pdo = config::getConnexion();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit();
}

// Récupérer le paramètre de tri
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Valider le paramètre (seulement 'asc' ou 'desc')
if (!in_array($order, ['asc', 'desc'])) {
    $order = 'desc';
}

// Définir l'ordre SQL
$sqlOrder = ($order === 'asc') ? 'ASC' : 'DESC';

// Stocker l'ordre dans la session
$_SESSION['user_sort_order'] = $order;

try {
    // Requête avec tri par date_creation
    $query = "SELECT id_utilisateur, nom, prenom, email, role, date_creation, date_mise_a_jour 
              FROM utilisateurs 
              ORDER BY date_creation $sqlOrder";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedUsers = [];
    foreach ($users as $user) {
        $formattedUsers[] = [
            'id' => $user['id_utilisateur'],
            'nom' => htmlspecialchars($user['nom']),
            'prenom' => htmlspecialchars($user['prenom']),
            'email' => htmlspecialchars($user['email']),
            'role' => htmlspecialchars($user['role']),
            'date_creation' => date('d/m/Y H:i', strtotime($user['date_creation'])),
            'date_mise_a_jour' => date('d/m/Y H:i', strtotime($user['date_mise_a_jour']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedUsers,
        'order' => $order,
        'message' => "Utilisateurs triés par date d'inscription ($order)"
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du tri des utilisateurs: ' . $e->getMessage()
    ]);
}
?>