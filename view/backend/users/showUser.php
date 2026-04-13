<?php
require_once __DIR__ . '/../../../controller/user.controller.php';
require_once __DIR__ . '/../../../config.php';

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
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    th, td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }
    th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }
    tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    tr:hover {
        background-color: #f0f0f0;
        transition: background 0.3s;
    }
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: bold;
    }
    .badge-admin {
        background: linear-gradient(135deg, #ff9800, #f57c00);
        color: white;
    }
    .badge-user {
        background: linear-gradient(135deg, #2196F3, #1976D2);
        color: white;
    }
    .action-btns {
        display: flex;
        gap: 10px;
    }
    .btn-edit, .btn-delete {
        padding: 6px 14px;
        text-decoration: none;
        border-radius: 6px;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-edit {
        background: linear-gradient(135deg, #2196F3, #1976D2);
    }
    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
    }
    .btn-delete {
        background: linear-gradient(135deg, #f44336, #d32f2f);
    }
    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
    }
    .btn-edit:active, .btn-delete:active {
        transform: translateY(0);
    }
    .btn-add {
        display: inline-block;
        margin: 20px 0;
        padding: 10px 24px;
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
    .message {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 6px;
        text-align: center;
        animation: fadeOut 3s forwards;
        font-weight: 500;
    }
    .success {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
    }
    .error {
        background: linear-gradient(135deg, #f44336, #d32f2f);
        color: white;
    }
    @keyframes fadeOut {
        0% { opacity: 1; transform: translateY(0); }
        70% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(-10px); display: none; }
    }
    h1 {
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
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