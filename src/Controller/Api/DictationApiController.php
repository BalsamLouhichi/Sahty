<?php

namespace App\Controller\Api;

use App\Service\DictationTranscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dictation', name: 'api_dictation_')]
class DictationApiController extends AbstractController
{
    #[Route('/transcribe', name: 'transcribe', methods: ['POST'])]
    public function transcribe(Request $request, DictationTranscriptionService $dictationTranscriptionService): JsonResponse
    {
        if (!$this->isGranted('ROLE_MEDECIN') && !$this->isGranted('ROLE_PATIENT')) {
            return new JsonResponse(['success' => false, 'error' => 'Acces refuse'], JsonResponse::HTTP_FORBIDDEN);
        }

        $audio = $request->files->get('audio');
        if (!$audio instanceof UploadedFile) {
            return new JsonResponse(['success' => false, 'error' => 'Fichier audio manquant'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$audio->isValid()) {
            return new JsonResponse(['success' => false, 'error' => 'Upload audio invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($audio->getSize() !== null && $audio->getSize() > 10 * 1024 * 1024) {
            return new JsonResponse(['success' => false, 'error' => 'Audio trop volumineux (max 10MB)'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $allowedMimeTypes = [
            'audio/webm',
            'video/webm',
            'audio/ogg',
            'video/ogg',
            'audio/wav',
            'audio/x-wav',
            'audio/mp4',
            'video/mp4',
            'audio/mpeg',
            'audio/mp3',
            'audio/x-m4a',
            'application/octet-stream',
        ];
        $allowedExtensions = ['webm', 'ogg', 'wav', 'mp3', 'mp4', 'm4a', 'mpeg'];
        $mimeType = strtolower((string) ($audio->getMimeType() ?: $audio->getClientMimeType() ?: ''));
        $extension = strtolower((string) ($audio->guessExtension() ?: $audio->getClientOriginalExtension() ?: ''));

        $mimeAllowed = in_array($mimeType, $allowedMimeTypes, true);
        $extensionAllowed = in_array($extension, $allowedExtensions, true);
        if (!$mimeAllowed && !$extensionAllowed) {
            return new JsonResponse(['success' => false, 'error' => 'Format audio non supporte'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $language = (string) $request->request->get('language', 'fr');
        $result = $dictationTranscriptionService->transcribe($audio, $language);
        if (!($result['ok'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error' => (string) ($result['error'] ?? 'Erreur de transcription'),
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }

        return new JsonResponse([
            'success' => true,
            'text' => (string) ($result['text'] ?? ''),
        ]);
    }
}
