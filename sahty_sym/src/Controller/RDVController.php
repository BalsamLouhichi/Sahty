<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\FicheMedicale;
use App\Form\RendezVousType;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RDVController extends AbstractController
{
    #[Route('/rdv/prendre', name: 'app_rdv_prendre', methods: ['GET', 'POST'])]
    public function prendre(
        Request $request,
        EntityManagerInterface $em,
        MedecinRepository $medecinRepository
    ): Response {
        // 1️⃣ Créer l'entité
        $rdv = new RendezVous();

        // 2️⃣ Créer le formulaire lié à l'entité
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        // 3️⃣ Vérification formulaire soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Champs automatiques
            $rdv->setStatut('Confirmé');
            $rdv->setCreeLe(new \DateTime());

            // Associer le patient si connecté
            if ($this->getUser()) {
                $rdv->setPatient($this->getUser());
            }

            // 4️⃣ Sauvegarde en base
            $em->persist($rdv);
            $em->flush(); // 🔥 INSERT SQL ICI

            $this->addFlash('success', '✅ Rendez-vous confirmé avec succès');

            // REDIRIGER VERS LA CRÉATION DE FICHE MÉDICALE
            return $this->redirectToRoute('app_fiche_medicale_new_for_rdv', [
                'rdvId' => $rdv->getId()
            ]);
        }

        // 5️⃣ Affichage du formulaire
        return $this->render('rdv/index.html.twig', [
            'form' => $form->createView(),
            'medecins' => $medecinRepository->findAll(),
            'is_patient' => true,
        ]);
    }

    #[Route('/rdv/mes-rdv', name: 'app_rdv_mes_rdv')]
    public function mesRendezVous(EntityManagerInterface $em): Response
    {
        $patient = $this->getUser();

        $rdvs = $em->getRepository(RendezVous::class)->findBy(
            ['patient' => $patient],
            ['dateRdv' => 'DESC']
        );

        return $this->render('rdv/mes_rdv.html.twig', [
            'rendez_vous' => $rdvs,
        ]);
    }
}