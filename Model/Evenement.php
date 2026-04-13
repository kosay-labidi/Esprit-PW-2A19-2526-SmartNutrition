<?php
if (!class_exists('Evenement')) {
    class Evenement {
        private ?int $id_event;
        private ?string $titre;
        private ?string $description;
        private ?DateTime $date;
        private ?string $heure;
        private ?string $type;

        public function __construct(?int $id_event, ?string $titre, ?string $description, ?DateTime $date, ?string $heure, ?string $type) {
            $this->id_event = $id_event;
            $this->titre = $titre;
            $this->description = $description;
            $this->date = $date;
            $this->heure = $heure;
            $this->type = $type;
        }

        // Getters & Setters
        public function getIdEvent(): ?int { return $this->id_event; }
        public function setIdEvent(?int $id_event): void { $this->id_event = $id_event; }
        public function getTitre(): ?string { return $this->titre; }
        public function setTitre(?string $titre): void { $this->titre = $titre; }
        public function getDescription(): ?string { return $this->description; }
        public function setDescription(?string $description): void { $this->description = $description; }
        public function getDate(): ?DateTime { return $this->date; }
        public function setDate(?DateTime $date): void { $this->date = $date; }
        public function getHeure(): ?string { return $this->heure; }
        public function setHeure(?string $heure): void { $this->heure = $heure; }
        public function getType(): ?string { return $this->type; }
        public function setType(?string $type): void { $this->type = $type; }
    }
}
?>