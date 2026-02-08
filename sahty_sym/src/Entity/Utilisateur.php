<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: "App\Repository\UtilisateurRepository")]
#[ORM\Table(name: "Utilisateur")]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name:"role", type:"string")]
#[ORM\DiscriminatorMap([
    "admin" => Administrateur::class,
    "medecin" => Medecin::class,
    "patient" => Patient::class,
    "responsable_labo" =>  ResponsableLaboratoire::class,
    "responsable_para" => ResponsableParapharmacie::class,
])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"bigint")]
    private $id;

    #[ORM\Column(type:"string", length:180, unique:true)]
    private $email;

    #[ORM\Column(type:"json")]
    private $roles = [];

    #[ORM\Column(type:"string")]
    private $password;

    #[ORM\Column(type:"string", length:100)]
    private $nom;

    #[ORM\Column(type:"string", length:100)]
    private $prenom;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private $telephone;

    #[ORM\Column(type:"date", nullable:true)]
    private $dateNaissance;

    #[ORM\Column(type:"boolean", options:["default"=>true])]
    private $estActif = true;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private $photoProfil;

    #[ORM\Column(type:"datetime", options:["default"=>"CURRENT_TIMESTAMP"])]
    private $creeLe;

    // Méthodes UserInterface et PasswordAuthenticatedUserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials()
    {
       
    }

    // Getters et Setters ajoutés
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function getEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): self
    {
        $this->estActif = $estActif;
        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): self
    {
        $this->photoProfil = $photoProfil;
        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeInterface $creeLe): self
    {
        $this->creeLe = $creeLe;
        return $this;
    }
}