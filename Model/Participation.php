<?php
if (!class_exists('Participation')) {
    class Participation {
        private ?int $id_participation;
        private ?int $id_event;
        private ?string $nom_complet;
        private ?string $email;
        private ?string $telephone;
        private ?string $centre_interet;
        private ?string $statut;

        public function __construct(?int $id_participation, ?int $id_event, ?string $nom_complet, ?string $email, 
                                    ?string $telephone, ?string $centre_interet, ?string $statut) {
            $this->id_participation = $id_participation;
            $this->id_event = $id_event;
            $this->nom_complet = $nom_complet;
            $this->email = $email;
            $this->telephone = $telephone;
            $this->centre_interet = $centre_interet;
            $this->statut = $statut;
        }

        // Getters
        public function getIdParticipation(): ?int { return $this->id_participation; }
        public function getIdEvent(): ?int { return $this->id_event; }
        public function getNomComplet(): ?string { return $this->nom_complet; }
        public function getEmail(): ?string { return $this->email; }
        public function getTelephone(): ?string { return $this->telephone; }
        public function getCentreInteret(): ?string { return $this->centre_interet; }
        public function getStatut(): ?string { return $this->statut; }

        // Setters
        public function setIdParticipation(?int $id): void { $this->id_participation = $id; }
        public function setIdEvent(?int $id): void { $this->id_event = $id; }
        public function setNomComplet(?string $nom): void { $this->nom_complet = $nom; }
        public function setEmail(?string $email): void { $this->email = $email; }
        public function setTelephone(?string $tel): void { $this->telephone = $tel; }
        public function setCentreInteret(?string $ci): void { $this->centre_interet = $ci; }
        public function setStatut(?string $statut): void { $this->statut = $statut; }
    }
}
?>