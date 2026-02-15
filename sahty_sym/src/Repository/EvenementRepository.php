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

    if ($statut) {
        $qb->andWhere('e.statut = :statut')
           ->setParameter('statut', $statut);
    }

    $orderField = $tri === 'dateFin' ? 'e.dateFin' : 'e.dateDebut';
    $qb->orderBy($orderField, $ordre);

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

   public function findByFilters(?string $type = null, ?string $statut = null, ?string $recherche = null): array
{
    $qb = $this->createQueryBuilder('e');

    if ($type) {
        $qb->andWhere('e.type = :type')
           ->setParameter('type', $type);
    }

    if ($statut) {
        $qb->andWhere('e.statut = :statut')
           ->setParameter('statut', $statut);
    }

    if ($recherche) {
        $qb->andWhere('e.titre LIKE :recherche OR e.description LIKE :recherche')
           ->setParameter('recherche', '%' . $recherche . '%');
    }

    return $qb->orderBy('e.dateDebut', 'ASC')
              ->getQuery()
              ->getResult();
}

public function findVisibleEventsForClient($user = null): array {
    $qb = $this->createQueryBuilder('e')
        ->orderBy('e.dateDebut', 'ASC');

    // 1. CRITICAL: Strictly filter ONLY approved statuses
    // This removes "en_attente_approbation" completely from this list
    $qb->andWhere('e.statut IN (:statutsApprouves)')
       ->setParameter('statutsApprouves', ['planifie', 'confirme', 'en_cours']);

    // 2. Filter by Groups (Target Audience)
    if ($user) {
        $userGroups = $user->getGroupes();
        
        $qb->leftJoin('e.groupeCibles', 'g')
           ->andWhere(
               $qb->expr()->orX(
                   'e.groupeCibles IS EMPTY',        // Public events
                   'g IN (:userGroups)',             // Events for my group
                   'e.createur = :userId'            // AND my own APPROVED events
               )
           )
           ->setParameter('userGroups', $userGroups ?: [])
           ->setParameter('userId', $user->getId());
    } else {
        // If not logged in, only show public events
        $qb->andWhere('e.groupeCibles IS EMPTY');
    }

    return $qb->getQuery()->getResult();
}


public function findAllPendingEvents(): array
{
    return $this->createQueryBuilder('e')
        ->where('e.statut = :statut')
        ->setParameter('statut', 'en_attente_approbation')
        ->orderBy('e.creeLe', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findByStatutDemande(string $statutDemande, ?string $type = null, ?string $recherche = null): array
{
    $qb = $this->createQueryBuilder('e')
        ->where('e.statutDemande = :statutDemande')
        ->setParameter('statutDemande', $statutDemande)
        ->orderBy('e.creeLe', 'DESC');

    if ($type) {
        $qb->andWhere('e.type = :type')
           ->setParameter('type', $type);
    }

    if ($recherche) {
        $qb->andWhere('e.titre LIKE :recherche OR e.description LIKE :recherche')
           ->setParameter('recherche', '%' . $recherche . '%');
    }

    return $qb->getQuery()->getResult();
}
}