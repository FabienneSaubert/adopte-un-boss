<?php
 
namespace App\Entity;
 
use App\Enum\TypeCompetence;
use App\Enum\DomaineActivite;
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
 
    #[ORM\Column(enumType: TypeCompetence::class)]
    private ?TypeCompetence $type = null;

    #[ORM\Column(enumType: DomaineActivite::class, nullable: true)]
    private ?DomaineActivite $domaine = null;
 
    #[ORM\Column(length: 255)]
    private ?string $nom = null;
 
    /**
     * @var Collection<int, Candidat>
     */
    #[ORM\ManyToMany(targetEntity: Candidat::class, mappedBy: 'domaines')]
    private Collection $candidats;
 
    /**
     * @var Collection<int, SelectionCompetence>
     */
    #[ORM\OneToMany(targetEntity: SelectionCompetence::class, mappedBy: 'domaine')]
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
 
    public function getType(): ?TypeCompetence
    {
        return $this->type;
    }
 
    public function setType(?TypeCompetence $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDomaine(): ?DomaineActivite
    {
        return $this->domaine;
    }
 
    public function setDomaine(?DomaineActivite $domaine): static
    {
        $this->domaine = $domaine;
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
            $candidat->addDomaine($this);
        }
        return $this;
    }
 
    public function removeCandidat(Candidat $candidat): static
    {
        if ($this->candidats->removeElement($candidat)) {
            $candidat->removeDomaine($this);
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
            $selectionCompetence->setDomaine($this);
        }
        return $this;
    }
 
    public function removeSelectionCompetence(SelectionCompetence $selectionCompetence): static
    {
        if ($this->selectionCompetences->removeElement($selectionCompetence)) {
            if ($selectionCompetence->getDomaine() === $this) {
                $selectionCompetence->setDomaine(null);
            }
        }
        return $this;
    }
}