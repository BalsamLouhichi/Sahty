<?php

namespace App\Controller;

use App\Form\FicheMedicaleType;
use App\Entity\FicheMedicale;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Repository\FicheMedicaleRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/fiche-medicale')]
class FicheMedicaleController extends AbstractController
{
    /**
     * Page principale - Gère tout dans une page
     */
    #[Route('/', name: 'app_fiche_medicale_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        FicheMedicaleRepository $ficheMedicaleRepository,
        PatientRepository $patientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Déterminer les permissions de l'utilisateur
        $isPatient = $this->isGranted('ROLE_PATIENT');
        $isMedecin = $this->isGranted('ROLE_MEDECIN');
        
        // Mode par défaut : LISTE
        $mode = 'list';
        $fiche = null;
        $form = null;
        
        // RECHERCHE PAR ID
        if ($request->query->has('search_id')) {
            $searchId = $request->query->get('search_id');
            if (!empty($searchId)) {
                $fiche = $ficheMedicaleRepository->find($searchId);
                if ($fiche) {
                    // Rediriger vers la vue de cette fiche
                    return $this->redirectToRoute('app_fiche_medicale_index', ['view' => $fiche->getId()]);
                } else {
                    $this->addFlash('error', '❌ Aucune fiche trouvée avec l\'ID: ' . $searchId);
                }
            }
        }
        
        // CRÉATION D'UNE NOUVELLE FICHE
        if ($request->query->has('new')) {
            $mode = 'new';
            $fiche = new FicheMedicale();
            
            // Si l'utilisateur est un patient, l'associer automatiquement
            if ($isPatient && $this->getUser() instanceof Patient) {
                $fiche->setPatient($this->getUser());
            } else {
                // Sinon, prendre le premier patient disponible (pour test)
                $firstPatient = $patientRepository->findOneBy([], ['id' => 'ASC']);
                if ($firstPatient) {
                    $fiche->setPatient($firstPatient);
                    $this->addFlash('info', '📋 Fiche associée au patient: ' . $firstPatient->getNom() . ' ' . $firstPatient->getPrenom());
                } else {
                    $this->addFlash('warning', '⚠️ Aucun patient trouvé dans la base. Veuillez d\'abord créer un patient.');
                }
            }
            
            $form = $this->createForm(FicheMedicaleType::class, $fiche);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Vérifier qu'un patient est bien associé
                if (!$fiche->getPatient()) {
                    $this->addFlash('error', '❌ Aucun patient associé à cette fiche');
                    return $this->redirectToRoute('app_fiche_medicale_index');
                }
                
                // Définir la date de création et le statut par défaut
                $fiche->setCreeLe(new \DateTime());
                if (!$fiche->getStatut()) {
                    $fiche->setStatut('actif');
                }
                
                // Calculer l'IMC si les données sont disponibles
                if ($fiche->getTaille() && $fiche->getPoids()) {
                    $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                    $fiche->setImc($imc);
                    
                    // Déterminer la catégorie IMC
                    if ($imc < 18.5) {
                        $fiche->setCategorieImc('Maigreur');
                    } elseif ($imc < 25) {
                        $fiche->setCategorieImc('Normal');
                    } elseif ($imc < 30) {
                        $fiche->setCategorieImc('Surpoids');
                    } else {
                        $fiche->setCategorieImc('Obésité');
                    }
                }
                
                $entityManager->persist($fiche);
                $entityManager->flush();
                
                $this->addFlash('success', '✅ Fiche médicale créée avec succès !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
        }
        
        // AFFICHAGE DÉTAILLÉ D'UNE FICHE
        if ($request->query->has('view')) {
            $mode = 'view';
            $ficheId = $request->query->get('view');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if (!$fiche) {
                $this->addFlash('error', '❌ Fiche non trouvée !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
            
            // Calculer l'IMC si non calculé
            if ($fiche->getTaille() && $fiche->getPoids() && !$fiche->getImc()) {
                $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                $fiche->setImc($imc);
                
                if ($imc < 18.5) {
                    $fiche->setCategorieImc('Maigreur');
                } elseif ($imc < 25) {
                    $fiche->setCategorieImc('Normal');
                } elseif ($imc < 30) {
                    $fiche->setCategorieImc('Surpoids');
                } else {
                    $fiche->setCategorieImc('Obésité');
                }
            }
        }
        
        // MODIFICATION D'UNE FICHE
        if ($request->query->has('edit')) {
            $mode = 'edit';
            $ficheId = $request->query->get('edit');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if (!$fiche) {
                $this->addFlash('error', '❌ Fiche non trouvée !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
            
            // Vérifier les permissions
            if ($isPatient && $fiche->getPatient()->getId() !== $this->getUser()->getId()) {
                $this->addFlash('error', '❌ Vous ne pouvez modifier que vos propres fiches');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
            
            $form = $this->createForm(FicheMedicaleType::class, $fiche);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Mettre à jour la date de modification
                $fiche->setModifieLe(new \DateTime());
                $fiche->setStatut('modifié');
                
                // Recalculer l'IMC
                if ($fiche->getTaille() && $fiche->getPoids()) {
                    $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                    $fiche->setImc($imc);
                    
                    if ($imc < 18.5) {
                        $fiche->setCategorieImc('Maigreur');
                    } elseif ($imc < 25) {
                        $fiche->setCategorieImc('Normal');
                    } elseif ($imc < 30) {
                        $fiche->setCategorieImc('Surpoids');
                    } else {
                        $fiche->setCategorieImc('Obésité');
                    }
                }
                
                $entityManager->flush();
                $this->addFlash('success', '✅ Fiche médicale modifiée avec succès !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
        }
        
        // SUPPRESSION D'UNE FICHE
        if ($request->isMethod('POST') && $request->request->has('delete_id')) {
            $ficheId = $request->request->get('delete_id');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if ($fiche && $this->isCsrfTokenValid('delete'.$ficheId, $request->request->get('_token'))) {
                // Vérifier les permissions
                if ($isPatient && $fiche->getPatient()->getId() !== $this->getUser()->getId()) {
                    $this->addFlash('error', '❌ Vous ne pouvez supprimer que vos propres fiches');
                    return $this->redirectToRoute('app_fiche_medicale_index');
                }
                
                $entityManager->remove($fiche);
                $entityManager->flush();
                $this->addFlash('success', '✅ Fiche médicale supprimée avec succès !');
            } else {
                $this->addFlash('error', '❌ Token CSRF invalide ou fiche non trouvée !');
            }
            
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        // Récupérer toutes les fiches pour la liste
        if ($isPatient && $this->getUser() instanceof Patient) {
            // Si c'est un patient, afficher seulement ses fiches
            $fiches = $ficheMedicaleRepository->findBy(['patient' => $this->getUser()]);
        } else {
            // Sinon (médecin/admin), afficher toutes les fiches
            $fiches = $ficheMedicaleRepository->findAll();
        }
        
        // Recherche textuelle
        if ($request->query->has('search')) {
            $searchTerm = $request->query->get('search');
            if (!empty($searchTerm)) {
                $fiches = array_filter($fiches, function($fiche) use ($searchTerm) {
                    $searchLower = strtolower($searchTerm);
                    return 
                        stripos($fiche->getPatient()->getNom(), $searchTerm) !== false ||
                        stripos($fiche->getPatient()->getPrenom(), $searchTerm) !== false ||
                        stripos((string)$fiche->getId(), $searchTerm) !== false ||
                        stripos($fiche->getAntecedents() ?? '', $searchTerm) !== false ||
                        stripos($fiche->getAllergies() ?? '', $searchTerm) !== false;
                });
            }
        }
        
        return $this->render('fiche_medicale/index.html.twig', [
            'fiches' => $fiches,
            'mode' => $mode,
            'fiche' => $fiche,
            'form' => $form ? $form->createView() : null,
            'isPatient' => $isPatient,
            'isMedecin' => $isMedecin,
        ]);
    }
    
    /**
     * 📄 EXPORT PDF - UNE SEULE FICHE
     */
    #[Route('/export-pdf/{id}', name: 'app_fiche_medicale_export_pdf', methods: ['GET'])]
    public function exportPdf(
        int $id,
        FicheMedicaleRepository $ficheMedicaleRepository
    ): Response
    {
        $fiche = $ficheMedicaleRepository->find($id);
        
        if (!$fiche) {
            $this->addFlash('error', '❌ Fiche non trouvée !');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        // Vérifier les permissions
        $isPatient = $this->isGranted('ROLE_PATIENT');
        if ($isPatient && $fiche->getPatient()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', '❌ Accès non autorisé');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        // Recalculer l'IMC si nécessaire
        if ($fiche->getTaille() && $fiche->getPoids()) {
            $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
            $fiche->setImc($imc);
            
            if ($imc < 18.5) {
                $fiche->setCategorieImc('Maigreur');
            } elseif ($imc < 25) {
                $fiche->setCategorieImc('Normal');
            } elseif ($imc < 30) {
                $fiche->setCategorieImc('Surpoids');
            } else {
                $fiche->setCategorieImc('Obésité');
            }
        }
        
        return $this->render('fiche_medicale/pdf_single.html.twig', [
            'fiche' => $fiche,
        ]);
    }
    
    /**
     * 📄 EXPORT PDF - TOUTES LES FICHES
     */
    #[Route('/export-all-pdf', name: 'app_fiche_medicale_export_all_pdf', methods: ['GET'])]
    public function exportAllPdf(
        FicheMedicaleRepository $ficheMedicaleRepository
    ): Response
    {
        $isPatient = $this->isGranted('ROLE_PATIENT');
        
        // Récupérer les fiches selon les permissions
        if ($isPatient && $this->getUser() instanceof Patient) {
            $fiches = $ficheMedicaleRepository->findBy(['patient' => $this->getUser()]);
        } else {
            $fiches = $ficheMedicaleRepository->findAll();
        }
        
        if (empty($fiches)) {
            $this->addFlash('error', '❌ Aucune fiche à exporter');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        // Calculer l'IMC pour toutes les fiches
        foreach ($fiches as $fiche) {
            if ($fiche->getTaille() && $fiche->getPoids()) {
                $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                $fiche->setImc($imc);
                
                if ($imc < 18.5) {
                    $fiche->setCategorieImc('Maigreur');
                } elseif ($imc < 25) {
                    $fiche->setCategorieImc('Normal');
                } elseif ($imc < 30) {
                    $fiche->setCategorieImc('Surpoids');
                } else {
                    $fiche->setCategorieImc('Obésité');
                }
            }
        }
        
        return $this->render('fiche_medicale/pdf_all.html.twig', [
            'fiches' => $fiches,
        ]);
    }
    
    /**
     * REDIRECTIONS pour les anciennes routes
     */
    #[Route('/new', name: 'app_fiche_medicale_new', methods: ['GET', 'POST'])]
    public function newRedirect(): Response
    {
        return $this->redirectToRoute('app_fiche_medicale_index', ['new' => true]);
    }
    
    #[Route('/{id}', name: 'app_fiche_medicale_show', methods: ['GET'])]
    public function showRedirect($id): Response
    {
        return $this->redirectToRoute('app_fiche_medicale_index', ['view' => $id]);
    }
    
    #[Route('/{id}/edit', name: 'app_fiche_medicale_edit', methods: ['GET', 'POST'])]
    public function editRedirect($id): Response
    {
        return $this->redirectToRoute('app_fiche_medicale_index', ['edit' => $id]);
    }
    
    /**
     * Route pour créer une fiche avec un patient spécifique
     */
    #[Route('/new-for-patient/{patientId}', name: 'app_fiche_medicale_new_for_patient', methods: ['GET', 'POST'])]
    public function newForPatient(
        int $patientId,
        Request $request,
        PatientRepository $patientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $patient = $patientRepository->find($patientId);
        
        if (!$patient) {
            $this->addFlash('error', '❌ Patient non trouvé !');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        $fiche = new FicheMedicale();
        $fiche->setPatient($patient);
        
        $form = $this->createForm(FicheMedicaleType::class, $fiche);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $fiche->setCreeLe(new \DateTime());
            if (!$fiche->getStatut()) {
                $fiche->setStatut('actif');
            }
            
            // Calculer l'IMC
            if ($fiche->getTaille() && $fiche->getPoids()) {
                $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                $fiche->setImc($imc);
                
                if ($imc < 18.5) {
                    $fiche->setCategorieImc('Maigreur');
                } elseif ($imc < 25) {
                    $fiche->setCategorieImc('Normal');
                } elseif ($imc < 30) {
                    $fiche->setCategorieImc('Surpoids');
                } else {
                    $fiche->setCategorieImc('Obésité');
                }
            }
            
            $entityManager->persist($fiche);
            $entityManager->flush();
            
            $this->addFlash('success', '✅ Fiche médicale créée pour ' . $patient->getNom() . ' ' . $patient->getPrenom() . ' !');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        return $this->render('fiche_medicale/index.html.twig', [
            'fiches' => [],
            'mode' => 'new',
            'fiche' => $fiche,
            'form' => $form->createView(),
            'isPatient' => $this->isGranted('ROLE_PATIENT'),
            'isMedecin' => $this->isGranted('ROLE_MEDECIN'),
        ]);
    }
    
    /**
     * Route pour créer une fiche depuis un rendez-vous
     */
    #[Route('/new-for-rdv/{rdvId}', name: 'app_fiche_medicale_new_for_rdv', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function newForRdv(
        Request $request,
        int $rdvId,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Récupérer le rendez-vous
        $rdv = $entityManager->getRepository(RendezVous::class)->find($rdvId);
        
        if (!$rdv) {
            $this->addFlash('error', '❌ Rendez-vous non trouvé !');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }
        
        // Vérifier que l'utilisateur est bien le patient du rendez-vous
        if ($this->getUser()->getId() !== $rdv->getPatient()->getId()) {
            $this->addFlash('error', '❌ Accès non autorisé !');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }
        
        // Vérifier si une fiche existe déjà pour ce rendez-vous
        if ($rdv->getFicheMedicale()) {
            $this->addFlash('info', 'ℹ️ Une fiche médicale existe déjà pour ce rendez-vous.');
            return $this->redirectToRoute('app_fiche_medicale_index', ['view' => $rdv->getFicheMedicale()->getId()]);
        }
        
        // Créer une nouvelle fiche
        $fiche = new FicheMedicale();
        $fiche->setPatient($rdv->getPatient());
        
        $form = $this->createForm(FicheMedicaleType::class, $fiche);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Définir les valeurs par défaut
            $fiche->setCreeLe(new \DateTime());
            if (!$fiche->getStatut()) {
                $fiche->setStatut('actif');
            }
            
            // Calculer l'IMC
            if ($fiche->getTaille() && $fiche->getPoids()) {
                $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
                $fiche->setImc($imc);
                
                if ($imc < 18.5) {
                    $fiche->setCategorieImc('Maigreur');
                } elseif ($imc < 25) {
                    $fiche->setCategorieImc('Normal');
                } elseif ($imc < 30) {
                    $fiche->setCategorieImc('Surpoids');
                } else {
                    $fiche->setCategorieImc('Obésité');
                }
            }
            
            // Associer la fiche au rendez-vous
            $rdv->setFicheMedicale($fiche);
            
            // Sauvegarder
            $entityManager->persist($fiche);
            $entityManager->flush();
            
            $this->addFlash('success', '✅ Fiche médicale créée avec succès et associée à votre rendez-vous !');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }
        
        return $this->render('fiche_medicale/index.html.twig', [
            'fiches' => [],
            'mode' => 'new',
            'fiche' => $fiche,
            'form' => $form->createView(),
            'isPatient' => true,
            'isMedecin' => false,
            'isFromRdv' => true,
            'rdv' => $rdv,
        ]);
    }

    // src/Controller/FicheMedicaleController.php
/**
 * ✏️ Modifier une fiche médicale
 */
#[Route('/fiche-medicale/edit/{id}', name: 'app_fiche_medicale_edit', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_PATIENT')]
public function edit(
    int $id,
    Request $request,
    FicheMedicaleRepository $ficheRepository,
    EntityManagerInterface $entityManager
): Response {
    $fiche = $ficheRepository->find($id);
    
    if (!$fiche) {
        $this->addFlash('error', '❌ Fiche médicale introuvable');
        return $this->redirectToRoute('app_fiche_medicale_index');
    }
    
    // Vérifier que c'est le patient de la fiche
    if ($fiche->getPatient()->getId() !== $this->getUser()->getId()) {
        throw $this->createAccessDeniedException();
    }
    
    $form = $this->createForm(FicheMedicaleType::class, $fiche);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // Sécurité : Empêcher les patients de modifier les champs médecin
        if (!$this->isGranted('ROLE_MEDECIN')) {
            $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($fiche);
            $fiche->setDiagnostic($originalData['diagnostic'] ?? null);
            $fiche->setTraitementPrescrit($originalData['traitementPrescrit'] ?? null);
            $fiche->setObservations($originalData['observations'] ?? null);
            $fiche->setStatut($originalData['statut'] ?? 'actif');
        }
        
        // Recalculer l'IMC
        if ($fiche->getTaille() && $fiche->getPoids()) {
            $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
            $fiche->setImc($imc);
        }
        
        $fiche->setModifieLe(new \DateTime());
        $entityManager->flush();
        
        $this->addFlash('success', '✅ Fiche médicale mise à jour !');
        return $this->redirectToRoute('app_rdv_mes_rdv');
    }
    
    return $this->render('fiche_medicale/index.html.twig', [
        'fiches' => [],
        'mode' => 'edit',
        'fiche' => $fiche,
        'form' => $form->createView(),
    ]);
}

#[Route('/search-ajax', name: 'app_fiche_medicale_search_ajax', methods: ['GET'])]
public function searchAjax(Request $request, FicheMedicaleRepository $repository): JsonResponse
{
    $query = $request->query->get('q', '');
    
    if (strlen($query) < 2) {
        return $this->json([]);
    }

    $fiches = $repository->createQueryBuilder('f')
        ->leftJoin('f.patient', 'p')
        ->where('LOWER(p.nom) LIKE LOWER(:query)')
        ->orWhere('LOWER(p.prenom) LIKE LOWER(:query)')
        ->orWhere('LOWER(f.diagnostic) LIKE LOWER(:query)')
        ->orWhere('LOWER(f.statut) LIKE LOWER(:query)')
        ->orWhere('CAST(f.id AS string) LIKE :query')
        ->setParameter('query', '%' . $query . '%')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

    $results = [];
    foreach ($fiches as $fiche) {
        $patient = $fiche->getPatient();
        $results[] = [
            'id' => $fiche->getId(),
            'patient' => $patient ? $patient->getNom() . ' ' . $patient->getPrenom() : 'Patient #' . $fiche->getId(),
            'statut' => $fiche->getStatut(),
            'creeLe' => $fiche->getCreeLe() ? $fiche->getCreeLe()->format('d/m/Y') : '',
            'imc' => $fiche->getImc(),
            'categorieImc' => $fiche->getCategorieImc(),
        ];
    }

    return $this->json($results);
}

 

}
