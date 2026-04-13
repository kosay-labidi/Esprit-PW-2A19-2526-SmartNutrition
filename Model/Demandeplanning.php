<?php
class Demandeplanning {
    private ?int $id;
    private ?int $id_utilisateur;
    private ?int $calories;
    private ?float $budget;
    private ?string $type_budget;
    private ?int $duree;
    private ?string $type_duree;
    private ?DateTime $date_demande;

    // Constructor
    public function __construct(
        ?int $id = null,
        ?int $id_utilisateur = null,
        ?int $calories = null,
        ?float $budget = null,
        ?string $type_budget = null,
        ?int $duree = null,
        ?string $type_duree = null,
        ?DateTime $date_demande = null
    ) {
        $this->id = $id;
        $this->id_utilisateur = $id_utilisateur;
        $this->calories = $calories;
        $this->budget = $budget;
        $this->type_budget = $type_budget;
        $this->duree = $duree;
        $this->type_duree = $type_duree;
        $this->date_demande = $date_demande;
    }

    // Getters
    public function getId(): ?int {
        return $this->id;
    }

    public function getIdUtilisateur(): ?int {
        return $this->id_utilisateur;
    }

    public function getCalories(): ?int {
        return $this->calories;
    }

    public function getBudget(): ?float {
        return $this->budget;
    }

    public function getTypeBudget(): ?string {
        return $this->type_budget;
    }

    public function getDuree(): ?int {
        return $this->duree;
    }

    public function getTypeDuree(): ?string {
        return $this->type_duree;
    }

    public function getDateDemande(): ?DateTime {
        return $this->date_demande;
    }

    // Setters
    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setIdUtilisateur(?int $id_utilisateur): void {
        $this->id_utilisateur = $id_utilisateur;
    }

    public function setCalories(?int $calories): void {
        $this->calories = $calories;
    }

    public function setBudget(?float $budget): void {
        $this->budget = $budget;
    }

    public function setTypeBudget(?string $type_budget): void {
        $this->type_budget = $type_budget;
    }

    public function setDuree(?int $duree): void {
        $this->duree = $duree;
    }

    public function setTypeDuree(?string $type_duree): void {
        $this->type_duree = $type_duree;
    }

    public function setDateDemande(?DateTime $date_demande): void {
        $this->date_demande = $date_demande;
    }
}
?>