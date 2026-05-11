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
                'nom'    => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email'  => $user->getEmail(),
                'mdp'    => $mdpHash,
                'role'   => $user->getRole(),
            ]);
        } catch (Exception $e) {
            error_log("Erreur addUser: " . $e->getMessage());
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

        $sql = 'SELECT id_utilisateur, nom, prenom, email, mdp, role, photo, status
                FROM utilisateurs WHERE email = :email LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if ($row['status'] === 'inactif') {
    return ['status' => 'account_inactive', 'data' => null];
}

            if (!$row) {
                return ['status' => 'account_not_found', 'data' => null];
            }

            // Vérifier si le compte est inactif
            if ($row['status'] === 'inactif') {
                return ['status' => 'account_inactive', 'data' => null];
            }

            if (!password_verify($mdp, $row['mdp'])) {
                return ['status' => 'wrong_password', 'data' => null];
            }

            unset($row['mdp']);
            return ['status' => 'ok', 'data' => $row];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['status' => 'error', 'data' => null];
        }
    }

    public function listUsers(): array
    {
        $sql = 'SELECT id_utilisateur AS id, nom, prenom, email, role, photo, status,
                       date_creation AS date_inscription
                FROM utilisateurs
                ORDER BY date_creation DESC';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur listUsers: " . $e->getMessage());
            return [];
        }
    }

    public function getUserById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $sql = 'SELECT id_utilisateur, nom, prenom, email, mdp, role, photo, status,
                       date_creation, date_mise_a_jour
                FROM utilisateurs WHERE id_utilisateur = :id LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log("Erreur getUserById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un utilisateur avec ses informations complètes (photo, status)
     * Utilisé par auto_login.php
     */
    public function getUserFullData(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }
        $sql = 'SELECT id_utilisateur, nom, prenom, email, role, photo, status, date_creation
                FROM utilisateurs WHERE id_utilisateur = :id LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['id' => $userId]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Ajouter le libellé du statut
                $row['status_label'] = $row['status'] === 'actif' ? 'Actif' : 'Inactif';
                // Ajouter l'URL complète de la photo si existante
                if (!empty($row['photo']) && !preg_match('/^https?:\/\//', $row['photo'])) {
                    $row['photo_url'] = 'http://localhost/Mainn/' . $row['photo'];
                } else {
                    $row['photo_url'] = $row['photo'] ?? null;
                }
            }
            return $row;
        } catch (Exception $e) {
            error_log("Erreur getUserFullData: " . $e->getMessage());
            return null;
        }
    }

    public function getUserByEmail(string $email): ?array
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT id_utilisateur, nom, prenom, email, mdp, role, photo, status,
                       date_creation, date_mise_a_jour, provider, provider_id
                FROM utilisateurs WHERE email = :email LIMIT 1';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log("Erreur getUserByEmail: " . $e->getMessage());
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
            error_log("Erreur deleteUser: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser(User $user): bool
    {
        $sql = 'UPDATE utilisateurs
                SET nom    = :nom,
                    prenom = :prenom,
                    email  = :email,
                    role   = :role,
                    date_mise_a_jour = NOW()
                WHERE id_utilisateur = :id';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            return $query->execute([
                'id'     => $user->getIdUtilisateur(),
                'nom'    => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email'  => $user->getEmail(),
                'role'   => $user->getRole(),
            ]);
        } catch (Exception $e) {
            error_log("Erreur updateUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la photo de profil d'un utilisateur
     */
    public function updateUserPhoto(int $userId, string $photoPath): bool
    {
        if ($userId < 1) {
            return false;
        }
        $sql = 'UPDATE utilisateurs SET photo = :photo, date_mise_a_jour = NOW()
                WHERE id_utilisateur = :id';
        try {
            $db = config::getConnexion();
            $query = $db->prepare($sql);
            return $query->execute([
                'photo' => $photoPath,
                'id'    => $userId
            ]);
        } catch (Exception $e) {
            error_log("Erreur updateUserPhoto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Active ou désactive un compte utilisateur
     * @return array ['success' => bool, 'new_status' => string, 'message' => string]
     */
    public function toggleAccountStatus(int $userId): array
    {
        if ($userId < 1) {
            return ['success' => false, 'new_status' => null, 'message' => 'ID invalide'];
        }

        try {
            $db = config::getConnexion();
            
            // Récupérer le statut actuel
            $stmt = $db->prepare("SELECT status FROM utilisateurs WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $userId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                return ['success' => false, 'new_status' => null, 'message' => 'Utilisateur non trouvé'];
            }
            
            $newStatus = $current['status'] === 'actif' ? 'inactif' : 'actif';
            $message = $newStatus === 'actif' ? 'Compte réactivé avec succès' : 'Compte désactivé avec succès';
            
            $stmt = $db->prepare("UPDATE utilisateurs SET status = :status, date_mise_a_jour = NOW() 
                                  WHERE id_utilisateur = :id");
            $result = $stmt->execute(['status' => $newStatus, 'id' => $userId]);
            
            if ($result) {
                return ['success' => true, 'new_status' => $newStatus, 'message' => $message];
            } else {
                return ['success' => false, 'new_status' => null, 'message' => 'Erreur lors de la mise à jour'];
            }
        } catch (Exception $e) {
            error_log("Erreur toggleAccountStatus: " . $e->getMessage());
            return ['success' => false, 'new_status' => null, 'message' => 'Erreur technique'];
        }
    }

    // ── REMEMBER ME ──────────────────────────────────────────────────────────────

    public function saveRememberToken(int $userId, string $token, string $expires): bool
    {
        // Supprimer les anciens tokens de cet utilisateur
        $del = 'DELETE FROM remember_tokens WHERE id_utilisateur = :id';
        $ins = 'INSERT INTO remember_tokens (id_utilisateur, token, expires_at)
                VALUES (:id, :token, :expires)';
        try {
            $db = config::getConnexion();
            $db->prepare($del)->execute(['id' => $userId]);
            $stmt = $db->prepare($ins);
            return $stmt->execute([
                'id'      => $userId,
                'token'   => $token,
                'expires' => $expires,
            ]);
        } catch (Exception $e) {
            error_log("saveRememberToken: " . $e->getMessage());
            return false;
        }
    }

    public function getUserByRememberToken(string $token): ?array
    {
        $sql = 'SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.role, u.photo, u.status
                FROM remember_tokens rt
                JOIN utilisateurs u ON u.id_utilisateur = rt.id_utilisateur
                WHERE rt.token = :token AND rt.expires_at > NOW()
                LIMIT 1';
        try {
            $db    = config::getConnexion();
            $stmt  = $db->prepare($sql);
            $stmt->execute(['token' => $token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log("getUserByRememberToken: " . $e->getMessage());
            return null;
        }
    }

    public function deleteRememberToken(string $token): bool
    {
        $sql = 'DELETE FROM remember_tokens WHERE token = :token';
        try {
            $db = config::getConnexion();
            return $db->prepare($sql)->execute(['token' => $token]);
        } catch (Exception $e) {
            error_log("deleteRememberToken: " . $e->getMessage());
            return false;
        }
    }
}
?>