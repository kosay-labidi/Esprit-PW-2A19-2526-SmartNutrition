<?php
/**
 * suspendUser.php
 * Placer dans : view/backend/users/suspendUser.php
 */
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../config.php';

// ── 1. Headers ───────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── 2. Méthode ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

requireAdmin();

// ── 3. Lecture du body JSON ───────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log pour debug
error_log("suspendUser.php - Données reçues: " . print_r($data, true));

if (!$data || !isset($data['id'], $data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants (id, status)']);
    exit;
}

$id     = (int) $data['id'];
$status = trim($data['status']);

// ── 4. Valider le statut ──────────────────────────────────────────────────────
$allowed = ['actif', 'inactif', 'suspendu'];
if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Statut invalide : '$status'"]);
    exit;
}

// ── 5. Connexion via Config ─────────────────────────────────────
try {
    $pdo = Config::getConnexion();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Connexion BDD impossible : ' . $e->getMessage()]);
    exit;
}

// ── 6. Vérifier que l'utilisateur existe ──────────────────────────────────────
try {
    $stmtCheck = $pdo->prepare("SELECT id_utilisateur, role, status FROM utilisateurs WHERE id_utilisateur = ? LIMIT 1");
    $stmtCheck->execute([$id]);
    $user = $stmtCheck->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable (id=' . $id . ')']);
        exit;
    }

    $sessionUser = getSessionUser();
    $sessionUserId = (int)($sessionUser['id_utilisateur'] ?? 0);

    // Éviter qu'un administrateur bloque son propre accès.
    if ((int)$user['id_utilisateur'] === $sessionUserId) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier le statut de votre propre compte administrateur']);
        exit;
    }

    // Vérifier si on essaie de suspendre ou désactiver un admin
    if ($user['role'] === 'admin' && $status !== 'actif') {
        echo json_encode(['success' => false, 'message' => 'Impossible de désactiver ou suspendre un administrateur']);
        exit;
    }

    // Vérifier si le statut est déjà celui demandé
    if ($user['status'] === $status) {
        $msg = $status === 'suspendu' ? 'Utilisateur déjà suspendu' : 'Utilisateur déjà actif';
        echo json_encode(['success' => true, 'message' => $msg, 'status' => $status, 'id' => $id, 'already' => true]);
        exit;
    }

    // ── 7. UPDATE ─────────────────────────────────────────────────────────────
    // Utiliser NOW() pour la date de mise à jour
    $stmt = $pdo->prepare("UPDATE utilisateurs SET status = ?, date_mise_a_jour = NOW() WHERE id_utilisateur = ?");
    $stmt->execute([$status, $id]);
    
    // Vérifier le nombre de lignes affectées
    $rowCount = $stmt->rowCount();
    error_log("suspendUser.php - rowCount: $rowCount");
    
    if ($rowCount > 0) {
        // Re-lire le statut pour confirmer
        $stmtVerify = $pdo->prepare("SELECT status FROM utilisateurs WHERE id_utilisateur = ? LIMIT 1");
        $stmtVerify->execute([$id]);
        $updated = $stmtVerify->fetch();
        
        error_log("suspendUser.php - Nouveau statut lu: " . print_r($updated, true));
        
        if ($updated && $updated['status'] === $status) {
            $msg = match($status) {
                'suspendu' => 'Utilisateur suspendu avec succès',
                'actif'    => 'Utilisateur réactivé avec succès',
                default    => 'Statut mis à jour'
            };
            echo json_encode(['success' => true, 'message' => $msg, 'status' => $status, 'id' => $id]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'La mise à jour n\'a pas été appliquée correctement',
                'status_in_db' => $updated['status'] ?? 'inconnu'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aucune modification effectuée (rowCount=0)'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("suspendUser.php - SQL Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
}
