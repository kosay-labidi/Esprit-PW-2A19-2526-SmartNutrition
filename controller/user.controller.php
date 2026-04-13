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
                return ['status' => 'account_not_found', 'data' => null];
            }

            if (!password_verify($mdp, $row['mdp'])) {
                return ['status' => 'wrong_password', 'data' => null];
            }

            unset($row['mdp']);
            return ['status' => 'ok', 'data' => $row];

        } catch (Exception $e) {
            return ['status' => 'error', 'data' => null];
        }
    }

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

    public function getUserById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $sql = 'SELECT id_utilisateur, nom, prenom, email, mdp, role, date_creation, date_mise_a_jour
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

    public function deleteUser(int $id): bool
    {
        if ($id < 1) {
            return false;
        }
        
        $sql = 'DELETE FROM utilisateurs WHERE id_utilisateur = :id';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            return $query->execute(['id' => $id]);
        } catch (Exception $e) {
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }
    public function updateUser(User $user): bool
{
    $sql = 'UPDATE utilisateurs 
            SET nom = :nom, 
                prenom = :prenom, 
                email = :email, 
                role = :role
            WHERE id_utilisateur = :id';
    
    try {
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        
        $result = $query->execute([
            'id' => $user->getIdUtilisateur(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ]);
        
        // Debug
        error_log("Update result: " . ($result ? "true" : "false"));
        error_log("SQL: " . $sql);
        error_log("Params: id=" . $user->getIdUtilisateur() . ", nom=" . $user->getNom() . ", prenom=" . $user->getPrenom() . ", email=" . $user->getEmail() . ", role=" . $user->getRole());
        
        return $result;
    } catch (Exception $e) {
        error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
        return false;
    }
}
}