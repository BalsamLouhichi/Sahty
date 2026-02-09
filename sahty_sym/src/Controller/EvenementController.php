<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Entity\InscriptionEvenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// Routes accessible à /evenements et /admin/evenements
// Permet aux médecins et autres rôles d'accéder à la gestion des événements depuis le panel admin
#[Route(['/evenements', '/admin/evenements'], name: 'admin_')]
class EvenementController extends AbstractController
{
    private EvenementRepository $evenementRepository;

    public function __construct(EvenementRepository $evenementRepository)
    {
        $this->evenementRepository = $evenementRepository;
    }

    #[Route('/', name: 'evenement_list', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');
        $recherche = $request->query->get('recherche');
        $tri = $request->query->get('tri', 'dateDebut');
        $ordre = $request->query->get('ordre', 'ASC');

        // Récupérer les événements selon le rôle
        if (!$isAdmin) {
            if (!$user) {
                // Utilisateur non connecté : uniquement les événements sans groupes cibles
                $allEvenements = $this->evenementRepository->findByFilters($type, $recherche);
                $evenements = array_filter($allEvenements, function($evenement) {
                    return $evenement->getGroupeCibles()->isEmpty();
                });
            } else {
                // Utilisateur connecté
                $userGroups = $user->getGroupes();
                $allEvenements = $this->evenementRepository->findByFilters($type, $recherche);
                
                $evenements = array_filter($allEvenements, function($evenement) use ($user, $userGroups) {
                    // L'utilisateur peut voir ses propres événements
                    if ($evenement->getCreateur() === $user) {
                        return true;
                    }
                    
                    // Si l'événement n'a pas de groupes cibles, visible par tous
                    $eventGroups = $evenement->getGroupeCibles();
                    if ($eventGroups->isEmpty()) {
                        return true;
                    }
                    
                    // Vérifier si l'utilisateur appartient à un des groupes cibles
                    foreach ($eventGroups as $eventGroup) {
                        if ($userGroups->contains($eventGroup)) {
                            return true;
                        }
                    }
                    
                    return false;
                });
            }
            
            $evenements = array_values($evenements);
        } else {
            // Admin voit tout
            $evenements = $this->evenementRepository->findByFilters($type, $recherche);
        }

        // Filtrage par statut
        if ($statut) {
            $evenements = array_filter($evenements, fn($e) => $e->getStatut() === $statut);
        }

        // Tri
        if (!$type && !$recherche) {
            $evenements = $this->evenementRepository->getEvenementsTries($tri, $ordre, $statut);
        } else {
            usort($evenements, function($a, $b) use ($tri, $ordre) {
                $valA = $tri === 'dateFin' ? $a->getDateFin() : $a->getDateDebut();
                $valB = $tri === 'dateFin' ? $b->getDateFin() : $b->getDateDebut();
                if ($valA == $valB) return 0;
                return ($valA < $valB ? -1 : 1) * ($ordre === 'ASC' ? 1 : -1);
            });
        }

        $statutsDisponibles = $this->evenementRepository->getStatutsDisponibles();

        // Calcul des statistiques
        $totalParticipants = 0;
        $totalTauxRemplissage = 0;
        $evenementsAVenir = 0;
        $now = new \DateTime();

        foreach ($evenements as $evenement) {
            $participants = $this->evenementRepository->getNombreParticipants($evenement);
            $tauxRemplissage = $this->evenementRepository->getTauxRemplissage($evenement);

            $totalParticipants += $participants;
            $totalTauxRemplissage += $tauxRemplissage;

            if ($evenement->getDateDebut() > $now) {
                $evenementsAVenir++;
            }
        }

        $stats = [
            'evenementsAVenir' => $evenementsAVenir,
            'totalParticipants' => $totalParticipants,
            'tauxRemplissageMoyen' => count($evenements) > 0 ? round($totalTauxRemplissage / count($evenements), 1) : 0,
            'revenusGeneres' => 0,
        ];

        // Calculer les permissions d'affichage (édition/suppression/inscription) par événement
        $actions = [];
        foreach ($evenements as $evt) {
            $canEdit = $isAdmin || ($user && $evt->getCreateur() === $user);

            // Suppression : si inscrit(s) et non-admin, désactiver la suppression
            $inscriptionsCount = $em->getRepository(InscriptionEvenement::class)->count(['evenement' => $evt]);
            $canDelete = ($isAdmin || ($user && $evt->getCreateur() === $user)) && ($isAdmin || $inscriptionsCount == 0);

            // Inscription : déléguer à la méthode canUserSubscribe pour la logique métier
            $canInscrire = false;
            if ($user && !$isAdmin) {
                $subscribeCheck = $this->canUserSubscribe($user, $evt, $em);
                $canInscrire = $subscribeCheck['can_subscribe'];
            } elseif ($user && $isAdmin) {
                // admin can always see inscription control (but action may be restricted server-side)
                $canInscrire = true;
            }

            $actions[$evt->getId()] = [
                'can_edit' => (bool) $canEdit,
                'can_delete' => (bool) $canDelete,
                'can_inscribe' => (bool) $canInscrire,
            ];
        }

        return $this->render('evenement/evenement.html.twig', [
            'evenements' => $evenements,
            'statutsDisponibles' => $statutsDisponibles,
            'stats' => $stats,
            'tri' => $tri,
            'ordre' => $ordre,
            'type' => $type,
            'statut' => $statut,
            'recherche' => $recherche,
            'is_admin' => $isAdmin,
            'actions' => $actions,
        ]);
    }

    #[Route('/nouveau', name: 'evenement_add', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Vérifier les permissions de création
        $allowedRoles = ['ROLE_ADMIN', 'ROLE_MEDECIN', 'ROLE_RESPONSABLE_LABO', 'ROLE_RESPONSABLE_PARA', 'ROLE_PATIENT'];
        $hasPermission = false;
        
        foreach ($allowedRoles as $role) {
            if ($this->isGranted($role)) {
                $hasPermission = true;
                break;
            }
        }
        
        if (!$hasPermission) {
            throw new AccessDeniedException('Vous n\'avez pas la permission de créer des événements.');
        }

        $evenement = new Evenement();
        
        // Définir le statut initial selon le rôle
        if ($this->isGranted('ROLE_PATIENT')) {
            $evenement->setStatut('en_attente_approbation');
        } else {
            $evenement->setStatut('planifie');
        }

        $form = $this->createForm(EvenementType::class, $evenement, [
            'user_role' => $user->getRoles()[0] ?? 'ROLE_USER',
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenement->setCreeLe(new \DateTime());
            $evenement->setCreateur($user);

            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement ajouté avec succès');
            
            if ($this->isGranted('ROLE_PATIENT')) {
                $this->addFlash('info', 'Votre événement est en attente d\'approbation par un administrateur.');
            }

            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/new.html.twig', [
            'form' => $form->createView(),
            'is_patient' => $this->isGranted('ROLE_PATIENT'),
        ]);
    }

    #[Route('/{id}/modifier', name: 'evenement_update', methods: ['GET','POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        // Vérifier les permissions
        if (!$isAdmin) {
            if ($evenement->getCreateur() !== $user) {
                throw new AccessDeniedException('Vous ne pouvez modifier que vos propres événements.');
            }
            
            if ($this->isGranted('ROLE_PATIENT') && $evenement->getStatut() === 'approuve') {
                $this->addFlash('warning', 'Les événements approuvés ne peuvent plus être modifiés. Contactez un administrateur.');
                return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
            }
        }

        $form = $this->createForm(EvenementType::class, $evenement, [
            'user_role' => $user->getRoles()[0] ?? 'ROLE_USER',
            'is_admin' => $isAdmin,
            'is_edit' => true,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $evenement->setModifieLe(new \DateTime());
            
            if ($isAdmin && $evenement->getStatut() === 'en_attente_approbation') {
                $evenement->setStatut('planifie');
                $this->addFlash('success', 'Événement approuvé et modifié avec succès.');
            } else {
                $this->addFlash('success', 'Événement modifié avec succès.');
            }
            
            $em->flush();

            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if (!$isAdmin && $evenement->getCreateur() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez supprimer que vos propres événements.');
        }

        $inscriptionsCount = $em->getRepository(InscriptionEvenement::class)
            ->count(['evenement' => $evenement]);
            
        if ($inscriptionsCount > 0 && !$isAdmin) {
            $this->addFlash('danger', 'Impossible de supprimer cet événement car il a déjà des inscriptions. Contactez un administrateur.');
            return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $this->evenementRepository->supprimerEvenement($evenement);
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('evenement_list');
    }

    #[Route('/{id}', name: 'evenement_view', methods: ['GET'])]
    public function view(Evenement $evenement, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $debug = $request->query->get('debug_perms') === '1';
        
        // Roles allowed to view all events (admin + trusted staff)
        $isPrivilegedViewer = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MEDECIN') || $this->isGranted('ROLE_RESPONSABLE_LABO') || $this->isGranted('ROLE_RESPONSABLE_PARA');

        // Vérifier si l'utilisateur peut voir cet événement
        // Les administrateurs et le personnel privilégié voient tout
        $canView = true;
        if (!$isPrivilegedViewer && !$debug) {
            // L'utilisateur non-admin
            $eventGroups = $evenement->getGroupeCibles();
            if (!$user) {
                // Utilisateur non connecté : peut voir les événements ouverts à tous
                if (!$eventGroups->isEmpty()) {
                    $canView = false;
                }
            } else {
                // Utilisateur connecté : vérifier qu'il est créateur ou dans les groupes cibles
                if ($evenement->getCreateur() !== $user) {
                    $userGroups = $user->getGroupes();
                    if (!$eventGroups->isEmpty()) {
                        $canView = false;
                        foreach ($eventGroups as $eventGroup) {
                            if ($userGroups->contains($eventGroup)) {
                                $canView = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        // If debug mode is enabled, do not throw; continue to render page so template debug panel can show values.

        $nombreParticipants = $this->evenementRepository->getNombreParticipants($evenement);
        $tauxRemplissage = $this->evenementRepository->getTauxRemplissage($evenement);
        $revenusGeneres = $nombreParticipants * ($evenement->getTarif() ?? 0);
        
        // Vérifier si l'utilisateur est déjà inscrit
        $userInscription = null;
        if ($user) {
            try {
                $userInscription = $em->getRepository(InscriptionEvenement::class)
                    ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);
            } catch (\Throwable $e) {
                // Base de données incomplète (colonne manquante) — afficher la page sans planter
                $this->addFlash('warning', 'Problème base de données : colonne manquante. Affichage limité.');
                $userInscription = null;
            }
        }

        // Vérifier si l'utilisateur peut s'inscrire (logique serveur)
        $canSubscribe = $user ? $this->canUserSubscribe($user, $evenement, $em) : [
            'can_subscribe' => false,
            'message' => 'Vous devez être connecté pour vous inscrire.'
        ];

        // Calculer permissions côté serveur pour la vue (édition/suppression/inscription)
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isCreator = $user && $evenement->getCreateur() && $evenement->getCreateur() === $user;

        $inscriptionsCount = $em->getRepository(InscriptionEvenement::class)->count(['evenement' => $evenement]);

        $canEdit = $isAdmin || $isCreator;
        $canDelete = ($isAdmin || $isCreator) && ($isAdmin || $inscriptionsCount == 0);
        $canInscribe = $user ? ($isAdmin ? true : ($canSubscribe['can_subscribe'] ?? false)) : false;

        $perms = [
            'can_edit' => (bool) $canEdit,
            'can_delete' => (bool) $canDelete,
            'can_inscribe' => (bool) $canInscribe,
        ];

        return $this->render('evenement/view.html.twig', [
            'evenement' => $evenement,
            'nombreParticipants' => $nombreParticipants,
            'tauxRemplissage' => $tauxRemplissage,
            'revenusGeneres' => $revenusGeneres,
            'user_inscription' => $userInscription,
            'can_subscribe' => $canSubscribe,
            'perms' => $perms,
        ]);
    }

    #[Route('/{id}/inscrire', name: 'evenement_inscrire', methods: ['POST'])]
    public function inscrire(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour vous inscrire.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification stricte de l'accès à l'événement (même logique que view())
        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($evenement->getCreateur() !== $user) {
                $userGroups = $user->getGroupes();
                $eventGroups = $evenement->getGroupeCibles();
                
                if (!$eventGroups->isEmpty()) {
                    $canView = false;
                    foreach ($eventGroups as $eventGroup) {
                        if ($userGroups->contains($eventGroup)) {
                            $canView = true;
                            break;
                        }
                    }
                    
                    if (!$canView) {
                        throw new AccessDeniedException('Vous n\'avez pas accès à cet événement.');
                    }
                }
            }
        }

        // Vérifier si l'utilisateur peut s'inscrire
        $canSubscribe = $this->canUserSubscribe($user, $evenement, $em);
        
        if (!$canSubscribe['can_subscribe']) {
            $this->addFlash('warning', $canSubscribe['message']);
            return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
        }

        // Vérifier si déjà inscrit
        $existing = $em->getRepository(InscriptionEvenement::class)
            ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);

        if ($existing) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
        }

        // Vérifier les places disponibles
        if ($evenement->getPlacesMax() !== null) {
            $inscriptionsCount = $em->getRepository(InscriptionEvenement::class)
                ->count(['evenement' => $evenement]);
                
            if ($inscriptionsCount >= $evenement->getPlacesMax()) {
                $this->addFlash('danger', 'Désolé, cet événement est complet.');
                return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
            }
        }

        // Créer l'inscription
        $inscription = new InscriptionEvenement();
        $inscription->setEvenement($evenement);
        $inscription->setUtilisateur($user);
        $inscription->setDateInscription(new \DateTime());
        $inscription->setStatut('confirme');
        $inscription->setPresent(false);
        $inscription->setCreeLe(new \DateTime());
        
        // Déterminer le groupe cible si applicable
        $userGroups = $user->getGroupes();
        $eventGroups = $evenement->getGroupeCibles();
        
        if (!$eventGroups->isEmpty()) {
            foreach ($eventGroups as $eventGroup) {
                if ($userGroups->contains($eventGroup)) {
                    $inscription->setGroupeCible($eventGroup);
                    break;
                }
            }
        }

        $em->persist($inscription);
        $em->flush();

        $this->addFlash('success', 'Inscription réussie !');
        return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/inscrire', name: 'evenement_inscrire_get', methods: ['GET'])]
    public function inscrireGet(Evenement $evenement): Response
    {
        $this->addFlash('info', 'Veuillez utiliser le formulaire d\'inscription sur la page de l\'événement.');
        return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/desinscrire', name: 'evenement_desinscrire', methods: ['POST'])]
    public function desinscrire(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $inscription = $em->getRepository(InscriptionEvenement::class)
            ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);

        if ($inscription) {
            $em->remove($inscription);
            $em->flush();
            $this->addFlash('success', 'Vous avez été désinscrit de cet événement.');
        }

        return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/approuver', name: 'evenement_approuver', methods: ['POST'])]
    public function approuver(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($evenement->getStatut() === 'en_attente_approbation') {
            $evenement->setStatut('planifie');
            $em->flush();
            
            $this->addFlash('success', 'Événement approuvé avec succès.');
        }

        return $this->redirectToRoute('evenement_view', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/participants', name: 'evenement_participants', methods: ['GET'])]
    public function participants(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $participants = $em->getRepository(InscriptionEvenement::class)
            ->findBy(['evenement' => $evenement]);

        return $this->render('evenement/participants.html.twig', [
            'evenement' => $evenement,
            'participants' => $participants,
        ]);
    }

    #[Route('/calendrier', name: 'evenement_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if ($isAdmin) {
            $evenements = $this->evenementRepository->findAll();
        } else {
            $evenements = $this->evenementRepository->findPublicEvents();
        }

        return $this->render('evenement/calendar.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/promouvoir', name: 'evenement_promote', methods: ['GET'])]
    public function promote(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('evenement/promote.html.twig');
    }

    #[Route('/exporter', name: 'evenement_export', methods: ['GET'])]
    public function export(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $evenements = $this->evenementRepository->findAllWithStats();

        return $this->render('evenement/export.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/parametres', name: 'evenement_settings', methods: ['GET'])]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('evenement/settings.html.twig');
    }

    /**
     * Vérifie si un utilisateur peut s'inscrire à un événement
     */
    private function canUserSubscribe(Utilisateur $user, Evenement $evenement, EntityManagerInterface $em): array
    {
        // Vérifier le statut de l'événement
        if (!in_array($evenement->getStatut(), ['planifie', 'approuve'])) {
            return [
                'can_subscribe' => false,
                'message' => 'Cet événement n\'est pas ouvert aux inscriptions.'
            ];
        }

        // Vérifier si l'utilisateur est le créateur
        if ($evenement->getCreateur() === $user) {
            return [
                'can_subscribe' => false,
                'message' => 'Vous ne pouvez pas vous inscrire à votre propre événement.'
            ];
        }

        // Vérifier les groupes cibles
        $eventGroups = $evenement->getGroupeCibles();
        $userGroups = $user->getGroupes();
        
        if (!$eventGroups->isEmpty()) {
            $hasMatchingGroup = false;
            foreach ($eventGroups as $eventGroup) {
                if ($userGroups->contains($eventGroup)) {
                    $hasMatchingGroup = true;
                    break;
                }
            }
            
            if (!$hasMatchingGroup) {
                return [
                    'can_subscribe' => false,
                    'message' => 'Cet événement n\'est pas destiné à votre profil.'
                ];
            }
        }

        // Vérifier la date
        $now = new \DateTime();
        if ($evenement->getDateDebut() <= $now) {
            return [
                'can_subscribe' => false,
                'message' => 'Les inscriptions sont closes pour cet événement.'
            ];
        }

        return [
            'can_subscribe' => true,
            'message' => ''
        ];
    }
}