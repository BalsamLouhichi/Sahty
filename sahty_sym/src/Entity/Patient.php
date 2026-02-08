<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patient')]
class Patient extends Utilisateur
{
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactUrgence = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sexe = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(self::ROLE_SIMPLE_PATIENT);
    }

    public function getGroupeSanguin(): ?string
    {
        return $this->groupeSanguin;
    }

    public function setGroupeSanguin(?string $groupeSanguin): self
    {
        $this->groupeSanguin = $groupeSanguin;
        return $this;
    }

    public function getContactUrgence(): ?string
    {
        return $this->contactUrgence;
    }

    public function setContactUrgence(?string $contactUrgence): self
    {
        $this->contactUrgence = $contactUrgence;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    /**
     * Calcul de l'âge du patient
     */
    public function getAge(): ?int
    {
        if (!$this->getDateNaissance()) {
            return null;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->getDateNaissance());
        return $interval->y;
    }
}