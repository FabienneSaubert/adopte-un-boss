<?php

namespace App\Entity;
//modification en domaineactivite
use App\Enum\DomaineActivite;
use App\Enum\NiveauEtude;
use App\Enum\StatutOffre;
use App\Repository\OffreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\SelectionCompetence;
use App\Entity\Candidature;
use App\Entity\Departement;
use App\Entity\Recruteur;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: DomaineActivite::class)]
    private ?DomaineActivite $categorie_offre = null;

    #[ORM\Column(length: 150)]
    private ?string $intitule = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $salaire = null;

    #[ORM\Column(enumType: NiveauEtude::class)]
    private ?NiveauEtude $niveau_etudes = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $coeff_niveau_etudes = null;

    #[ORM\Column]
    private ?bool $teletravail_possible = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $coeff_departement = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_de_publication = null;

    #[ORM\Column(enumType: StatutOffre::class, options: ['default' => 'En attente'])]
    private ?StatutOffre $statut_offre = StatutOffre::EN_ATTENTE;

    #[ORM\Column]
    private ?int $nombre_de_vues = null;

    #[ORM\ManyToOne(inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recruteur $recruteur = null;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(targetEntity: Candidature::class, mappedBy: 'offre')]
    private Collection $candidatures;

    #[ORM\ManyToOne(inversedBy: 'offres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departement $departement = null;

    /**
     * @var Collection<int, SelectionCompetence>
     */
    #[ORM\OneToMany(targetEntity: SelectionCompetence::class, mappedBy: 'offre')]
    private Collection $selectionCompetences;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->selectionCompetences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategorieOffre(): ?DomaineActivite
    {
        return $this->categorie_offre;
    }

    public function setCategorieOffre(DomaineActivite $categorie_offre): static
    {
        $this->categorie_offre = $categorie_offre;

        return $this;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSalaire(): ?int
    {
        return $this->salaire;
    }

    public function setSalaire(?int $salaire): static
    {
        $this->salaire = $salaire;

        return $this;
    }

    public function getNiveauEtudes(): ?NiveauEtude
    {
        return $this->niveau_etudes;
    }

    public function setNiveauEtudes(NiveauEtude $niveau_etudes): static
    {
        $this->niveau_etudes = $niveau_etudes;

        return $this;
    }

    public function getCoeffNiveauEtudes(): ?int
    {
        return $this->coeff_niveau_etudes;
    }

    public function setCoeffNiveauEtudes(int $coeff_niveau_etudes): static
    {
        $this->coeff_niveau_etudes = $coeff_niveau_etudes;

        return $this;
    }

    public function isTeletravailPossible(): ?bool
    {
        return $this->teletravail_possible;
    }

    public function setTeletravailPossible(bool $teletravail_possible): static
    {
        $this->teletravail_possible = $teletravail_possible;

        return $this;
    }

    public function getCoeffDepartement(): ?int
    {
        return $this->coeff_departement;
    }

    public function setCoeffDepartement(int $coeff_departement): static
    {
        $this->coeff_departement = $coeff_departement;

        return $this;
    }

    public function getDateDePublication(): ?\DateTimeImmutable
    {
        return $this->date_de_publication;
    }

    public function setDateDePublication(\DateTimeImmutable $date_de_publication): static
    {
        $this->date_de_publication = $date_de_publication;

        return $this;
    }

    public function getStatutOffre(): ?StatutOffre
    {
        return $this->statut_offre;
    }

    public function setStatutOffre(StatutOffre $statut_offre): static
    {
        $this->statut_offre = $statut_offre;

        return $this;
    }

    public function getNombreDeVues(): ?int
    {
        return $this->nombre_de_vues;
    }

    public function setNombreDeVues(int $nombre_de_vues): static
    {
        $this->nombre_de_vues = $nombre_de_vues;

        return $this;
    }

    public function getRecruteur(): ?Recruteur
    {
        return $this->recruteur;
    }

    public function setRecruteur(?Recruteur $recruteur): static
    {
        $this->recruteur = $recruteur;

        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setOffre($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            // set the owning side to null (unless already changed)
            if ($candidature->getOffre() === $this) {
                $candidature->setOffre(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): static
    {
        $this->departement = $departement;

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
            $selectionCompetence->setOffre($this);
        }

        return $this;
    }

    public function removeSelectionCompetence(SelectionCompetence $selectionCompetence): static
    {
        if ($this->selectionCompetences->removeElement($selectionCompetence)) {
            // set the owning side to null (unless already changed)
            if ($selectionCompetence->getOffre() === $this) {
                $selectionCompetence->setOffre(null);
            }
        }

        return $this;
    }
}
