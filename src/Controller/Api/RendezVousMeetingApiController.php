<?php

namespace App\Controller\Api;

use App\Entity\Medecin;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use App\Service\MeetingSchedulerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/rdv', name: 'api_rdv_')]
class RendezVousMeetingApiController extends AbstractController
{
    #[Route('/{id}/consultation-type', name: 'consultation_type', methods: ['POST'])]
    public function setConsultationType(
        RendezVous $rendezVous,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->canAccessRendezVous($rendezVous)) {
            return $this->json(['message' => 'Acces refuse'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        $type = strtolower((string) ($payload['type'] ?? ''));
        $allowedTypes = ['cabinet', 'en_ligne'];

        if (!in_array($type, $allowedTypes, true)) {
            return $this->json([
                'message' => 'Type invalide. Valeurs autorisees: cabinet, en_ligne',
            ], 422);
        }

        $rendezVous->setTypeConsultation($type);
        if ($type === 'cabinet') {
            $rendezVous->setMeetingUrl(null);
            $rendezVous->setMeetingProvider(null);
            $rendezVous->setMeetingCreatedAt(null);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Type de consultation mis a jour',
            'rendez_vous' => $this->serializeRendezVousMeeting($rendezVous),
        ]);
    }

    #[Route('/{id}/meeting/generate', name: 'meeting_generate', methods: ['POST'])]
    public function generateMeetingLink(
        RendezVous $rendezVous,
        MeetingSchedulerService $meetingSchedulerService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->canAccessRendezVous($rendezVous)) {
            return $this->json(['message' => 'Acces refuse'], 403);
        }

        if ($rendezVous->getTypeConsultation() !== 'en_ligne') {
            return $this->json([
                'message' => 'Le rendez-vous doit etre en_ligne pour generer un lien',
            ], 422);
        }

        try {
            $meeting = $meetingSchedulerService->createForRendezVous($rendezVous);
        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Generation du lien impossible',
                'error' => $e->getMessage(),
            ], 500);
        }

        $rendezVous->setMeetingProvider($meeting['provider']);
        $rendezVous->setMeetingUrl($meeting['url']);
        $rendezVous->setMeetingCreatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return $this->json([
            'message' => 'Lien de consultation genere',
            'meeting' => [
                'provider' => $rendezVous->getMeetingProvider(),
                'url' => $rendezVous->getMeetingUrl(),
                'created_at' => $rendezVous->getMeetingCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ], 201);
    }

    #[Route('/{id}/meeting', name: 'meeting_get', methods: ['GET'])]
    public function getMeetingLink(RendezVous $rendezVous): JsonResponse
    {
        if (!$this->canAccessRendezVous($rendezVous)) {
            return $this->json(['message' => 'Acces refuse'], 403);
        }

        return $this->json([
            'rendez_vous' => $this->serializeRendezVousMeeting($rendezVous),
        ]);
    }

    #[Route('/{id}/meeting', name: 'meeting_delete', methods: ['DELETE'])]
    public function deleteMeetingLink(
        RendezVous $rendezVous,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->canAccessRendezVous($rendezVous)) {
            return $this->json(['message' => 'Acces refuse'], 403);
        }

        $rendezVous->setMeetingUrl(null);
        $rendezVous->setMeetingProvider(null);
        $rendezVous->setMeetingCreatedAt(null);
        $rendezVous->setTypeConsultation('cabinet');

        $entityManager->flush();

        return $this->json([
            'message' => 'Lien de consultation supprime',
            'rendez_vous' => $this->serializeRendezVousMeeting($rendezVous),
        ]);
    }

    private function canAccessRendezVous(RendezVous $rendezVous): bool
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($user instanceof Patient && $rendezVous->getPatient()?->getId() === $user->getId()) {
            return true;
        }

        if ($user instanceof Medecin && $rendezVous->getMedecin()?->getId() === $user->getId()) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRendezVousMeeting(RendezVous $rendezVous): array
    {
        return [
            'id' => $rendezVous->getId(),
            'type_consultation' => $rendezVous->getTypeConsultation(),
            'meeting_provider' => $rendezVous->getMeetingProvider(),
            'meeting_url' => $rendezVous->getMeetingUrl(),
            'meeting_created_at' => $rendezVous->getMeetingCreatedAt()?->format(\DateTimeInterface::ATOM),
            'statut' => $rendezVous->getStatut(),
        ];
    }
}
