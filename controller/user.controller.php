<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/User.php';

class UserController
{
    public function addUser(User $user): bool
    {
        $sql = 'INSERT INTO utilisateurs (nom, prenom, email, mdp, role)
                VALUES (:nom, :prenom, :email, :mdp, :role)';
        try {
            $db = config::getConnexion();
            $mdpHash = password_hash($user->getMdp(), PASSWORD_DEFAULT);
            $query = $db->prepare($sql);
            return $query->execute([
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'mdp' => $mdpHash,
                'role' => $user->getRole(),
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function emailExists(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM utilisateurs WHERE email = :email LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            return (bool) $query->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
    /**
     * Retourne un tableau avec 'status' et 'data'.
     * status: 'ok' | 'account_not_found' | 'wrong_password' | 'error'
     */
    public function login(string $email, string $mdp): array
    {
        $email = trim($email);
        if ($email === '' || $mdp === '') {
            return ['status' => 'error', 'data' => null];
        }

        $sql = 'SELECT id_utilisateur, nom, prenom, email, mdp, role 
                FROM utilisateurs WHERE email = :email LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                // L'email n'existe pas dans la base
                return ['status' => 'account_not_found', 'data' => null];
            }

            if (!password_verify($mdp, $row['mdp'])) {
                // Email trouvé mais mot de passe incorrect
                return ['status' => 'wrong_password', 'data' => null];
            }

            unset($row['mdp']); // ne jamais renvoyer le hash
            return ['status' => 'ok', 'data' => $row];

        } catch (Exception $e) {
            return ['status' => 'error', 'data' => null];
        }
    }

    /**
     * Retourne la liste de tous les utilisateurs
     * @return array<int, array<string, mixed>>
     */
    public function listUsers(): array
    {
        $sql = 'SELECT id_utilisateur AS id, nom, prenom, email, role,
                       date_creation AS date_inscription
                FROM utilisateurs
                ORDER BY date_creation DESC';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUserById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $sql = 'SELECT id_utilisateur, nom, prenom, email, role, date_creation, date_mise_a_jour
                FROM utilisateurs WHERE id_utilisateur = :id LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}

$scriptReal = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
$fileReal = realpath(__FILE__);
if (PHP_SAPI !== 'cli' && $scriptReal && $fileReal && $scriptReal === $fileReal) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'] ?? '';
    $controller = new UserController();

    if ($action === 'readOne') {
        $id = (int) ($_GET['id'] ?? 0);
        $row = $controller->getUserById($id);
        if ($row === null) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable ou ID invalide'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data = [
            'id_utilisateur' => (int) $row['id_utilisateur'],
            'nom' => $row['nom'],
            'prenom' => $row['prenom'],
            'email' => $row['email'],
            'role' => $row['role'],
            'created_at' => $row['date_creation'],
            'date_creation' => $row['date_creation'],
            'date_mise_a_jour' => $row['date_mise_a_jour'],
        ];
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'register') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(['success' => false, 'message' => 'Données invalides.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $nom = trim((string) ($payload['nom'] ?? ''));
        $prenom = trim((string) ($payload['prenom'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $mdp = (string) ($payload['mdp'] ?? '');
        if ($nom === '' || $prenom === '' || $email === '' || $mdp === '') {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (preg_match('/\d/', $nom) || preg_match('/\d/', $prenom)) {
            echo json_encode(['success' => false, 'message' => 'Le nom et le prénom ne doivent pas contenir de chiffres.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (strlen($mdp) < 6) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Adresse e-mail invalide.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $role = 'utilisateur';
        try {
            $user = new User(null, $nom, $prenom, $email, $mdp, $role);
        } catch (InvalidArgumentException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($controller->emailExists($email)) {
            echo json_encode(['success' => false, 'message' => 'Cet e-mail est déjà utilisé.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($controller->addUser($user)) {
            echo json_encode(['success' => true, 'message' => 'Inscription réussie ! Vous pouvez vous connecter.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    if ($action === 'login') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $email = trim((string) ($payload['email'] ?? ''));
    $mdp   = (string) ($payload['mdp'] ?? '');

    if ($email === '' || $mdp === '') {
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $controller->login($email, $mdp);

    if ($result['status'] === 'ok') {
        echo json_encode(['success' => true, 'data' => $result['data']], JSON_UNESCAPED_UNICODE);
    } elseif ($result['status'] === 'account_not_found') {
        echo json_encode(['success' => false, 'message' => 'Compte n\'existe pas.'], JSON_UNESCAPED_UNICODE);
    } elseif ($result['status'] === 'wrong_password') {
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur interne du serveur.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

    echo json_encode(['success' => false, 'message' => 'Action inconnue'], JSON_UNESCAPED_UNICODE);
    exit;
}