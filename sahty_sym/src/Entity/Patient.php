<?php

namespace App\Entity;

use App\Entity\Utilisateur;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @var Collection<int, FicheMedicale>
     */
    #[ORM\OneToMany(targetEntity: FicheMedicale::class, mappedBy: 'patient')]
    private Collection $medecin;

    public function __construct()
    {
        $this->medecin = new ArrayCollection();
    }

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
}

