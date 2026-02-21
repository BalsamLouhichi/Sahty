<?php

namespace App\Entity;

use App\Repository\ResponsableLaboratoireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableLaboratoireRepository::class)]
#[ORM\Table(name: 'responsable_laboratoire')]
class ResponsableLaboratoire extends Utilisateur
{
    // Keep this from YOUR branch if needed for legacy code
    // But note: Balsam's approach makes this redundant
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $laboratoireId = null;

    #[ORM\OneToOne(targetEntity: Laboratoire::class, inversedBy: 'responsable')]
    #[ORM\JoinColumn(name: 'laboratoire_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Laboratoire $laboratoire = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(self::ROLE_SIMPLE_RESPONSABLE_LABO);
    }

    // From Balsam - MAIN getter for the laboratoire object
    public function getLaboratoire(): ?Laboratoire
    {
        return $this->laboratoire;
    }

    // From Balsam - MAIN setter for the laboratoire object
    public function setLaboratoire(?Laboratoire $laboratoire): self
    {
        // Désassocier l'ancien laboratoire
        if ($this->laboratoire !== null && $this->laboratoire->getResponsable() === $this) {
            $this->laboratoire->setResponsable(null);
        }

        // Associer le nouveau laboratoire
        $this->laboratoire = $laboratoire;

        if ($laboratoire !== null && $laboratoire->getResponsable() !== $this) {
            $laboratoire->setResponsable($this);
        }

        // Update the ID field for compatibility
        $this->laboratoireId = $laboratoire ? $laboratoire->getId() : null;

        return $this;
    }

    // COMBINED: Keep YOUR getLaboratoireId but make it use the object (like Balsam's version)
    public function getLaboratoireId(): ?int
    {
        // If we have the laboratoire object, use its ID
        if ($this->laboratoire) {
            return $this->laboratoire->getId();
        }
        // Otherwise fall back to the stored ID (for legacy data)
        return $this->laboratoireId;
    }

    // COMBINED: Keep YOUR setLaboratoireId but make it update both
    public function setLaboratoireId(?int $laboratoireId): self
    {
        $this->laboratoireId = $laboratoireId;
        
        // Note: This doesn't set the actual laboratoire object
        // That would need to be done separately with setLaboratoire()
        // This method is kept for backward compatibility
        
        return $this;
    }

    // From Balsam - Helper methods
    public function getNomLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getNom() : null;
    }

    public function getAdresseLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getAdresseComplete() : null;
    }

    public function getTelephoneLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getTelephone() : null;
    }

    public function hasLaboratoire(): bool
    {
        return $this->laboratoire !== null;
    }
}