<?php

class User
{
    private ?int $id_utilisateur = null;
    private string $nom = '';
    private string $prenom = '';
    private string $email = '';
    private string $mdp = '';
    private string $role = 'utilisateur';
    private string $date_creation = '';
    private string $date_mise_a_jour = '';

    public function __construct(?int $id_utilisateur = null,string $nom = '',string $prenom = '',string $email = '',string $mdp = '',string $role = 'utilisateur',string $date_creation = '',string $date_mise_a_jour = '') {
        $this->id_utilisateur = $id_utilisateur;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->mdp = $mdp;
        $this->role = $role;
        $this->date_creation = $date_creation;
        $this->date_mise_a_jour = $date_mise_a_jour;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMdp(): string
    {
        return $this->mdp;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getDateCreation(): string
    {
        return $this->date_creation;
    }

    public function getDateMiseAJour(): string
    {
        return $this->date_mise_a_jour;
    }

    public function setIdUtilisateur(?int $id_utilisateur): void
    {
        $this->id_utilisateur = $id_utilisateur;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setMdp(string $mdp): void
    {
        $this->mdp = $mdp;
    }

    public function setRole(string $role): void
    {
        $rolesValides = ['utilisateur', 'nutritionniste','ecologiste','admin'];
        if (!in_array($role, $rolesValides, true)) {
            throw new InvalidArgumentException('Rôle invalide');
        }
        $this->role = $role;
    }

    public function setDateCreation(string $date_creation): void
    {
        $this->date_creation = $date_creation;
    }

    public function setDateMiseAJour(string $date_mise_a_jour): void
    {
        $this->date_mise_a_jour = $date_mise_a_jour;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isNutritionniste(): bool
{
    return $this->role === 'nutritionniste';
}

public function isEcologiste(): bool
{
    return $this->role === 'ecologiste';
}

    public function isUtilisateur(): bool
    {
        return $this->role === 'utilisateur';
    }
}
