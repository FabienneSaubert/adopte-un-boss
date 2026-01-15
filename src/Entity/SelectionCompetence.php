<?php

namespace App\Entity;

use App\Repository\SelectionCompetenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SelectionCompetenceRepository::class)]
class SelectionCompetence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $coeff_competence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoeffCompetence(): ?int
    {
        return $this->coeff_competence;
    }

    public function setCoeffCompetence(int $coeff_competence): static
    {
        $this->coeff_competence = $coeff_competence;

        return $this;
    }
}
