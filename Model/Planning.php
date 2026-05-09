<?php
/**
 * Model : Planning
 * Représente une ligne du planning généré (sport ou sommeil).
 * Plusieurs lignes sont créées pour une même id_demande.
 *
 * @package SmartNutrition\Model
 */
class Planning {

    // ── Attributs ──────────────────────────────────────────────
    private ?int    $id;
    private ?int    $id_demande;
    private ?string $date;           // format YYYY-MM-DD
    private ?string $type_activite;  // 'repas' | 'sport' | 'sommeil'
    private ?string $description;    // ex: "Cardio 2h 18h-20h"
    private ?string $created_at;

    // ── Constructeur ───────────────────────────────────────────
    public function __construct(
        ?int    $id            = null,
        ?int    $id_demande    = null,
        ?string $date          = null,
        ?string $type_activite = null,
        ?string $description   = null,
        ?string $created_at    = null
    ) {
        $this->id            = $id;
        $this->id_demande    = $id_demande;
        $this->date          = $date;
        $this->type_activite = $type_activite;
        $this->description   = $description;
        $this->created_at    = $created_at;
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId(): ?int            { return $this->id; }
    public function getIdDemande(): ?int     { return $this->id_demande; }
    public function getDate(): ?string       { return $this->date; }
    public function getTypeActivite(): ?string { return $this->type_activite; }
    public function getDescription(): ?string  { return $this->description; }
    public function getCreatedAt(): ?string     { return $this->created_at; }

    // ── Setters ────────────────────────────────────────────────
    public function setId(?int $id): void                { $this->id = $id; }
    public function setIdDemande(?int $id_demande): void { $this->id_demande = $id_demande; }
    public function setDate(?string $date): void         { $this->date = $date; }
    public function setTypeActivite(?string $v): void    { $this->type_activite = $v; }
    public function setDescription(?string $v): void     { $this->description = $v; }
    public function setCreatedAt(?string $v): void       { $this->created_at = $v; }
}
?>