<?php

namespace App\Entity;

use App\Enum\RoleUtilisateur;
use App\Enum\StatutInscription;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: RoleUtilisateur::class)]
    private ?RoleUtilisateur $role = null;

    #[ORM\Column(length: 45)]
    private ?string $nom = null;

    #[ORM\Column(length: 45)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_de_naissance = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 60)]
    private ?string $mdp_hash = null;

    #[ORM\Column(enumType: StatutInscription::class, options: ['default' => 'En attente'])]
    private ?StatutInscription $statut_inscription = StatutInscription::EN_ATTENTE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): ?RoleUtilisateur
    {
        return $this->role;
    }

    public function setRole(RoleUtilisateur $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDateDeNaissance(): ?\DateTime
    {
        return $this->date_de_naissance;
    }

    public function setDateDeNaissance(?\DateTime $date_de_naissance): static
    {
        $this->date_de_naissance = $date_de_naissance;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMdpHash(): ?string
    {
        return $this->mdp_hash;
    }

    public function setMdpHash(string $mdp_hash): static
    {
        $this->mdp_hash = $mdp_hash;

        return $this;
    }

    public function getStatutInscription(): ?StatutInscription
    {
        return $this->statut_inscription;
    }

    public function setStatutInscription(StatutInscription $statut_inscription): static
    {
        $this->statut_inscription = $statut_inscription;

        return $this;
    }
}
