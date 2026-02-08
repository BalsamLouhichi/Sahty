<?php
// src/Entity/ResponsableLaboratoire.php

namespace App\Entity;

use App\Repository\ResponsableLaboratoireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResponsableLaboratoireRepository::class)]
#[ORM\Table(name: 'responsable_laboratoire')]
class ResponsableLaboratoire extends Utilisateur
{
    #[ORM\OneToOne(targetEntity: Laboratoire::class, inversedBy: 'responsable')]
    #[ORM\JoinColumn(name: 'laboratoire_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Laboratoire $laboratoire = null;

    public function __construct()
    {
        parent::__construct();
        $this->setRole(self::ROLE_SIMPLE_RESPONSABLE_LABO);
    }

    public function getLaboratoire(): ?Laboratoire
    {
        return $this->laboratoire;
    }

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
        
        return $this;
    }

    /**
     * Méthode pour garder la compatibilité avec l'ancien code
     * qui utilisait laboratoireId
     */
    public function getLaboratoireId(): ?int
    {
        return $this->laboratoire ? $this->laboratoire->getId() : null;
    }

    public function setLaboratoireId(?int $laboratoireId): self
    {
        // Cette méthode est maintenant dépréciée
        // Il est préférable d'utiliser setLaboratoire() directement
        // Elle est gardée pour la compatibilité avec SignupController
        return $this;
    }

    /**
     * Méthode pour obtenir le nom du laboratoire
     */
    public function getNomLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getNom() : null;
    }

    /**
     * Méthode pour obtenir l'adresse du laboratoire
     */
    public function getAdresseLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getAdresseComplete() : null;
    }

    /**
     * Méthode pour obtenir le téléphone du laboratoire
     */
    public function getTelephoneLaboratoire(): ?string
    {
        return $this->laboratoire ? $this->laboratoire->getTelephone() : null;
    }

    /**
     * Méthode pour vérifier si le responsable a un laboratoire assigné
     */
    public function hasLaboratoire(): bool
    {
        return $this->laboratoire !== null;
    }
}