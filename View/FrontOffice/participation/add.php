<?php
require_once __DIR__ . '/../../../config.php';

$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['nom_complet']) && !empty($_POST['email']) && !empty($_POST['id_event'])) {
        
        $db = config::getConnexion();
        
        $sql = "INSERT INTO participation (id_event, nom_complet, email, telephone, centre_interet, statut) 
                VALUES (:id_event, :nom_complet, :email, :telephone, :centre_interet, 'en_attente')";
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':id_event' => (int)$_POST['id_event'],
                ':nom_complet' => trim($_POST['nom_complet']),
                ':email' => trim($_POST['email']),
                ':telephone' => trim($_POST['telephone'] ?? ''),
                ':centre_interet' => trim($_POST['centre_interet'] ?? '')
            ]);
            
            if ($result) {
                $success = true;
                header("Location: ../index.php?success=1");
                exit();
            } else {
                $error = "Erreur SQL";
            }
        } catch (PDOException $e) {
            $error = "Exception : " . $e->getMessage();
        }
        
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

if ($error) {
    header("Location: ../index.php?error=" . urlencode($error));
    exit();
}
?>