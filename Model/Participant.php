<?php
class Participant {
    private ?int $id;
    private ?int $id_challenge;
    private ?string $nom;
    private ?string $email;
    private ?int $objectif;
    private ?string $motivation;
    private ?string $action;
    private ?int $engagement;
    private ?int $notifications;
<<<<<<< HEAD
    private ?int $points;
    private ?int $days_active;
    private ?float $smart_score;

    public function __construct(?int $id_challenge, ?string $nom, ?string $email, ?int $objectif, ?string $motivation, ?string $action, ?int $engagement, ?int $notifications, int $points = 0, int $days_active = 1, float $smart_score = 0) {
=======

    public function __construct(?int $id_challenge, ?string $nom, ?string $email, ?int $objectif, ?string $motivation, ?string $action, ?int $engagement, ?int $notifications) {
>>>>>>> 1c5c0dc78ae19773dcd2a28e5572cfe3c293169c
        $this->id_challenge = $id_challenge;
        $this->nom = $nom;
        $this->email = $email;
        $this->objectif = $objectif;
        $this->motivation = $motivation;
        $this->action = $action;
        $this->engagement = $engagement;
        $this->notifications = $notifications;
<<<<<<< HEAD
        $this->points = $points;
        $this->days_active = $days_active;
        $this->smart_score = $smart_score;
=======
>>>>>>> 1c5c0dc78ae19773dcd2a28e5572cfe3c293169c
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getIdChallenge(): ?int { return $this->id_challenge; }
    public function getNom(): ?string { return $this->nom; }
    public function getEmail(): ?string { return $this->email; }
    public function getObjectif(): ?int { return $this->objectif; }
    public function getMotivation(): ?string { return $this->motivation; }
    public function getAction(): ?string { return $this->action; }
    public function getEngagement(): ?int { return $this->engagement; }
    public function getNotifications(): ?int { return $this->notifications; }
<<<<<<< HEAD
    public function getPoints(): ?int { return $this->points; }
    public function getDaysActive(): ?int { return $this->days_active; }
    public function getSmartScore(): ?float { return $this->smart_score; }
=======
>>>>>>> 1c5c0dc78ae19773dcd2a28e5572cfe3c293169c

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setIdChallenge(?int $id_challenge): void { $this->id_challenge = $id_challenge; }
    public function setNom(?string $nom): void { $this->nom = $nom; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setObjectif(?int $objectif): void { $this->objectif = $objectif; }
    public function setMotivation(?string $motivation): void { $this->motivation = $motivation; }
    public function setAction(?string $action): void { $this->action = $action; }
    public function setEngagement(?int $engagement): void { $this->engagement = $engagement; }
    public function setNotifications(?int $notifications): void { $this->notifications = $notifications; }
<<<<<<< HEAD
    public function setPoints(?int $points): void { $this->points = $points; }
    public function setDaysActive(?int $days_active): void { $this->days_active = $days_active; }
    public function setSmartScore(?float $smart_score): void { $this->smart_score = $smart_score; }
=======
>>>>>>> 1c5c0dc78ae19773dcd2a28e5572cfe3c293169c
}
?>
