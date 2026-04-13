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

    // Getters
    public function getIdRegime() { return $this->id_regime; }
    public function getNomRegime() { return $this->nom_regime; }
    public function getSlug() { return $this->slug; }
    public function getDescription() { return $this->description; }
    public function getTypeRegime() { return $this->type_regime; }
    public function getNiveauDifficulte() { return $this->niveau_difficulte; }
    public function getAlimentsInterdits() { return $this->aliments_interdits; }
    public function getAlimentsRecommandes() { return $this->aliments_recommandes; }
    public function getApportCalorique() { return $this->apport_calorique_moyen; }

    // Setters
    public function setIdRegime(?int $id_regime) { $this->id_regime = $id_regime; }
    public function setNomRegime(?string $nom_regime) { $this->nom_regime = $nom_regime; }
    public function setSlug(?string $slug) { $this->slug = $slug; }
    public function setDescription(?string $description) { $this->description = $description; }
    public function setTypeRegime(?string $type_regime) { $this->type_regime = $type_regime; }
    public function setNiveauDifficulte(?string $niveau_difficulte) { $this->niveau_difficulte = $niveau_difficulte; }
    public function setAlimentsInterdits(?string $aliments_interdits) { $this->aliments_interdits = $aliments_interdits; }
    public function setAlimentsRecommandes(?string $aliments_recommandes) { $this->aliments_recommandes = $aliments_recommandes; }
    public function setApportCalorique(?float $apport_calorique_moyen) { $this->apport_calorique_moyen = $apport_calorique_moyen; }

    // Helper methods
    public function getAlimentsInterditsArray() {
        return json_decode($this->aliments_interdits ?: '[]', true) ?: [];
    }

    public function getAlimentsRecommandesArray() {
        return json_decode($this->aliments_recommandes ?: '[]', true) ?: [];
    }

    public function isAlimentAllowed($aliment) {
        $interdits = $this->getAlimentsInterditsArray();
        return !in_array(strtolower($aliment), array_map('strtolower', $interdits));
    }

    public function isAlimentRecommended($aliment) {
        $recommandes = $this->getAlimentsRecommandesArray();
        return in_array(strtolower($aliment), array_map('strtolower', $recommandes));
    }

    public function getTypeRegimeLabel() {
        $types = [
            'alimentaire' => 'Alimentaire',
            'medical' => 'Médical',
            'sportif' => 'Sportif',
            'perte_de_poids' => 'Perte de poids',
            'prise_de_masse' => 'Prise de masse',
            'autre' => 'Autre'
        ];
        return $types[$this->type_regime] ?? $this->type_regime;
    }

    public function getNiveauDifficulteLabel() {
        $niveaux = [
            'facile' => 'Facile',
            'modere' => 'Modéré',
            'avance' => 'Avancé'
        ];
        return $niveaux[$this->niveau_difficulte] ?? $this->niveau_difficulte;
    }

    public static function generateSlug($text = null) {
        if (is_null($text) || empty($text)) {
            return '';
        }
        return strtolower(str_replace([' ', '_'], '-', trim($text)));
    }

    public function toArray() {
        return [
            'id_regime' => $this->id_regime,
            'nom_regime' => $this->nom_regime,
            'slug' => $this->slug,
            'description' => $this->description,
            'type_regime' => $this->type_regime,
            'niveau_difficulte' => $this->niveau_difficulte,
            'aliments_interdits' => $this->aliments_interdits,
            'aliments_recommandes' => $this->aliments_recommandes,
            'apport_calorique_moyen' => $this->apport_calorique_moyen
        ];
    }

    public function validate() {
        $errors = [];

        if (empty($this->nom_regime)) {
            $errors[] = "Le nom du régime est obligatoire";
        }

        if (empty($this->type_regime)) {
            $errors[] = "Le type de régime est obligatoire";
        }

        if (empty($this->niveau_difficulte)) {
            $errors[] = "Le niveau de difficulté est obligatoire";
        }

        $validTypes = ['alimentaire', 'medical', 'sportif', 'perte_de_poids', 'prise_de_masse', 'autre'];
        if (!in_array($this->type_regime, $validTypes)) {
            $errors[] = "Type de régime invalide";
        }

        $validNiveaux = ['facile', 'modere', 'avance'];
        if (!in_array($this->niveau_difficulte, $validNiveaux)) {
            $errors[] = "Niveau de difficulté invalide";
        }

        return $errors;
    }
}
?>