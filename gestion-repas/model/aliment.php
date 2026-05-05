<?php

require_once __DIR__ . '/../controller/alimentcontroller.php';

class Aliment {
    
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
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
}
?>
