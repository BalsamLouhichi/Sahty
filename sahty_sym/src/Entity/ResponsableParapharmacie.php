<?php

namespace App\Entity;

use App\Entity\Utilisateur;

use App\Repository\ResponsableParapharmacieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableParapharmacieRepository::class)]
#[ORM\Table(name: "responsable_parapharmacie")]
class ResponsableParapharmacie extends Utilisateur
{
    #[ORM\Column(type:"bigint")]
    private ?int $parapharmacieId = null;

    // ------------------------
    // Getters et Setters
    // ------------------------

    public function getParapharmacieId(): ?int
    {
        return $this->parapharmacieId;
    }

    public function setParapharmacieId(int $parapharmacieId): static
    {
        $this->parapharmacieId = $parapharmacieId;
        return $this;
    }
}
