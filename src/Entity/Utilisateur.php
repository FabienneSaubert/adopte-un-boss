<?php

namespace App\Entity;

use App\Enum\RoleUtilisateur;
use App\Enum\StatutInscription;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// Interfaces à ajouter pour que l'entité puisse assurer l'authentification via lexik
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $date_de_naissance = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 60)]
    private ?string $mdp_hash = null;

    #[ORM\Column(enumType: StatutInscription::class, options: ['default' => 'En attente'])]
    private ?StatutInscription $statut_inscription = StatutInscription::EN_ATTENTE;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Candidat $candidat = null;

    #[ORM\OneToOne(mappedBy: 'utilisateur', cascade: ['persist', 'remove'])]
    private ?Recruteur $recruteur = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reset_token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reset_token_expires_at = null;

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

    public function getDateDeNaissance(): ?\DateTimeImmutable
    {
        return $this->date_de_naissance;
    }

    public function setDateDeNaissance(?\DateTimeImmutable $date_de_naissance): static
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

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(Candidat $candidat): static
    {
        // set the owning side of the relation if necessary
        if ($candidat->getUtilisateur() !== $this) {
            $candidat->setUtilisateur($this);
        }

        $this->candidat = $candidat;

        return $this;
    }

    public function getRecruteur(): ?Recruteur
    {
        return $this->recruteur;
    }

    public function setRecruteur(Recruteur $recruteur): static
    {
        // set the owning side of the relation if necessary
        if ($recruteur->getUtilisateur() !== $this) {
            $recruteur->setUtilisateur($this);
        }

        $this->recruteur = $recruteur;

        return $this;
    }


    /* ----- Méthodes obligatoires à implémenter pour l'authentification via lexik */

    // Getter permettant d'obtenir l'identifiant demandé par lexik
    public function getUserIdentifier(): string
    {
        // Dans notre cas, il s'agit de l'email
        return $this->email;
    }

    // Getter permettant d'obtenir le rôle utilisateur
    public function getRoles(): array
    {
        // Dans notre cas, les rôles définis en base de données ne sont pas compatibles avec des rôles Symfony,
        // on renvoi alors par défaut le rôle utilisateur de Symfony seul
        return ['ROLE_USER'];
    }

    // Getter permettant d'obtenir le hash du password demandé par lexik
    public function getPassword(): ?string
    {
        // Le getter renvoi donc la même valeur que notre getter déjà implémenté getMdpHash()
        return $this->mdp_hash;
    }

    // Méthode permettant d'effacer de la mémoire du serveur les identifiants après l'authentification
    public function eraseCredentials(): void
    {
        // Comme dans notre cas les identifiants ne sont pas stockés en mémoire, on peut laisser la méthode vide
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }
    public function setResetToken(?string $token): static
    {
        $this->reset_token = $token;
        return $this;
    }
    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->reset_token_expires_at;
    }
    public function setResetTokenExpiresAt(?\DateTimeImmutable $date): static
    {
        $this->reset_token_expires_at = $date;
        return $this;
    }
}
