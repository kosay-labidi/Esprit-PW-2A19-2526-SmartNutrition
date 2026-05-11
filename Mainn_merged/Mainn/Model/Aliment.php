<?php
/**
 * Aliment.php — Modèle Aliment
 * Intégré depuis GSRepasVF2 dans le projet Mainn
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/aliment.controller.php';

class Aliment {

    private $pdo;

    public function __construct() {
        $this->pdo = Config::getConnexion();
    }

    public function create($data) {
        return aliment_create($this->pdo, $data);
    }

    public function getAll() {
        return aliment_getAll($this->pdo);
    }

    public function getById($id) {
        return aliment_getById($this->pdo, $id);
    }

    public function update($id, $data) {
        aliment_update($this->pdo, $id, $data);
    }

    public function delete($id) {
        aliment_delete($this->pdo, $id);
    }

    public function search($query) {
        return aliment_search($this->pdo, $query);
    }
}
