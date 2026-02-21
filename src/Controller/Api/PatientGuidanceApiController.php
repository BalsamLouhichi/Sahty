<?php

namespace App\Controller\Api;

use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Service\PatientAppointmentGuidanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/rdv', name: 'api_rdv_')]
class PatientGuidanceApiController extends AbstractController
{
    #[Route('/{id}/patient-guidance', name: 'patient_guidance', methods: ['GET'])]
    #[IsGranted('ROLE_PATIENT')]
    public function guidance(
        RendezVous $rendezVous,
        PatientAppointmentGuidanceService $patientAppointmentGuidanceService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof Patient) {
            return new JsonResponse(['success' => false, 'error' => 'Acces reserve aux patients'], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($rendezVous->getPatient()?->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false, 'error' => 'Acces refuse a ce rendez-vous'], JsonResponse::HTTP_FORBIDDEN);
        }

        $payload = $patientAppointmentGuidanceService->generate($rendezVous);

        return new JsonResponse([
            'success' => true,
            'guidance' => $payload,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}

