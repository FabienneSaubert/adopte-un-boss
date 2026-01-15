<?php

namespace App\Entity;

use App\Enum\NiveauEtude;
use App\Repository\CandidatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $profil_visible = null;

    #[ORM\Column]
    private ?bool $infos_visibles = null;

    #[ORM\Column(length: 30)]
    private ?string $uuid = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(enumType: NiveauEtude::class)]
    private ?NiveauEtude $niveau_etude = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isProfilVisible(): ?bool
    {
        return $this->profil_visible;
    }

    public function setProfilVisible(bool $profil_visible): static
    {
        $this->profil_visible = $profil_visible;

        return $this;
    }

    public function isInfosVisibles(): ?bool
    {
        return $this->infos_visibles;
    }

    public function setInfosVisibles(bool $infos_visibles): static
    {
        $this->infos_visibles = $infos_visibles;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;

        return $this;
    }

    public function getNiveauEtude(): ?NiveauEtude
    {
        return $this->niveau_etude;
    }

    public function setNiveauEtude(NiveauEtude $niveau_etude): static
    {
        $this->niveau_etude = $niveau_etude;

        return $this;
    }
}
