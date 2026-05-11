<?php
class Challenge  {
    private ?int $id;
    private ?string $titre;
    private ?string $description;
    private ?string $type;
    private ?string $objectif;
    private ?int $valeur_cible;
    private ?string $date_debut;
    private ?string $date_fin;
    private ?string $statut;
    private ?string $streak_icon;
    private ?string $image;
    private ?int $est_payant;
    private ?float $prix;

    public function __construct(?int $id, ?string $titre, ?string $description, ?string $type, ?string $objectif, ?int $valeur_cible, ?string $date_debut, ?string $date_fin, ?string $statut, ?string $streak_icon, ?string $image, ?int $est_payant = 0, ?float $prix = 0) {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->type = $type;
        $this->objectif = $objectif;
        $this->valeur_cible = $valeur_cible;
        $this->date_debut = $date_debut;
        $this->date_fin = $date_fin;
        $this->statut = $statut;
        $this->streak_icon = $streak_icon;
        $this->image = $image;
        $this->est_payant = $est_payant;
        $this->prix = $prix;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function getDescription(): ?string { return $this->description; }
    public function getType(): ?string { return $this->type; }
    public function getObjectif(): ?string { return $this->objectif; }
    public function getValeurCible(): ?int { return $this->valeur_cible; }
    public function getDateDebut(): ?string { return $this->date_debut; }
    public function getDateFin(): ?string { return $this->date_fin; }
    public function getStatut(): ?string { return $this->statut; }
    public function getStreakIcon(): ?string { return $this->streak_icon; }
    public function getImage(): ?string { return $this->image; }
    public function getEstPayant(): ?int { return $this->est_payant; }
    public function getPrix(): ?float { return $this->prix; }

    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setType(string $type): void { $this->type = $type; }
    public function setObjectif(string $objectif): void { $this->objectif = $objectif; }
    public function setValeurCible(int $valeur_cible): void { $this->valeur_cible = $valeur_cible; }
    public function setDateDebut(string $date_debut): void { $this->date_debut = $date_debut; }
    public function setDateFin(string $date_fin): void { $this->date_fin = $date_fin; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setStreakIcon(string $streak_icon): void { $this->streak_icon = $streak_icon; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setEstPayant(int $est_payant): void { $this->est_payant = $est_payant; }
    public function setPrix(float $prix): void { $this->prix = $prix; }
}
?>
