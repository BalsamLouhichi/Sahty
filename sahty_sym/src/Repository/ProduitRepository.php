<?php

namespace App\Repository;


use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }
    // Dans src/Repository/ProduitRepository.php

/**
 * Rechercher des produits par terme
 */
public function search(string $term): array
{
    return $this->createQueryBuilder('p')
        ->where('p.nom LIKE :term OR p.description LIKE :term')
        ->setParameter('term', '%' . $term . '%')
        ->orderBy('p.nom', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Trouver les produits par catégorie
 */
public function findByCategorie(string $categorie): array
{
    // Si vous avez une propriété 'categorie' dans l'entité Produit
    return $this->createQueryBuilder('p')
        ->where('p.categorie = :categorie')
        ->setParameter('categorie', $categorie)
        ->orderBy('p.nom', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Trouver les produits en promotion
 */
public function findPromotions(): array
{
    // Si vous avez une propriété 'promotion' ou 'reduction' dans l'entité Produit
    return $this->createQueryBuilder('p')
        ->where('p.promotion IS NOT NULL')
        ->orWhere('p.reduction > 0')
        ->orderBy('p.nom', 'ASC')
        ->getQuery()
        ->getResult();
}

//    /**
//     * @return Produit[] Returns an array of Produit objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Produit
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
