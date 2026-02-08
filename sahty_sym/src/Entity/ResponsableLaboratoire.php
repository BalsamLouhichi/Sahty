<?php

namespace App\Entity;

use App\Repository\ResponsableLaboratoireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableLaboratoireRepository::class)]
#[ORM\Table(name: 'responsable_laboratoire')]
class ResponsableLaboratoire extends Utilisateur
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $laboratoireId = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(self::ROLE_SIMPLE_RESPONSABLE_LABO);
    }

    public function getLaboratoireId(): ?int
    {
        return $this->laboratoireId;
    }

    public function setLaboratoireId(?int $laboratoireId): self
    {
        $this->laboratoireId = $laboratoireId;
        return $this;
    }
}