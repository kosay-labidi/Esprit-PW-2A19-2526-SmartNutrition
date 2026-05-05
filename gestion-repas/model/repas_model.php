<?php

require_once __DIR__ . '/../controller/repascontroller.php';

class Repas {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function create(array $data): int {
        return repas_create($this->pdo, $data);
    }

    public function attachAliments(int $idRepas, array $aliments, array $quantites): void {
        repas_attachAliments($this->pdo, $idRepas, $aliments, $quantites);
    }

    public function detachAliments(int $idRepas): void {
        repas_detachAliments($this->pdo, $idRepas);
    }

    public function getAll(): array {
        return repas_getAll($this->pdo);
    }

    public function getAllByUser(int $idUser): array {
        return repas_getAllByUser($this->pdo, $idUser);
    }

    public function getById(int $id): array|false {
        return repas_getById($this->pdo, $id);
    }

    public function getAlimentsOfRepas(int $idRepas): array {
        return repas_getAlimentsOfRepas($this->pdo, $idRepas);
    }

    public function getTotauxNutritionnels(int $idRepas): array {
        return repas_getTotauxNutritionnels($this->pdo, $idRepas);
    }

    public function update(int $id, array $data): void {
        repas_update($this->pdo, $id, $data);
    }

    public function delete(int $id): void {
        repas_delete($this->pdo, $id);
    }

    public function existsByNomDate(string $nom, string $date, int $userId, int $excludeId = 0): bool {
        return repas_existsByNomDate($this->pdo, $nom, $date, $userId, $excludeId);
    }
}
?>
