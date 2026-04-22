<?php
class Demandeplanning {
    private ?int    $id;
    private ?int    $id_utilisateur;
    private ?int    $calories;
    private ?float  $budget;
    private ?string $type_budget;
    private ?int    $duree;
    private ?string $type_duree;
    private ?string $statut;       // en_attente | approuve | rejete
    private ?string $date_demande;

    public function __construct(
        ?int    $id             = null,
        ?int    $id_utilisateur = null,
        ?int    $calories       = null,
        ?float  $budget         = null,
        ?string $type_budget    = null,
        ?int    $duree          = null,
        ?string $type_duree     = null,
        ?string $statut         = 'en_attente',
        ?string $date_demande   = null
    ) {
        $this->id             = $id;
        $this->id_utilisateur = $id_utilisateur;
        $this->calories       = $calories;
        $this->budget         = $budget;
        $this->type_budget    = $type_budget;
        $this->duree          = $duree;
        $this->type_duree     = $type_duree;
        $this->statut         = $statut ?? 'en_attente';
        $this->date_demande   = $date_demande;
    }

    public function getId(): ?int            { return $this->id; }
    public function getIdUtilisateur(): ?int { return $this->id_utilisateur; }
    public function getCalories(): ?int      { return $this->calories; }
    public function getBudget(): ?float      { return $this->budget; }
    public function getTypeBudget(): ?string { return $this->type_budget; }
    public function getDuree(): ?int         { return $this->duree; }
    public function getTypeDuree(): ?string  { return $this->type_duree; }
    public function getStatut(): ?string     { return $this->statut; }
    public function getDateDemande(): ?string { return $this->date_demande; }

    public function setId(?int $v): void             { $this->id = $v; }
    public function setIdUtilisateur(?int $v): void  { $this->id_utilisateur = $v; }
    public function setCalories(?int $v): void       { $this->calories = $v; }
    public function setBudget(?float $v): void       { $this->budget = $v; }
    public function setTypeBudget(?string $v): void  { $this->type_budget = $v; }
    public function setDuree(?int $v): void          { $this->duree = $v; }
    public function setTypeDuree(?string $v): void   { $this->type_duree = $v; }
    public function setStatut(?string $v): void      { $this->statut = $v; }
    public function setDateDemande(?string $v): void { $this->date_demande = $v; }
}
?>
