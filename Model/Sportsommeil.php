<?php
/**
 * Model : SportSommeil
 * Entité représentant le formulaire étape 2 (sport + sommeil)
 * Liée à DemandePlanning via id_demande.
 *
 * @package SmartNutrition\Model
 */
class SportSommeil {

    // ── Attributs ──────────────────────────────────────────────
    private ?int    $id;
    private ?int    $id_demande;
    private ?string $activite_sportive;
    private ?int    $duree_sport_hebdo;   // en minutes
    private ?string $heure_coucher;       // format HH:MM
    private ?string $heure_reveil;        // format HH:MM
    private ?string $qualite_sommeil;     // mauvaise / passable / bonne / excellente
    private ?string $created_at;

    // ── Constructeur ───────────────────────────────────────────
    public function __construct(
        ?int    $id                = null,
        ?int    $id_demande        = null,
        ?string $activite_sportive = null,
        ?int    $duree_sport_hebdo = null,
        ?string $heure_coucher     = null,
        ?string $heure_reveil      = null,
        ?string $qualite_sommeil   = null,
        ?string $created_at        = null
    ) {
        $this->id                = $id;
        $this->id_demande        = $id_demande;
        $this->activite_sportive = $activite_sportive;
        $this->duree_sport_hebdo = $duree_sport_hebdo;
        $this->heure_coucher     = $heure_coucher;
        $this->heure_reveil      = $heure_reveil;
        $this->qualite_sommeil   = $qualite_sommeil;
        $this->created_at        = $created_at;
    }

    // ── Getters ────────────────────────────────────────────────
    public function getId(): ?int            { return $this->id; }
    public function getIdDemande(): ?int     { return $this->id_demande; }
    public function getActiviteSportive(): ?string { return $this->activite_sportive; }
    public function getDureSportHebdo(): ?int      { return $this->duree_sport_hebdo; }
    public function getHeureCoucher(): ?string     { return $this->heure_coucher; }
    public function getHeureReveil(): ?string      { return $this->heure_reveil; }
    public function getQualiteSommeil(): ?string   { return $this->qualite_sommeil; }
    public function getCreatedAt(): ?string         { return $this->created_at; }

    /**
     * Calcule la durée de sommeil en heures à partir de heure_coucher et heure_reveil.
     * Gère le passage minuit (ex: 23h → 07h = 8h).
     */
    public function getDureeSommeilHeures(): float {
        if (!$this->heure_coucher || !$this->heure_reveil) return 0.0;
        [$hC, $mC] = array_map('intval', explode(':', $this->heure_coucher));
        [$hR, $mR] = array_map('intval', explode(':', $this->heure_reveil));
        $minCoucher = $hC * 60 + $mC;
        $minReveil  = $hR * 60 + $mR;
        // Si réveil < coucher → passage minuit
        if ($minReveil <= $minCoucher) $minReveil += 24 * 60;
        return round(($minReveil - $minCoucher) / 60, 1);
    }

    // ── Setters ────────────────────────────────────────────────
    public function setId(?int $id): void                       { $this->id = $id; }
    public function setIdDemande(?int $id_demande): void        { $this->id_demande = $id_demande; }
    public function setActiviteSportive(?string $v): void       { $this->activite_sportive = $v; }
    public function setDureSportHebdo(?int $v): void            { $this->duree_sport_hebdo = $v; }
    public function setHeureCoucher(?string $v): void           { $this->heure_coucher = $v; }
    public function setHeureReveil(?string $v): void            { $this->heure_reveil = $v; }
    public function setQualiteSommeil(?string $v): void         { $this->qualite_sommeil = $v; }
    public function setCreatedAt(?string $v): void              { $this->created_at = $v; }
}
?>