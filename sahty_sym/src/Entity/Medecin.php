<?php

namespace App\Entity;

use App\Entity\Utilisateur;
use App\Repository\MedecinRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
#[ORM\Table(name: "medecin")]
class Medecin extends Utilisateur
{
    #[ORM\Column(length: 100)]
    private ?string $specialite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentPdf = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $disponibilite = null;

    #[ORM\Column(nullable: true)]
    private ?int $anneeExperience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseCabinet = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephoneCabinet = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $nomEtablissement = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numeroUrgence = null;

    #[ORM\Column(length: 1, nullable: true)]
    private ?string $sexe = null;

    // ------------------------
    // Getters et Setters
    // ------------------------

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getDocumentPdf(): ?string
    {
        return $this->documentPdf;
    }

    public function setDocumentPdf(?string $documentPdf): static
    {
        $this->documentPdf = $documentPdf;
        return $this;
    }

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?string $disponibilite): static
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getAnneeExperience(): ?int
    {
        return $this->anneeExperience;
    }

    public function setAnneeExperience(?int $anneeExperience): static
    {
        $this->anneeExperience = $anneeExperience;
        return $this;
    }

    public function getAdresseCabinet(): ?string
    {
        return $this->adresseCabinet;
    }

    public function setAdresseCabinet(?string $adresseCabinet): static
    {
        $this->adresseCabinet = $adresseCabinet;
        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getTelephoneCabinet(): ?string
    {
        return $this->telephoneCabinet;
    }

    public function setTelephoneCabinet(?string $telephoneCabinet): static
    {
        $this->telephoneCabinet = $telephoneCabinet;
        return $this;
    }

    public function getNomEtablissement(): ?string
    {
        return $this->nomEtablissement;
    }

    public function setNomEtablissement(?string $nomEtablissement): static
    {
        $this->nomEtablissement = $nomEtablissement;
        return $this;
    }

    public function getNumeroUrgence(): ?string
    {
        return $this->numeroUrgence;
    }

    public function setNumeroUrgence(?string $numeroUrgence): static
    {
        $this->numeroUrgence = $numeroUrgence;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;
        return $this;
    }
}
