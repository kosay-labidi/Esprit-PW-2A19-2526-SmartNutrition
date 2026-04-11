<?php
class DossierMedical {
    private ?int $id_dossier;
    private ?int $id_utilisateur;
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

    public function __construct(?int $id_dossier = null, ?int $id_utilisateur = null, ?string $date_creation = null, 
                                ?string $date_mise_a_jour = null, ?string $groupe_sanguin = null, ?float $poids = null, 
                                ?float $taille = null, ?float $imc = null, ?string $regime_special = null, 
                                ?string $notes_medecin = null, ?string $allergie = null, ?string $gravite_allergie = null, 
                                ?string $maladies = null, ?string $traitement = null, ?string $medecin = null, 
                                ?string $contact_en_cas_durgence = null) {
        $this->id_dossier = $id_dossier;
        $this->id_utilisateur = $id_utilisateur;
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

    public function getIdDossier() { return $this->id_dossier; }
    public function getIdUtilisateur() { return $this->id_utilisateur; }
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
}
?>