<?php

require_once __DIR__ . '/../config.php';

class Aliment {
    
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // CREATE
    public function create($data) {
        $sql = "INSERT INTO aliments (nom, type, categorie, calories, proteines, glucides, lipides, fibres, sucre, sodium, vitamines, co2, label_ecologique, prix, origine, allergenes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nom'], $data['type'], $data['categorie'], $data['calories'],
            $data['proteines'], $data['glucides'], $data['lipides'], $data['fibres'],
            $data['sucre'], $data['sodium'], $data['vitamines'], $data['co2'],
            $data['label_ecologique'], $data['prix'], $data['origine'], $data['allergenes']
        ]);
        return $this->pdo->lastInsertId();
    }

    // READ ALL
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM aliments ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ ONE
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM aliments WHERE id_aliment = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $data) {
        $sql = "UPDATE aliments SET nom=?, type=?, categorie=?, calories=?, proteines=?, glucides=?, lipides=?, fibres=?, sucre=?, sodium=?, vitamines=?, co2=?, label_ecologique=?, prix=?, origine=?, allergenes=? WHERE id_aliment=?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['nom'], $data['type'], $data['categorie'], $data['calories'],
            $data['proteines'], $data['glucides'], $data['lipides'], $data['fibres'],
            $data['sucre'], $data['sodium'], $data['vitamines'], $data['co2'],
            $data['label_ecologique'], $data['prix'], $data['origine'], $data['allergenes'],
            $id
        ]);
    }

    // DELETE
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM aliments WHERE id_aliment = ?");
        $stmt->execute([$id]);
    }
}
?>