<?php

namespace App\Entity;

use App\Repository\ResponsableParapharmacieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableParapharmacieRepository::class)]
#[ORM\Table(name: 'responsable_parapharmacie')]
class ResponsableParapharmacie extends Utilisateur
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $parapharmacieId = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(self::ROLE_SIMPLE_RESPONSABLE_PARA);
    }

    public function getParapharmacieId(): ?int
    {
        return $this->parapharmacieId;
    }

    public function setParapharmacieId(?int $parapharmacieId): self
    {
        $this->parapharmacieId = $parapharmacieId;
        return $this;
    }
}