<?php

namespace App\Controller\Api;

use App\Entity\RendezVous;
use App\Entity\Utilisateur;
use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/translation', name: 'api_translation_')]
class TranslationApiController extends AbstractController
{
    #[Route('/texts', name: 'texts', methods: ['POST'])]
    public function translateTexts(Request $request, TranslationService $translationService): JsonResponse
    {
        if (!$this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_PATIENT') && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'error' => 'Acces refuse'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return new JsonResponse(['success' => false, 'error' => 'JSON invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $texts = $this->extractTexts($payload);
        if ($texts === []) {
            return new JsonResponse(['success' => false, 'error' => 'Champ "texts" requis'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $targetLanguages = $this->extractTargetLanguages($payload);
        if ($targetLanguages === []) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Fournir "target_language" ou "target_languages"',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sourceLanguage = trim((string) ($payload['source_language'] ?? 'auto'));

        $result = $translationService->translateBatch($texts, $targetLanguages, $sourceLanguage);
        if (!($result['ok'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error' => (string) ($result['error'] ?? 'Erreur de traduction'),
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse([
            'success' => true,
            'source_language' => $sourceLanguage,
            'target_languages' => $targetLanguages,
            'source_texts' => $texts,
            'translations' => $result['translations'] ?? [],
        ]);
    }

    #[Route('/rdv/{id}/medical-context', name: 'rdv_medical_context', methods: ['POST'])]
    public function translateRdvMedicalContext(
        Request $request,
        RendezVous $rendezVous,
        TranslationService $translationService
    ): JsonResponse {
        if (!$this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_PATIENT') && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'error' => 'Acces refuse'], JsonResponse::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur non authentifie'], JsonResponse::HTTP_FORBIDDEN);
        }

        if (!$this->canAccessRendezVousContext($user, $rendezVous)) {
            return new JsonResponse(['success' => false, 'error' => 'Acces refuse a ce rendez-vous'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            $payload = [];
        }

        $targetLanguages = $this->extractTargetLanguages($payload);
        if ($targetLanguages === []) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Fournir "target_language" ou "target_languages"',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sourceLanguage = trim((string) ($payload['source_language'] ?? 'auto'));
        $texts = $this->buildRdvMedicalTexts($rendezVous);
        if ($texts === []) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun contenu medical a traduire pour ce rendez-vous',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $result = $translationService->translateBatch($texts, $targetLanguages, $sourceLanguage);
        if (!($result['ok'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error' => (string) ($result['error'] ?? 'Erreur de traduction'),
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse([
            'success' => true,
            'rdv_id' => $rendezVous->getId(),
            'source_language' => $sourceLanguage,
            'target_languages' => $targetLanguages,
            'source_texts' => $texts,
            'translations' => $result['translations'] ?? [],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function extractTexts(array $payload): array
    {
        $raw = $payload['texts'] ?? null;
        if (!is_array($raw)) {
            return [];
        }

        $texts = [];
        foreach ($raw as $key => $value) {
            $k = trim((string) $key);
            $v = trim((string) $value);
            if ($k !== '' && $v !== '') {
                $texts[$k] = $v;
            }
        }

        return $texts;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, string>
     */
    private function extractTargetLanguages(array $payload): array
    {
        $single = trim((string) ($payload['target_language'] ?? ''));
        $languages = [];
        if ($single !== '') {
            $languages[] = $single;
        }

        $multiple = $payload['target_languages'] ?? [];
        if (is_array($multiple)) {
            foreach ($multiple as $lang) {
                $value = trim((string) $lang);
                if ($value !== '') {
                    $languages[] = $value;
                }
            }
        }

        return array_values(array_unique($languages));
    }

    /**
     * @return array<string, string>
     */
    private function buildRdvMedicalTexts(RendezVous $rendezVous): array
    {
        $texts = [
            'motif_rendez_vous' => trim((string) $rendezVous->getRaison()),
        ];

        $fiche = $rendezVous->getFicheMedicale();
        if ($fiche !== null) {
            $texts['antecedents'] = trim((string) $fiche->getAntecedents());
            $texts['allergies'] = trim((string) $fiche->getAllergies());
            $texts['traitement_en_cours'] = trim((string) $fiche->getTraitementEnCours());
            $texts['diagnostic'] = trim((string) $fiche->getDiagnostic());
            $texts['traitement_prescrit'] = trim((string) $fiche->getTraitementPrescrit());
            $texts['observations'] = trim((string) $fiche->getObservations());
        }

        $filtered = [];
        foreach ($texts as $key => $value) {
            if ($value !== '') {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function canAccessRendezVousContext(Utilisateur $user, RendezVous $rendezVous): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_PATIENT') && $rendezVous->getPatient()?->getId() === $user->getId()) {
            return true;
        }

        if ($this->isGranted('ROLE_MEDECIN') && $rendezVous->getMedecin()?->getId() === $user->getId()) {
            return true;
        }

        return false;
    }
}

