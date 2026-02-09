<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Entity\FicheMedicale;
use App\Entity\Patient;
use App\Form\RendezVousType;
use App\Form\FicheMedicaleType;  // ✅ AJOUTÉ
use App\Repository\MedecinRepository;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RDVController extends AbstractController
{
    /**
     * 📋 Page de prise de rendez-vous (GET/POST)
     */
    #[Route('/rdv/prendre', name: 'app_rdv_prendre', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function prendre(
        Request $request,
        EntityManagerInterface $em,
        MedecinRepository $medecinRepository
    ): Response {
        // Vérifier que l'utilisateur est un patient
        $patient = $this->getUser();
        if (!$patient instanceof Patient) {
            $this->addFlash('error', '❌ Seuls les patients peuvent prendre rendez-vous');
            return $this->redirectToRoute('home');
        }

        // 1️⃣ Créer l'entité rendez-vous
        $rdv = new RendezVous();
        $rdv->setPatient($patient);

        // 2️⃣ Créer et traiter le formulaire
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        // 3️⃣ Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Valider les données
            if (!$rdv->getMedecin()) {
                $this->addFlash('error', '❌ Veuillez sélectionner un médecin');
                return $this->redirectToRoute('app_rdv_prendre');
            }

            if (!$rdv->getDateRdv() || !$rdv->getHeureRdv()) {
                $this->addFlash('error', '❌ Veuillez sélectionner une date et une heure');
                return $this->redirectToRoute('app_rdv_prendre');
            }

            // Vérifier que la date est dans le futur
            $rdvDateTime = new \DateTime();
            $rdvDateTime->setDate(
                $rdv->getDateRdv()->format('Y'),
                $rdv->getDateRdv()->format('m'),
                $rdv->getDateRdv()->format('d')
            );
            $rdvDateTime->setTime(
                $rdv->getHeureRdv()->format('H'),
                $rdv->getHeureRdv()->format('i')
            );

            if ($rdvDateTime < new \DateTime()) {
                $this->addFlash('error', '❌ La date et l\'heure doivent être dans le futur');
                return $this->redirectToRoute('app_rdv_prendre');
            }

            // Vérifier les conflits d'horaires
            $conflictingRdv = $em->getRepository(RendezVous::class)->findBy([
                'medecin' => $rdv->getMedecin(),
                'dateRdv' => $rdv->getDateRdv(),
                'heureRdv' => $rdv->getHeureRdv(),
                'statut' => 'en attente'
            ]);

            if (!empty($conflictingRdv)) {
                $this->addFlash('error', '⚠️ Ce créneau horaire est déjà réservé. Veuillez choisir un autre créneau');
                return $this->redirectToRoute('app_rdv_prendre');
            }

            // 4️⃣ Définir les paramètres automatiques
            $rdv->setStatut('en attente');
            $rdv->setCreeLe(new \DateTime());

            // 5️⃣ Sauvegarder en base de données
            $em->persist($rdv);
            $em->flush();

            // 6️⃣ Succès
            $this->addFlash('success', '✅ Rendez-vous confirmé avec succès! Veuillez compléter votre fiche médicale.');

            // Redirection vers la création de fiche médicale
            return $this->redirectToRoute('app_fiche_medicale_new_for_rdv', [
                'rdvId' => $rdv->getId()
            ]);
        }

        // 5️⃣ Afficher le formulaire
        return $this->render('rdv/prendre.html.twig', [
            'form' => $form->createView(),
            'medecins' => $medecinRepository->findBy(['estActif' => true]),
        ]);
    }

    // ❌ SUPPRIMER TOUTE LA MÉTHODE newForRdv D'ICI
    // Elle doit rester uniquement dans FicheMedicaleController

    /**
     * ✏️ Modifier un rendez-vous existant
     */
    #[Route('/rdv/modifier/{id}', name: 'app_rdv_modifier', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function modifier(
        int $id,
        Request $request,
        RendezVousRepository $rdvRepository,
        EntityManagerInterface $em,
        MedecinRepository $medecinRepository
    ): Response {
        // Récupérer le rendez-vous
        $rdv = $rdvRepository->find($id);

        if (!$rdv) {
            throw $this->createNotFoundException('Rendez-vous non trouvé');
        }

        // Vérifier que c'est le patient du RDV
        if ($rdv->getPatient()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous');
        }

        // Vérifier que le RDV n'est pas annulé
        if ($rdv->getStatut() === 'Annulé') {
            $this->addFlash('error', '❌ Impossible de modifier un rendez-vous annulé');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }

        // Vérifier que le RDV n'est pas passé
        $rdvDateTime = new \DateTime();
        $rdvDateTime->setDate(
            $rdv->getDateRdv()->format('Y'),
            $rdv->getDateRdv()->format('m'),
            $rdv->getDateRdv()->format('d')
        );
        $rdvDateTime->setTime(
            $rdv->getHeureRdv()->format('H'),
            $rdv->getHeureRdv()->format('i')
        );

        if ($rdvDateTime < new \DateTime()) {
            $this->addFlash('error', '❌ Impossible de modifier un rendez-vous passé');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }

        // Sauvegarder les anciennes valeurs pour la vérification de conflits
        $oldMedecin = $rdv->getMedecin();
        $oldDate = $rdv->getDateRdv();
        $oldHeure = $rdv->getHeureRdv();

        // Créer et traiter le formulaire
        $form = $this->createForm(RendezVousType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valider les données
            if (!$rdv->getMedecin()) {
                $this->addFlash('error', '❌ Veuillez sélectionner un médecin');
                return $this->redirectToRoute('app_rdv_modifier', ['id' => $id]);
            }

            if (!$rdv->getDateRdv() || !$rdv->getHeureRdv()) {
                $this->addFlash('error', '❌ Veuillez sélectionner une date et une heure');
                return $this->redirectToRoute('app_rdv_modifier', ['id' => $id]);
            }

            // Vérifier que la nouvelle date est dans le futur
            $newRdvDateTime = new \DateTime();
            $newRdvDateTime->setDate(
                $rdv->getDateRdv()->format('Y'),
                $rdv->getDateRdv()->format('m'),
                $rdv->getDateRdv()->format('d')
            );
            $newRdvDateTime->setTime(
                $rdv->getHeureRdv()->format('H'),
                $rdv->getHeureRdv()->format('i')
            );

            if ($newRdvDateTime < new \DateTime()) {
                $this->addFlash('error', '❌ La date et l\'heure doivent être dans le futur');
                return $this->redirectToRoute('app_rdv_modifier', ['id' => $id]);
            }

            // Vérifier les conflits seulement si le créneau a changé
            $creneauChanged = (
                $rdv->getMedecin()->getId() !== $oldMedecin->getId() ||
                $rdv->getDateRdv()->format('Y-m-d') !== $oldDate->format('Y-m-d') ||
                $rdv->getHeureRdv()->format('H:i') !== $oldHeure->format('H:i')
            );

            if ($creneauChanged) {
                $conflictingRdv = $em->getRepository(RendezVous::class)->createQueryBuilder('r')
                    ->where('r.medecin = :medecin')
                    ->andWhere('r.dateRdv = :date')
                    ->andWhere('r.heureRdv = :heure')
                    ->andWhere('r.statut = :statut')
                    ->andWhere('r.id != :currentId')
                    ->setParameter('medecin', $rdv->getMedecin())
                    ->setParameter('date', $rdv->getDateRdv())
                    ->setParameter('heure', $rdv->getHeureRdv())
                    ->setParameter('statut', 'en attente')
                    ->setParameter('currentId', $id)
                    ->getQuery()
                    ->getResult();

                if (!empty($conflictingRdv)) {
                    $this->addFlash('error', '⚠️ Ce créneau horaire est déjà réservé. Veuillez choisir un autre créneau');
                    return $this->redirectToRoute('app_rdv_modifier', ['id' => $id]);
                }
            }

            // Sauvegarder les modifications
            $em->flush();

            $this->addFlash('success', '✅ Rendez-vous modifié avec succès!');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }

        return $this->render('rdv/modifier.html.twig', [
            'form' => $form->createView(),
            'rdv' => $rdv,
            'medecins' => $medecinRepository->findBy(['estActif' => true]),
        ]);
    }

    /**
     * 📅 Liste des rendez-vous du patient
     */
    #[Route('/rdv/mes-rdv', name: 'app_rdv_mes_rdv')]
    #[IsGranted('ROLE_PATIENT')]
    public function mesRendezVous(
        RendezVousRepository $rdvRepository
    ): Response {
        $patient = $this->getUser();

        if (!$patient instanceof Patient) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer tous les RDV du patient
        $rdvs = $rdvRepository->findBy(
            ['patient' => $patient],
            ['dateRdv' => 'DESC', 'heureRdv' => 'DESC']
        );

        return $this->render('rdv/mes_rdv.html.twig', [
            'rendez_vous' => $rdvs,
        ]);
    }

    /**
     * ❌ Annuler un rendez-vous
     */
    #[Route('/rdv/annuler/{id}', name: 'app_rdv_annuler', methods: ['POST'])]
    #[IsGranted('ROLE_PATIENT')]
    public function annulerRendezVous(
        int $id,
        RendezVousRepository $rdvRepository,
        EntityManagerInterface $em
    ): Response {
        $rdv = $rdvRepository->find($id);

        if (!$rdv) {
            throw $this->createNotFoundException('Rendez-vous non trouvé');
        }

        // Vérifier que c'est le patient du RDV
        if ($rdv->getPatient()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Vérifier que le RDV n'est pas déjà annulé
        if ($rdv->getStatut() === 'Annulé') {
            $this->addFlash('error', '❌ Ce rendez-vous est déjà annulé');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }

        // Vérifier que le RDV n'est pas passé
        $rdvDateTime = new \DateTime();
        $rdvDateTime->setDate(
            $rdv->getDateRdv()->format('Y'),
            $rdv->getDateRdv()->format('m'),
            $rdv->getDateRdv()->format('d')
        );
        $rdvDateTime->setTime(
            $rdv->getHeureRdv()->format('H'),
            $rdv->getHeureRdv()->format('i')
        );

        if ($rdvDateTime < new \DateTime()) {
            $this->addFlash('error', '❌ Impossible d\'annuler un rendez-vous passé');
            return $this->redirectToRoute('app_rdv_mes_rdv');
        }

        // Annuler
        $rdv->setStatut('Annulé');
        $em->flush();

        $this->addFlash('success', '✅ Rendez-vous annulé avec succès');
        return $this->redirectToRoute('app_rdv_mes_rdv');
    }
}
