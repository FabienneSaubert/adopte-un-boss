<?php

namespace App\Entity;

use App\Enum\CategorieCompetence;
use App\Repository\CompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetenceRepository::class)]
class Competence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: CategorieCompetence::class)]
    private ?CategorieCompetence $categorie = null;

    #[ORM\Column(length: 45)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Candidat>
     */
    #[ORM\ManyToMany(targetEntity: Candidat::class, mappedBy: 'competences')]
    private Collection $candidats;

    /**
     * @var Collection<int, SelectionCompetence>
     */
    #[ORM\OneToMany(targetEntity: SelectionCompetence::class, mappedBy: 'competence')]
    private Collection $selectionCompetences;

    public function __construct()
    {
        $this->candidats = new ArrayCollection();
        $this->selectionCompetences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorie(): ?CategorieCompetence
    {
        return $this->categorie;
    }

    public function setCategorie(CategorieCompetence $categorie): static
    {
        $this->categorie = $categorie;

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

    /**
     * @return Collection<int, Candidat>
     */
    public function getCandidats(): Collection
    {
        return $this->candidats;
    }

    public function addCandidat(Candidat $candidat): static
    {
        if (!$this->candidats->contains($candidat)) {
            $this->candidats->add($candidat);
            $candidat->addCompetence($this);
        }

        return $this;
    }

    public function removeCandidat(Candidat $candidat): static
    {
        if ($this->candidats->removeElement($candidat)) {
            $candidat->removeCompetence($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SelectionCompetence>
     */
    public function getSelectionCompetences(): Collection
    {
        return $this->selectionCompetences;
    }

    public function addSelectionCompetence(SelectionCompetence $selectionCompetence): static
    {
        if (!$this->selectionCompetences->contains($selectionCompetence)) {
            $this->selectionCompetences->add($selectionCompetence);
            $selectionCompetence->setCompetence($this);
        }

        return $this;
    }

    public function removeSelectionCompetence(SelectionCompetence $selectionCompetence): static
    {
        if ($this->selectionCompetences->removeElement($selectionCompetence)) {
            // set the owning side to null (unless already changed)
            if ($selectionCompetence->getCompetence() === $this) {
                $selectionCompetence->setCompetence(null);
            }
        }

        return $this;
    }
}
