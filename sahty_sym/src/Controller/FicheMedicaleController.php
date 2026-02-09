<?php

namespace App\Controller;

use App\Entity\FicheMedicale;
use App\Entity\Patient;
use App\Form\FicheMedicaleType;
use App\Repository\FicheMedicaleRepository;
use App\Repository\PatientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/fiche-medicale')]
class FicheMedicaleController extends AbstractController
{
    // VOTRE VERSION UNIFIÉE - Gère tout dans une page
    #[Route('/', name: 'app_fiche_medicale_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        FicheMedicaleRepository $ficheMedicaleRepository,
        PatientRepository $patientRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Mode par défaut : LISTE
        $mode = 'list';
        $fiche = null;
        $form = null;
        
        // Si on veut créer une nouvelle fiche
        if ($request->query->has('new')) {
            $mode = 'new';
            $fiche = new FicheMedicale();
            
            // CRITIQUE: Associer automatiquement un patient
            // Solution 1: Prendre le premier patient disponible (pour test)
            $firstPatient = $patientRepository->findOneBy([], ['id' => 'ASC']);
            
            if ($firstPatient) {
                $fiche->setPatient($firstPatient);
                $this->addFlash('info', 'Fiche associée au patient: ' . $firstPatient->getNom() . ' ' . $firstPatient->getPrenom());
            } else {
                $this->addFlash('warning', 'Aucun patient trouvé dans la base. Veuillez d\'abord créer un patient.');
            }
            
            $form = $this->createForm(FicheMedicaleType::class, $fiche);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Vérifier qu'un patient est bien associé
                if (!$fiche->getPatient() && $firstPatient) {
                    $fiche->setPatient($firstPatient);
                }
                
                // Ajouter la date de création
                if (!$fiche->getCreeLe()) {
                    $fiche->setCreeLe(new \DateTime());
                }
                
                $entityManager->persist($fiche);
                $entityManager->flush();
                
                $this->addFlash('success', 'Fiche médicale créée avec succès !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
        }
        
        // Si on veut voir une fiche
        if ($request->query->has('view')) {
            $mode = 'view';
            $ficheId = $request->query->get('view');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if (!$fiche) {
                $this->addFlash('error', 'Fiche non trouvée !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
        }
        
        // Si on veut éditer une fiche
        if ($request->query->has('edit')) {
            $mode = 'edit';
            $ficheId = $request->query->get('edit');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if (!$fiche) {
                $this->addFlash('error', 'Fiche non trouvée !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
            
            $form = $this->createForm(FicheMedicaleType::class, $fiche);
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                // Mettre à jour la date de modification
                $fiche->setModifieLe(new \DateTime());
                
                $entityManager->flush();
                $this->addFlash('success', 'Fiche médicale modifiée avec succès !');
                return $this->redirectToRoute('app_fiche_medicale_index');
            }
        }
        
        // Si on veut supprimer une fiche
        if ($request->isMethod('POST') && $request->request->has('delete_id')) {
            $ficheId = $request->request->get('delete_id');
            $fiche = $ficheMedicaleRepository->find($ficheId);
            
            if ($fiche && $this->isCsrfTokenValid('delete'.$ficheId, $request->request->get('_token'))) {
                $entityManager->remove($fiche);
                $entityManager->flush();
                $this->addFlash('success', 'Fiche médicale supprimée avec succès !');
            } else {
                $this->addFlash('error', 'Token CSRF invalide ou fiche non trouvée !');
            }
            
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        // Récupère toutes les fiches pour la liste
        $fiches = $ficheMedicaleRepository->findAll();
        
        // Si pas de formulaire créé, en créer un vide pour le mode new
        if ($mode === 'new' && !$form) {
            $fiche = new FicheMedicale();
            // Associer automatiquement le premier patient
            $firstPatient = $patientRepository->findOneBy([], ['id' => 'ASC']);
            if ($firstPatient) {
                $fiche->setPatient($firstPatient);
            }
            $form = $this->createForm(FicheMedicaleType::class, $fiche);
        }
        
        // Calculer les statistiques
        $totalFiches = count($fiches);
        $fichesActives = array_filter($fiches, fn($f) => $f->getStatut() === 'actif');
        $fichesModifiees = array_filter($fiches, fn($f) => $f->getStatut() === 'modifié');
        $fichesAvecIMC = array_filter($fiches, fn($f) => $f->getTaille() && $f->getPoids());
        
        return $this->render('fiche_medicale/index.html.twig', [
            'fiches' => $fiches,
            'mode' => $mode,
            'fiche' => $fiche,
            'form' => $form ? $form->createView() : null,
            'totalFiches' => $totalFiches,
            'fichesActives' => count($fichesActives),
            'fichesModifiees' => count($fichesModifiees),
            'fichesAvecIMC' => count($fichesAvecIMC),
        ]);
    }
    
    // REDIRECTIONS pour les anciennes routes (pour éviter les erreurs 404)
    #[Route('/new', name: 'app_fiche_medicale_new', methods: ['GET', 'POST'])]
    public function newRedirect(): Response
    {
        // Redirige vers la page principale avec ?new=true
        return $this->redirectToRoute('app_fiche_medicale_index', ['new' => true]);
    }
    
    #[Route('/{id}', name: 'app_fiche_medicale_show', methods: ['GET'])]
    public function showRedirect($id): Response
    {
        // Redirige vers la page principale avec ?view=ID
        return $this->redirectToRoute('app_fiche_medicale_index', ['view' => $id]);
    }
    
    #[Route('/{id}/edit', name: 'app_fiche_medicale_edit', methods: ['GET', 'POST'])]
    public function editRedirect($id): Response
    {
        // Redirige vers la page principale avec ?edit=ID
        return $this->redirectToRoute('app_fiche_medicale_index', ['edit' => $id]);
    }
    
    #[Route('/{id}', name: 'app_fiche_medicale_delete', methods: ['POST'])]
    public function deleteRedirect(Request $request, $id): Response
    {
        // Pour les anciens formulaires de suppression
        // Redirige vers la page principale qui gérera la suppression via delete_id
        return $this->redirectToRoute('app_fiche_medicale_index');
    }
    
    // Route pour l'IMPRESSION d'une fiche
    #[Route('/print/{id}', name: 'app_fiche_medicale_print', methods: ['GET'])]
    public function print(FicheMedicale $fiche): Response
    {
        // Calculer l'IMC si taille et poids sont disponibles
        if ($fiche->getTaille() && $fiche->getPoids()) {
            $imc = $fiche->getPoids() / ($fiche->getTaille() * $fiche->getTaille());
            $fiche->setImc($imc);
            
            // Déterminer la catégorie IMC
            if ($imc < 18.5) {
                $categorie = 'Maigreur';
            } elseif ($imc < 25) {
                $categorie = 'Normal';
            } elseif ($imc < 30) {
                $categorie = 'Surpoids';
            } else {
                $categorie = 'Obésité';
            }
            $fiche->setCategorieImc($categorie);
        }
        
        return $this->render('fiche_medicale/print.html.twig', [
            'fiche' => $fiche,
        ]);
    }
    
    // OPTIONNEL: Route pour créer une fiche avec un patient spécifique
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
            $this->addFlash('error', 'Patient non trouvé !');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        $fiche = new FicheMedicale();
        $fiche->setPatient($patient);
        
        $form = $this->createForm(FicheMedicaleType::class, $fiche);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $fiche->setCreeLe(new \DateTime());
            $entityManager->persist($fiche);
            $entityManager->flush();
            
            $this->addFlash('success', 'Fiche médicale créée pour ' . $patient->getNom() . ' ' . $patient->getPrenom() . ' !');
            return $this->redirectToRoute('app_fiche_medicale_index');
        }
        
        return $this->render('fiche_medicale/index.html.twig', [
            'fiches' => [],
            'mode' => 'new',
            'fiche' => $fiche,
            'form' => $form->createView(),
            'totalFiches' => 0,
            'fichesActives' => 0,
            'fichesModifiees' => 0,
            'fichesAvecIMC' => 0,
        ]);
    }
    #[Route('/fiche/new/for-rdv/{rdvId}', name: 'app_fiche_medicale_new_for_rdv', methods: ['GET', 'POST'])]
public function newForRdv(
    Request $request,
    int $rdvId,
    EntityManagerInterface $entityManager
): Response
{
    // Récupérer le rendez-vous
    $rdv = $entityManager->getRepository(RendezVous::class)->find($rdvId);
    
    if (!$rdv) {
        $this->addFlash('error', 'Rendez-vous non trouvé !');
        return $this->redirectToRoute('app_rdv_mes_rdv');
    }
    
    // Vérifier que l'utilisateur est bien le patient du rendez-vous
    if ($this->getUser()->getId() !== $rdv->getPatient()->getId()) {
        $this->addFlash('error', 'Accès non autorisé !');
        return $this->redirectToRoute('app_rdv_mes_rdv');
    }
    
    // Vérifier si une fiche existe déjà pour ce rendez-vous
    if ($rdv->getFicheMedicale()) {
        $this->addFlash('info', 'Une fiche médicale existe déjà pour ce rendez-vous.');
        return $this->redirectToRoute('app_fiche_medicale_index', ['view' => $rdv->getFicheMedicale()->getId()]);
    }
    
    // Créer une nouvelle fiche
    $fiche = new FicheMedicale();
    
    // Pré-remplir avec le patient du rendez-vous
    $fiche->setPatient($rdv->getPatient());
    
    $form = $this->createForm(FicheMedicaleType::class, $fiche);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        // Associer la fiche au rendez-vous
        $rdv->setFicheMedicale($fiche);
        
        // Sauvegarder
        $entityManager->persist($fiche);
        $entityManager->flush();
        
        $this->addFlash('success', 'Fiche médicale créée avec succès et associée à votre rendez-vous !');
        return $this->redirectToRoute('app_rdv_mes_rdv');
    }
    
    // Utiliser le template existant avec des variables supplémentaires
    $fiches = []; // Vide car on est en mode création
    return $this->render('fiche_medicale/index.html.twig', [
        'fiches' => $fiches,
        'mode' => 'new',
        'fiche' => $fiche,
        'form' => $form->createView(),
        'totalFiches' => 0,
        'fichesActives' => 0,
        'fichesModifiees' => 0,
        'fichesAvecIMC' => 0,
        'isFromRdv' => true, // Nouvelle variable pour indiquer que ça vient d'un rdv
        'rdv' => $rdv, // Passer le rendez-vous au template
    ]);
}
}