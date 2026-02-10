<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\ManyToMany(targetEntity: Parapharmacie::class, mappedBy: 'produits')]
    private Collection $parapharmacies;

    public function __construct()
    {
        $this->parapharmacies = new ArrayCollection();
    }

    // ----- Getters et Setters -----

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    /**
     * @return Collection|Parapharmacie[]
     */
    public function getParapharmacies(): Collection
    {
        return $this->parapharmacies;
    }

    public function addParapharmacie(Parapharmacie $parapharmacie): self
    {
        if (!$this->parapharmacies->contains($parapharmacie)) {
            $this->parapharmacies[] = $parapharmacie;
            $parapharmacie->addProduit($this);
        }
        return $this;
    }

    public function removeParapharmacie(Parapharmacie $parapharmacie): self
    {
        if ($this->parapharmacies->removeElement($parapharmacie)) {
            $parapharmacie->removeProduit($this);
        }
        return $this;
    }
}