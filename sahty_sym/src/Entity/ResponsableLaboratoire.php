<?php

namespace App\Entity;

use App\Entity\Utilisateur;

use App\Repository\ResponsableLaboratoireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableLaboratoireRepository::class)]
#[ORM\Table(name: "responsable_laboratoire")]
class ResponsableLaboratoire extends Utilisateur
{
    #[ORM\Column(type:"bigint")]
    private ?int $laboratoireId = null;

    // ------------------------
    // Getters et Setters
    // ------------------------

    public function getLaboratoireId(): ?int
    {
        return $this->laboratoireId;
    }

    public function setLaboratoireId(int $laboratoireId): static
    {
        $this->laboratoireId = $laboratoireId;
        return $this;
    }
}
