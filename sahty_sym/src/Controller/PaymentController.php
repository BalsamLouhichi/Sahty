<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\InscriptionEvenement;
use App\Entity\Utilisateur;
use App\Service\EventRegistrationEmailService;
use App\Service\StripeCheckoutService;
use App\Service\TwilioMessagingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payments', name: 'payment_')]
class PaymentController extends AbstractController
{
    #[Route('/evenements/{id}/checkout', name: 'event_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em,
        StripeCheckoutService $stripeCheckoutService,
        EventRegistrationEmailService $eventRegistrationEmailService,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            $this->addFlash('warning', 'Vous devez etre connecte pour finaliser le paiement.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('checkout' . $evenement->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Requete invalide. Merci de reessayer.');
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }

        if (((float) ($evenement->getTarif() ?? 0)) <= 0) {
            return $this->redirectToRoute('evenements_evenement_inscrire', ['id' => $evenement->getId()]);
        }

        if ($evenement->getCreateur() === $user) {
            $this->addFlash('warning', 'Vous ne pouvez pas payer pour votre propre evenement.');
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }

        $already = $em->getRepository(InscriptionEvenement::class)
            ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);
        if ($already) {
            $this->addFlash('info', 'Vous etes deja inscrit a cet evenement.');
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }

        $eligibility = $this->canUserSubscribe($user, $evenement, $em);
        if (($eligibility['can_subscribe'] ?? false) !== true) {
            $this->addFlash('warning', (string) ($eligibility['message'] ?? 'Inscription indisponible.'));
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }

        if (!$stripeCheckoutService->isConfigured()) {
            $createdInscription = $this->createConfirmedInscription($em, $evenement, $user);
            if ($createdInscription instanceof InscriptionEvenement) {
                $eventRegistrationEmailService->sendConfirmation($createdInscription);
            }
            $this->addFlash(
                'success',
                'Inscription confirmee en mode test gratuit (paiement simule, Stripe non configure).'
            );
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }

        $successUrl = $urlGenerator->generate(
            'evenements_client_event_view',
            ['id' => $evenement->getId(), 'payment' => 'success'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $cancelUrl = $urlGenerator->generate(
            'evenements_client_event_view',
            ['id' => $evenement->getId(), 'payment' => 'cancel'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $session = $stripeCheckoutService->createCheckoutSession(
                $evenement,
                $user,
                $successUrl,
                $cancelUrl
            );

            return $this->redirect($session['url']);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Creation du paiement impossible: ' . $e->getMessage());
            return $this->redirectToRoute('evenements_client_event_view', ['id' => $evenement->getId()]);
        }
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        EntityManagerInterface $em,
        StripeCheckoutService $stripeCheckoutService,
        TwilioMessagingService $twilioMessagingService,
        EventRegistrationEmailService $eventRegistrationEmailService
    ): JsonResponse {
        $payload = (string) $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        try {
            $event = $stripeCheckoutService->parseAndVerifyWebhook($payload, $signature);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $object = $event['data']['object'] ?? [];
            $metadata = $object['metadata'] ?? [];

            $eventId = isset($metadata['event_id']) ? (int) $metadata['event_id'] : 0;
            $userId = isset($metadata['user_id']) ? (int) $metadata['user_id'] : 0;

            if ($eventId > 0 && $userId > 0) {
                $evenement = $em->getRepository(Evenement::class)->find($eventId);
                $user = $em->getRepository(Utilisateur::class)->find($userId);

                if ($evenement instanceof Evenement && $user instanceof Utilisateur) {
                    $createdInscription = $this->createConfirmedInscription($em, $evenement, $user);
                    if ($createdInscription instanceof InscriptionEvenement) {
                        $twilioMessagingService->sendReminderForEvent($user, $evenement, 'sms');
                        $eventRegistrationEmailService->sendConfirmation($createdInscription);
                    }
                }
            }
        }

        return new JsonResponse(['received' => true], 200);
    }

    private function createConfirmedInscription(
        EntityManagerInterface $em,
        Evenement $evenement,
        Utilisateur $user
    ): ?InscriptionEvenement {
        $existing = $em->getRepository(InscriptionEvenement::class)
            ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);

        if ($existing) {
            return null;
        }

        $inscription = new InscriptionEvenement();
        $inscription->setEvenement($evenement);
        $inscription->setUtilisateur($user);
        $inscription->setDateInscription(new \DateTime());
        $inscription->setStatut('confirme');
        $inscription->setPresent(false);
        $inscription->setCreeLe(new \DateTime());

        $eventGroups = $evenement->getGroupeCibles();
        $userGroups = method_exists($user, 'getGroupes')
            ? $user->getGroupes()
            : new ArrayCollection();

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

        return $inscription;
    }

    private function canUserSubscribe(Utilisateur $user, Evenement $evenement, EntityManagerInterface $em): array
    {
        if (!in_array($evenement->getStatut(), ['planifie', 'approuve'], true)) {
            return [
                'can_subscribe' => false,
                'message' => 'Cet evenement n est pas ouvert aux inscriptions.',
            ];
        }

        if ($evenement->getCreateur() === $user) {
            return [
                'can_subscribe' => false,
                'message' => 'Vous ne pouvez pas vous inscrire a votre propre evenement.',
            ];
        }

        $existingInscription = $em->getRepository(InscriptionEvenement::class)
            ->findOneBy(['evenement' => $evenement, 'utilisateur' => $user]);
        if ($existingInscription) {
            return [
                'can_subscribe' => false,
                'message' => 'Vous etes deja inscrit a cet evenement.',
            ];
        }

        $eventGroups = $evenement->getGroupeCibles();
        $userGroups = method_exists($user, 'getGroupes')
            ? $user->getGroupes()
            : new ArrayCollection();

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
                    'message' => 'Cet evenement n est pas destine a votre profil.',
                ];
            }
        }

        $now = new \DateTime();
        if ($evenement->getDateDebut() <= $now) {
            return [
                'can_subscribe' => false,
                'message' => 'Les inscriptions sont closes pour cet evenement.',
            ];
        }

        if ($evenement->getPlacesMax() !== null) {
            $inscriptionsCount = (int) $em->getRepository(InscriptionEvenement::class)
                ->count(['evenement' => $evenement]);
            if ($inscriptionsCount >= $evenement->getPlacesMax()) {
                return [
                    'can_subscribe' => false,
                    'message' => 'Cet evenement est complet.',
                ];
            }
        }

        return ['can_subscribe' => true, 'message' => ''];
    }
}
