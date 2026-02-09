<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }


     public function ajouterEvenement(Evenement $evenement): void
    {
        $this->getEntityManager()->persist($evenement);
        $this->getEntityManager()->flush();
    }

    
    public function modifierEvenement(Evenement $evenement): void
    {
        $evenement->setModifieLe(new \DateTime());
        $this->getEntityManager()->flush();
    }

    
    public function supprimerEvenement(Evenement $evenement): void
    {
        $this->getEntityManager()->remove($evenement);
        $this->getEntityManager()->flush();
    }

    
    public function getEvenements(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getEvenementById(int $id): ?Evenement
    {
        return $this->find($id);
    }

    
    public function getEvenementByStatut(string $statut): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getEvenementByType(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.type = :type')
            ->setParameter('type', $type)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function rechercherEvenement(string $terme): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.titre LIKE :terme OR e.description LIKE :terme')
            ->setParameter('terme', '%' . $terme . '%')
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getParticipants(Evenement $evenement): array
    {
        return $this->createQueryBuilder('e')
            ->select('u')
            ->leftJoin('e.inscriptions', 'i')
            ->leftJoin('i.utilisateur', 'u')
            ->andWhere('e.id = :id')
            ->setParameter('id', $evenement->getId())
            ->getQuery()
            ->getResult();
    }
    
    public function getEvenementsTries(string $tri = 'dateDebut', string $ordre = 'ASC', ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('e');
        
        
        if ($statut !== null) {
            $qb->andWhere('e.statut = :statut')
               ->setParameter('statut', $statut);
        }
        
    
        switch ($tri) {
            case 'dateDebut':
                $qb->orderBy('e.dateDebut', $ordre);
                break;
                
            case 'dateFin':
                $qb->orderBy('e.dateFin', $ordre);
                break;
                
            case 'participants':
              
                $qb->leftJoin('e.inscriptions', 'i')
                   ->addSelect('COUNT(i.id) as HIDDEN nbParticipants')
                   ->groupBy('e.id')
                   ->orderBy('nbParticipants', $ordre);
                break;
                
            case 'tauxRemplissage':
                
                $qb->leftJoin('e.inscriptions', 'i')
                   ->addSelect('COUNT(i.id) as HIDDEN nbParticipants')
                   ->addSelect('CASE WHEN e.placesMax > 0 THEN (COUNT(i.id) * 100.0 / e.placesMax) ELSE 0 END as HIDDEN tauxRemplissage')
                   ->groupBy('e.id')
                   ->orderBy('tauxRemplissage', $ordre);
                break;
                
            default:
                $qb->orderBy('e.dateDebut', 'ASC');
                break;
        }
        
        return $qb->getQuery()->getResult();
    }

    
    public function getEvenementsParStatut(string $statut, string $tri = 'dateDebut', string $ordre = 'ASC'): array
    {
        return $this->getEvenementsTries($tri, $ordre, $statut);
    }

   
    public function getNombreParticipants(Evenement $evenement): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(i.id)')
            ->leftJoin('e.inscriptions', 'i')
            ->andWhere('e.id = :id')
            ->setParameter('id', $evenement->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    
    public function getTauxRemplissage(Evenement $evenement): float
    {
        if ($evenement->getPlacesMax() === null || $evenement->getPlacesMax() <= 0) {
            return 0;
        }
        
        $nbParticipants = $this->getNombreParticipants($evenement);
        return ($nbParticipants / $evenement->getPlacesMax()) * 100;
    }

   
    public function getEvenementsAvecStats(?string $statut = null, string $tri = 'dateDebut', string $ordre = 'ASC'): array
    {
        $evenements = $this->getEvenementsTries($tri, $ordre, $statut);
        
        
        foreach ($evenements as $evenement) {
            $evenement->nombreParticipants = $this->getNombreParticipants($evenement);
            $evenement->tauxRemplissage = $this->getTauxRemplissage($evenement);
        }
        
        return $evenements;
    }
  
    public function getStatutsDisponibles(): array
    {
        return $this->createQueryBuilder('e')
            ->select('DISTINCT e.statut')
            ->orderBy('e.statut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(?string $type, ?string $search): array
{
    $qb = $this->createQueryBuilder('e');

    if ($type) {
        $qb->andWhere('e.type = :type')
           ->setParameter('type', $type);
    }

    if ($search) {
        $qb->andWhere('e.titre LIKE :search OR e.description LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return $qb->orderBy('e.dateDebut', 'ASC')
              ->getQuery()
              ->getResult();
}

}