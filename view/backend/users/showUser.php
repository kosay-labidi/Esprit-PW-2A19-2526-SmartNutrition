<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

requireAdmin();
$userC = new UserController();
$list = $userC->listUsers();

$deleted = isset($_GET['deleted']) ? $_GET['deleted'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Utilisateurs</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-admin {
            background-color: #ff9800;
            color: white;
        }
        .badge-user {
            background-color: #2196F3;
            color: white;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            animation: fadeOut 3s forwards;
        }
        
        .success {
            background-color: #4CAF50;
            color: white;
        }
        
        .error {
            background-color: #f44336;
            color: white;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
    </style>
</head>
<body>
    <?php if ($deleted == 1): ?>
        <div class="message success">
            ✅ Utilisateur supprimé avec succès !
        </div>
    <?php elseif ($error == 1): ?>
        <div class="message error">
            ❌ Erreur lors de la suppression de l'utilisateur !
        </div>
    <?php endif; ?>
    <h1>📋 Liste des Utilisateurs</h1>
    
    <button onclick="addUser()" class="btn-add" style="display: inline-block; margin: 20px 0; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer;">➕ Ajouter un utilisateur</button>
    
    <table id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom Complet</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Date d'inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($list && count($list) > 0) {
                foreach ($list as $user) {
                    $badgeClass = ($user['role'] === 'admin') ? 'badge-admin' : 'badge-user';
                    ?>
                    <tr id="user-row-<?php echo $user['id']; ?>">
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick="editUser(<?php echo $user['id']; ?>)" class="btn-edit">✏️ Modifier</button>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-delete">🗑️ Supprimer</button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>Aucun utilisateur trouvé</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>