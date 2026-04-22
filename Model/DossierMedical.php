<?php
class DossierMedical {
    private ?int $id_dossier;
    private ?int $id_utilisateur;
    private ?int $id_regime;
    private ?string $date_creation;
    private ?string $date_mise_a_jour;
    private ?string $groupe_sanguin;
    private ?float $poids;
    private ?float $taille;
    private ?float $imc;
    private ?string $regime_special;
    private ?string $notes_medecin;
    private ?string $allergie;
    private ?string $gravite_allergie;
    private ?string $maladies;
    private ?string $traitement;
    private ?string $medecin;
    private ?string $contact_en_cas_durgence;

    public function __construct(?int $id_dossier = null, ?int $id_utilisateur = null, ?int $id_regime = null, ?string $date_creation = null, 
                                ?string $date_mise_a_jour = null, ?string $groupe_sanguin = null, ?float $poids = null, 
                                ?float $taille = null, ?float $imc = null, ?string $regime_special = null, 
                                ?string $notes_medecin = null, ?string $allergie = null, ?string $gravite_allergie = null, 
                                ?string $maladies = null, ?string $traitement = null, ?string $medecin = null, 
                                ?string $contact_en_cas_durgence = null) {
        $this->id_dossier = $id_dossier;
        $this->id_utilisateur = $id_utilisateur;
        $this->id_regime = $id_regime;
        $this->date_creation = $date_creation;
        $this->date_mise_a_jour = $date_mise_a_jour;
        $this->groupe_sanguin = $groupe_sanguin;
        $this->poids = $poids;
        $this->taille = $taille;
        $this->imc = $imc;
        $this->regime_special = $regime_special;
        $this->notes_medecin = $notes_medecin;
        $this->allergie = $allergie;
        $this->gravite_allergie = $gravite_allergie;
        $this->maladies = $maladies;
        $this->traitement = $traitement;
        $this->medecin = $medecin;
        $this->contact_en_cas_durgence = $contact_en_cas_durgence;
    }

    // Getters
    public function getIdDossier() { return $this->id_dossier; }
    public function getIdUtilisateur() { return $this->id_utilisateur; }
    public function getIdRegime() { return $this->id_regime; }
    public function getDateCreation() { return $this->date_creation; }
    public function getDateMiseAJour() { return $this->date_mise_a_jour; }
    public function getGroupeSanguin() { return $this->groupe_sanguin; }
    public function getPoids() { return $this->poids; }
    public function getTaille() { return $this->taille; }
    public function getImc() { return $this->imc; }
    public function getRegimeSpecial() { return $this->regime_special; }
    public function getNotesMedecin() { return $this->notes_medecin; }
    public function getAllergie() { return $this->allergie; }
    public function getGraviteAllergie() { return $this->gravite_allergie; }
    public function getMaladies() { return $this->maladies; }
    public function getTraitement() { return $this->traitement; }
    public function getMedecin() { return $this->medecin; }
    public function getContactUrgence() { return $this->contact_en_cas_durgence; }

    // Setters
    public function setIdDossier(?int $id_dossier) { $this->id_dossier = $id_dossier; }
    public function setIdUtilisateur(?int $id_utilisateur) { $this->id_utilisateur = $id_utilisateur; }
    public function setIdRegime(?int $id_regime) { $this->id_regime = $id_regime; }
    public function setDateCreation(?string $date_creation) { $this->date_creation = $date_creation; }
    public function setDateMiseAJour(?string $date_mise_a_jour) { $this->date_mise_a_jour = $date_mise_a_jour; }
    public function setGroupeSanguin(?string $groupe_sanguin) { $this->groupe_sanguin = $groupe_sanguin; }
    public function setPoids(?float $poids) { $this->poids = $poids; }
    public function setTaille(?float $taille) { $this->taille = $taille; }
    public function setImc(?float $imc) { $this->imc = $imc; }
    public function setRegimeSpecial(?string $regime_special) { $this->regime_special = $regime_special; }
    public function setNotesMedecin(?string $notes_medecin) { $this->notes_medecin = $notes_medecin; }
    public function setAllergie(?string $allergie) { $this->allergie = $allergie; }
    public function setGraviteAllergie(?string $gravite_allergie) { $this->gravite_allergie = $gravite_allergie; }
    public function setMaladies(?string $maladies) { $this->maladies = $maladies; }
    public function setTraitement(?string $traitement) { $this->traitement = $traitement; }
    public function setMedecin(?string $medecin) { $this->medecin = $medecin; }
    public function setContactUrgence(?string $contact_en_cas_durgence) { $this->contact_en_cas_durgence = $contact_en_cas_durgence; }

    // Helper methods
    public function calculateImc() {
        if ($this->poids && $this->taille && $this->taille > 0) {
            return $this->poids / (($this->taille / 100) ** 2);
        }
        return null;
    }

    public function getImcCategory() {
        if (!$this->imc) return null;
        if ($this->imc < 18.5) return 'underweight';
        if ($this->imc < 25) return 'normal';
        if ($this->imc < 30) return 'overweight';
        return 'obese';
    }

    public function getImcCategoryLabel() {
        $category = $this->getImcCategory();
        switch ($category) {
            case 'underweight': return 'Insuffisance pondérale';
            case 'normal': return 'Poids normal';
            case 'overweight': return 'Surpoids';
            case 'obese': return 'Obésité';
            default: return 'Non calculé';
        }
    }

    public function toArray() {
        return [
            'id_dossier' => $this->id_dossier,
            'id_utilisateur' => $this->id_utilisateur,
            'date_creation' => $this->date_creation,
            'date_mise_a_jour' => $this->date_mise_a_jour,
            'groupe_sanguin' => $this->groupe_sanguin,
            'poids' => $this->poids,
            'taille' => $this->taille,
            'imc' => $this->imc,
            'regime_special' => $this->regime_special,
            'notes_medecin' => $this->notes_medecin,
            'allergie' => $this->allergie,
            'gravite_allergie' => $this->gravite_allergie,
            'maladies' => $this->maladies,
            'traitement' => $this->traitement,
            'medecin' => $this->medecin,
            'contact_en_cas_durgence' => $this->contact_en_cas_durgence
        ];
    }

    public function validate() {
        $errors = [];

        if ($this->poids !== null && $this->poids <= 0) {
            $errors[] = "Le poids doit être supérieur à 0";
        }

        if ($this->taille !== null && $this->taille <= 0) {
            $errors[] = "La taille doit être supérieure à 0";
        }

        if ($this->groupe_sanguin && !preg_match('/^(A|B|AB|O)[+-]$/', $this->groupe_sanguin)) {
            $errors[] = "Le groupe sanguin doit être au format A+, B-, AB+, O-, etc.";
        }

        return $errors;
    }
}
?>