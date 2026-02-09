<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patient')]
class Patient extends Utilisateur
{
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $groupeSanguin = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $contactUrgence = null;

    /**
     * @var Collection<int, FicheMedicale>
     */
    #[ORM\OneToMany(targetEntity: FicheMedicale::class, mappedBy: 'patient')]
    private Collection $medecin;

    public function __construct()
    {
        $this->medecin = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, FicheMedicale>
     */
    public function getMedecin(): Collection
    {
        return $this->medecin;
    }

    public function addMedecin(FicheMedicale $medecin): static
    {
        if (!$this->medecin->contains($medecin)) {
            $this->medecin->add($medecin);
            $medecin->setPatient($this);
        }

        return $this;
    }

    public function removeMedecin(FicheMedicale $medecin): static
    {
        if ($this->medecin->removeElement($medecin)) {
            // set the owning side to null (unless already changed)
            if ($medecin->getPatient() === $this) {
                $medecin->setPatient(null);
            }
        }

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