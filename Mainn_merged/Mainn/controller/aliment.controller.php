<?php
/**
 * aliment.controller.php — CRUD Aliments
 * Intégré depuis GSRepasVF2 dans le projet Mainn
 * Utilise Config::getConnexion() de Mainn
 */

require_once __DIR__ . '/../config.php';

/* ============================================================
   FONCTIONS SQL — Aliment
   ============================================================ */

function aliment_create($pdo, $data) {
    $sql = "INSERT INTO aliments (nom, type, categorie, calories, proteines, glucides, lipides, fibres, sucre, sodium, vitamines, co2, label_ecologique, prix, origine, allergenes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nom'], $data['type'], $data['categorie'], $data['calories'],
        $data['proteines'], $data['glucides'], $data['lipides'], $data['fibres'],
        $data['sucre'], $data['sodium'], $data['vitamines'], $data['co2'],
        $data['label_ecologique'], $data['prix'], $data['origine'], $data['allergenes']
    ]);
    return $pdo->lastInsertId();
}

function aliment_getAll($pdo) {
    $stmt = $pdo->query("SELECT * FROM aliments ORDER BY nom ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function aliment_getById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM aliments WHERE id_aliment = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function aliment_update($pdo, $id, $data) {
    $sql = "UPDATE aliments SET nom=?, type=?, categorie=?, calories=?, proteines=?, glucides=?, lipides=?, fibres=?, sucre=?, sodium=?, vitamines=?, co2=?, label_ecologique=?, prix=?, origine=?, allergenes=? WHERE id_aliment=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nom'], $data['type'], $data['categorie'], $data['calories'],
        $data['proteines'], $data['glucides'], $data['lipides'], $data['fibres'],
        $data['sucre'], $data['sodium'], $data['vitamines'], $data['co2'],
        $data['label_ecologique'], $data['prix'], $data['origine'], $data['allergenes'],
        $id
    ]);
}

function aliment_delete($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM aliments WHERE id_aliment = ?");
    $stmt->execute([$id]);
}

function aliment_search($pdo, $query) {
    $stmt = $pdo->prepare("SELECT * FROM aliments WHERE nom LIKE ? OR type LIKE ? ORDER BY nom ASC");
    $q = '%' . $query . '%';
    $stmt->execute([$q, $q]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   ROUTING — JSON API si appelé directement
   ============================================================ */
if (basename($_SERVER['SCRIPT_FILENAME']) !== basename(__FILE__)) { return; }

$pdo = Config::getConnexion();

// API JSON pour les appels AJAX (recherche d'aliments)
if (isset($_GET['api']) && $_GET['api'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) {
        echo json_encode([]);
    } else {
        echo json_encode(aliment_search($pdo, $q));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            aliment_create($pdo, $data);
            header("Location: ../view/backend/meals/listAliments.php?success=created");
        } elseif ($_POST['action'] === 'update') {
            aliment_update($pdo, $_POST['id_aliment'], $data);
            header("Location: ../view/backend/meals/listAliments.php?success=updated");
        }
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    aliment_delete($pdo, $_GET['id']);
    header("Location: ../view/backend/meals/listAliments.php?success=deleted");
    exit;
}
