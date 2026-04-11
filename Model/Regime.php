<?php
class Regime {
    private ?int $id_regime;
    private ?string $nom_regime;
    private ?string $slug;
    private ?string $description;
    private ?string $type_regime;
    private ?string $niveau_difficulte;
    private ?string $aliments_interdits;
    private ?string $aliments_recommandes;
    private ?float $apport_calorique_moyen;

    public function __construct(?int $id_regime = null, ?string $nom_regime = null, ?string $slug = null, 
                                ?string $description = null, ?string $type_regime = null, 
                                ?string $niveau_difficulte = null, ?string $aliments_interdits = null, 
                                ?string $aliments_recommandes = null, ?float $apport_calorique_moyen = null) {
        $this->id_regime = $id_regime;
        $this->nom_regime = $nom_regime;
        $this->slug = $slug;
        $this->description = $description;
        $this->type_regime = $type_regime;
        $this->niveau_difficulte = $niveau_difficulte;
        $this->aliments_interdits = $aliments_interdits;
        $this->aliments_recommandes = $aliments_recommandes;
        $this->apport_calorique_moyen = $apport_calorique_moyen;
    }

    public function getIdRegime() { return $this->id_regime; }
    public function getNomRegime() { return $this->nom_regime; }
    public function getSlug() { return $this->slug; }
    public function getDescription() { return $this->description; }
    public function getTypeRegime() { return $this->type_regime; }
    public function getNiveauDifficulte() { return $this->niveau_difficulte; }
    public function getAlimentsInterdits() { return $this->aliments_interdits; }
    public function getAlimentsRecommandes() { return $this->aliments_recommandes; }
    public function getApportCalorique() { return $this->apport_calorique_moyen; }
}
?>