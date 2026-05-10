<?php
// triRole.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../config.php';

try {
    $pdo = config::getConnexion();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion: ' . $e->getMessage()]);
    exit();
}

// Récupérer le paramètre de filtre rôle
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Valider le rôle (sécurité)
$allowedRoles = ['', 'utilisateur', 'admin', 'nutritionniste', 'ecologiste'];
if (!in_array($role, $allowedRoles)) {
    $role = '';
}

// Stocker le filtre dans la session
if (isset($_GET['role'])) {
    $_SESSION['user_role_filter'] = $role;
}

try {
    // 🔴 CORRECTION : Ajouter la colonne 'status'
    $sql = "SELECT id_utilisateur, nom, prenom, email, role, status, date_creation, date_mise_a_jour
            FROM utilisateurs";

    if ($role !== '') {
        $sql .= " WHERE role = :role";
    }

    $sql .= " ORDER BY date_creation DESC";

    $stmt = $pdo->prepare($sql);

    if ($role !== '') {
        $stmt->execute(['role' => $role]);
    } else {
        $stmt->execute();
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données
    $formattedUsers = [];
    foreach ($users as $user) {
        // Normaliser le statut
        $status = $user['status'] ?? 'actif';
        if ($status === 'active') $status = 'actif';
        if ($status === 'inactive') $status = 'inactif';
        if ($status === 'suspended') $status = 'suspendu';

        $formattedUsers[] = [
            'id' => $user['id_utilisateur'],
            'nom' => htmlspecialchars($user['nom']),
            'prenom' => htmlspecialchars($user['prenom']),
            'email' => htmlspecialchars($user['email']),
            'role' => htmlspecialchars($user['role']),
            'status' => $status,  // 🔴 AJOUTER LE STATUT
            'date_creation' => date('d/m/Y H:i', strtotime($user['date_creation'])),
            'date_mise_a_jour' => date('d/m/Y H:i', strtotime($user['date_mise_a_jour']))
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedUsers,
        'role' => $role,
        'count' => count($formattedUsers),
        'message' => $role ? "Filtre par rôle: $role" : "Tous les utilisateurs"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>