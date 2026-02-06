<?php

namespace App\Entity;

use App\Entity\Utilisateur;

use App\Repository\PatientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: "patient")]
class Patient extends Utilisateur
{
    #[ORM\Column(length: 1, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactUrgence = null;

    // ------------------------
    // Getters et Setters
    // ------------------------

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): static
    {
        $this->groupeSanguin = $groupeSanguin;
        return $this;
    }

    public function getContactUrgence(): ?string
    {
        return $this->contactUrgence;
    }

    public function setContactUrgence(?string $contactUrgence): static
    {
        $this->contactUrgence = $contactUrgence;
        return $this;
    }
}

