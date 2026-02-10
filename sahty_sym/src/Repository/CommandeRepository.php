<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Trouver les commandes par parapharmacie
     */
    public function findByParapharmacie($parapharmacieId)
    {
        return $this->createQueryBuilder('c')
            ->join('c.parapharmacie', 'p')
            ->where('p.id = :parapharmacieId')
            ->setParameter('parapharmacieId', $parapharmacieId)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les commandes par produit
     */
    public function findByProduit($produitId)
    {
        return $this->createQueryBuilder('c')
            ->join('c.produit', 'p')
            ->where('p.id = :produitId')
            ->setParameter('produitId', $produitId)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les commandes par statut
     */
    public function findByStatut($statut)
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des commandes
     */
    public function getStats()
    {
        $qb = $this->createQueryBuilder('c')
            ->select([
                'COUNT(c.id) as total_commandes',
                'SUM(c.quantite) as total_quantite',
                'SUM(c.prixTotal) as total_chiffre'
            ])
            ->where('c.statut != :annulee')
            ->setParameter('annulee', 'annulee');

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Commandes récentes (7 derniers jours)
     */
    public function findRecentOrders($limit = 10)
    {
        $date = new \DateTime('-7 days');

        return $this->createQueryBuilder('c')
            ->where('c.dateCreation >= :date')
            ->setParameter('date', $date)
            ->orderBy('c.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Commandes par email
     */
    public function findByEmail($email)
    {
        return $this->createQueryBuilder('c')
            ->where('c.email = :email')
            ->setParameter('email', $email)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}