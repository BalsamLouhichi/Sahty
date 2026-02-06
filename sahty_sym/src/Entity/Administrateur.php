<?php

namespace App\Entity;

use App\Entity\Utilisateur;

use App\Repository\AdministrateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
#[ORM\Table(name: "administrateur")]
class Administrateur extends Utilisateur
{
    
}
