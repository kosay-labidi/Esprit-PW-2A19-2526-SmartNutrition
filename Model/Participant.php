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
    private ?int $score;
    private ?int $xp;
    private ?int $level;

    public function __construct(?int $id_challenge, ?string $nom, ?string $email, ?int $objectif, ?string $motivation, ?string $action, ?int $engagement, ?int $notifications, ?int $score = 0, ?int $xp = 0, ?int $level = 1) {
        $this->id_challenge = $id_challenge;
        $this->nom = $nom;
        $this->email = $email;
        $this->objectif = $objectif;
        $this->motivation = $motivation;
        $this->action = $action;
        $this->engagement = $engagement;
        $this->notifications = $notifications;
        $this->score = $score;
        $this->xp = $xp;
        $this->level = $level;
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
    public function getScore(): ?int { return $this->score; }
    public function getXp(): ?int { return $this->xp; }
    public function getLevel(): ?int { return $this->level; }

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
    public function setScore(?int $score): void { $this->score = $score; }
    public function setXp(?int $xp): void { $this->xp = $xp; }
    public function setLevel(?int $level): void { $this->level = $level; }
}
?>
