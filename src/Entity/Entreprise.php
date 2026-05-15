<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private ?string $nom = null;

    #[ORM\Column(length: 14, unique: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 100)]
    private ?string $adresse = null;

    /**
     * @var Collection<int, Recruteur>
     */
    #[ORM\OneToMany(targetEntity: Recruteur::class, mappedBy: 'entreprise')]
    private Collection $recruteurs;

    public function __construct()
    {
        $this->recruteurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * @return Collection<int, Recruteur>
     */
    public function getRecruteurs(): Collection
    {
        return $this->recruteurs;
    }

    public function addRecruteur(Recruteur $recruteur): static
    {
        if (!$this->recruteurs->contains($recruteur)) {
            $this->recruteurs->add($recruteur);
            $recruteur->setEntreprise($this);
        }

        return $this;
    }

    public function removeRecruteur(Recruteur $recruteur): static
    {
        if ($this->recruteurs->removeElement($recruteur)) {
            // set the owning side to null (unless already changed)
            if ($recruteur->getEntreprise() === $this) {
                $recruteur->setEntreprise(null);
            }
        }

        return $this;
    }
}
