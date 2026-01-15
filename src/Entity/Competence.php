<?php

namespace App\Entity;

use App\Enum\CategorieCompetence;
use App\Repository\CompetenceRepository;
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
}
