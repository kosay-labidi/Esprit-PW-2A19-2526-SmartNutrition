<?php
class Participant {
    private $id;
    private $id_challenge;
    private $nom;
    private $email;
    private $objectif;
    private $motivation;
    private $action;
    private $engagement;
    private $notifications;

    public function __construct($id_challenge, $nom, $email, $objectif, $motivation, $action, $engagement, $notifications) {
        $this->id_challenge = $id_challenge;
        $this->nom = $nom;
        $this->email = $email;
        $this->objectif = $objectif;
        $this->motivation = $motivation;
        $this->action = $action;
        $this->engagement = $engagement;
        $this->notifications = $notifications;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIdChallenge() { return $this->id_challenge; }
    public function getNom() { return $this->nom; }
    public function getEmail() { return $this->email; }
    public function getObjectif() { return $this->objectif; }
    public function getMotivation() { return $this->motivation; }
    public function getAction() { return $this->action; }
    public function getEngagement() { return $this->engagement; }
    public function getNotifications() { return $this->notifications; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setIdChallenge($id_challenge) { $this->id_challenge = $id_challenge; }
    public function setNom($nom) { $this->nom = $nom; }
    public function setEmail($email) { $this->email = $email; }
    public function setObjectif($objectif) { $this->objectif = $objectif; }
    public function setMotivation($motivation) { $this->motivation = $motivation; }
    public function setAction($action) { $this->action = $action; }
    public function setEngagement($engagement) { $this->engagement = $engagement; }
    public function setNotifications($notifications) { $this->notifications = $notifications; }
}
?>
