<?php

namespace App\Entity;

use App\Repository\RecruteurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecruteurRepository::class)]
class Recruteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $poste = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email_pro = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $telephone_pro = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoste(): ?string
    {
        return $this->poste;
    }

    public function setPoste(string $poste): static
    {
        $this->poste = $poste;

        return $this;
    }

    public function getEmailPro(): ?string
    {
        return $this->email_pro;
    }

    public function setEmailPro(?string $email_pro): static
    {
        $this->email_pro = $email_pro;

        return $this;
    }

    public function getTelephonePro(): ?string
    {
        return $this->telephone_pro;
    }

    public function setTelephonePro(?string $telephone_pro): static
    {
        $this->telephone_pro = $telephone_pro;

        return $this;
    }
}
