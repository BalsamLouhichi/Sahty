<?php

namespace App\AI;

use App\Entity\DemandeAnalyse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResultatAnalyseAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string,
     *   raw?: array<string, mixed>
     * }
     */
    public function analyzePdf(string $absolutePdfPath, DemandeAnalyse $demandeAnalyse): array
    {
        $endpoint = $_ENV['APP_AI_RESULTAT_ENDPOINT'] ?? null;
        $apiKey = $_ENV['APP_AI_RESULTAT_API_KEY'] ?? null;
        $provider = strtolower((string) ($_ENV['APP_AI_RESULTAT_PROVIDER'] ?? 'custom'));

        if (!$endpoint || !is_file($absolutePdfPath)) {
            return $this->fallbackAnalysis($demandeAnalyse);
        }

        if ($provider === 'huggingface') {
            return $this->analyzeWithHuggingFace($absolutePdfPath, $demandeAnalyse, (string) $endpoint, (string) $apiKey);
        }

        return $this->analyzeWithCustomApi($absolutePdfPath, $demandeAnalyse, (string) $endpoint, (string) $apiKey);
    }

    /**
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string,
     *   raw?: array<string, mixed>
     * }
     */
    private function analyzeWithCustomApi(string $absolutePdfPath, DemandeAnalyse $demandeAnalyse, string $endpoint, string $apiKey): array
    {
        $payload = [
            'demande_id' => $demandeAnalyse->getId(),
            'type_bilan' => $demandeAnalyse->getTypeBilan(),
            'patient' => [
                'nom' => $demandeAnalyse->getPatient()?->getNomComplet(),
                'age' => $demandeAnalyse->getPatient()?->getAge(),
                'sexe' => $demandeAnalyse->getPatient()?->getSexe(),
            ],
            'medecin' => [
                'nom' => $demandeAnalyse->getMedecin()?->getNomComplet(),
                'specialite' => $demandeAnalyse->getMedecin()?->getSpecialite(),
            ],
            'pdf_base64' => base64_encode((string) file_get_contents($absolutePdfPath)),
        ];

        try {
            $headers = ['Accept' => 'application/json'];
            if ($apiKey) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => 45,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            if ($statusCode >= 400 || !is_array($data)) {
                return $this->fallbackAnalysis($demandeAnalyse);
            }

            return [
                'anomalies' => is_array($data['anomalies'] ?? null) ? $data['anomalies'] : [],
                'danger_score' => $this->normalizeDangerScore($data['danger_score'] ?? 15),
                'danger_level' => $this->normalizeDangerLevel((string) ($data['danger_level'] ?? 'LOW')),
                'resume' => (string) ($data['resume'] ?? 'Analyse automatique effectuee. Validation medicale requise.'),
                'model_version' => (string) ($data['model_version'] ?? 'api-unknown'),
                'raw' => $data,
            ];
        } catch (\Throwable) {
            return $this->fallbackAnalysis($demandeAnalyse);
        }
    }

    /**
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string,
     *   raw?: array<string, mixed>
     * }
     */
    private function analyzeWithHuggingFace(string $absolutePdfPath, DemandeAnalyse $demandeAnalyse, string $endpoint, string $apiKey): array
    {
        $model = (string) ($_ENV['APP_AI_RESULTAT_MODEL'] ?? 'BioMistral/BioMistral-7B-DARE');
        $pdfText = $this->extractPdfText($absolutePdfPath);
        if (!$this->hasEnoughMedicalText($pdfText)) {
            return $this->unreadablePdfAnalysis($demandeAnalyse);
        }
        $ruleBased = $this->buildRuleBasedAnalysis($pdfText, $demandeAnalyse);

        if ($apiKey === '') {
            return $ruleBased ?? $this->fallbackAnalysis($demandeAnalyse);
        }

        $systemPrompt = 'Tu es un assistant d aide a la lecture de bilans biologiques. Reponds uniquement en JSON valide. N invente pas de valeurs.';
        $userPrompt = sprintf(
            "Analyse ce bilan et retourne uniquement un JSON avec les cles: anomalies (array), danger_score (0-100), danger_level (LOW|MEDIUM|HIGH|CRITICAL), resume (string), model_version (string).\n".
            "Regles obligatoires:\n".
            "- Base toi prioritairement sur les valeurs et references presentes dans le texte PDF.\n".
            "- Si conflit entre type_bilan et valeurs du PDF, priorite stricte au PDF.\n".
            "- Chaque anomalie doit contenir si possible: name, value, reference, severity.\n".
            "- Le resume doit etre factuel, court, et lie aux anomalies detectees.\n".
            "Contexte demande:\n".
            "- demande_id: %s\n".
            "- type_bilan: %s\n".
            "- patient_nom: %s\n".
            "- patient_age: %s\n".
            "- patient_sexe: %s\n".
            "- medecin: %s (%s)\n\n".
            "Texte extrait du PDF:\n%s",
            (string) ($demandeAnalyse->getId() ?? ''),
            (string) ($demandeAnalyse->getTypeBilan() ?? ''),
            (string) ($demandeAnalyse->getPatient()?->getNomComplet() ?? ''),
            (string) ($demandeAnalyse->getPatient()?->getAge() ?? ''),
            (string) ($demandeAnalyse->getPatient()?->getSexe() ?? ''),
            (string) ($demandeAnalyse->getMedecin()?->getNomComplet() ?? ''),
            (string) ($demandeAnalyse->getMedecin()?->getSpecialite() ?? ''),
            $pdfText !== '' ? $pdfText : '[Texte PDF non exploitable]'
        );

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.0,
                    'max_tokens' => 900,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            if ($statusCode >= 400 || !is_array($data)) {
                return $ruleBased ?? $this->fallbackAnalysis($demandeAnalyse);
            }

            $content = (string) ($data['choices'][0]['message']['content'] ?? '');
            $parsed = $this->parseJsonFromText($content);
            if (!is_array($parsed)) {
                $unstructured = [
                    'anomalies' => [],
                    'danger_score' => 20,
                    'danger_level' => 'LOW',
                    'resume' => $content !== '' ? $content : 'Analyse IA non structuree.',
                    'model_version' => (string) ($data['model'] ?? $model),
                    'raw' => $data,
                ];

                return $this->reconcileWithRuleBased($unstructured, $ruleBased);
            }

            $result = [
                'anomalies' => is_array($parsed['anomalies'] ?? null) ? $parsed['anomalies'] : [],
                'danger_score' => $this->normalizeDangerScore($parsed['danger_score'] ?? 15),
                'danger_level' => $this->normalizeDangerLevel((string) ($parsed['danger_level'] ?? 'LOW')),
                'resume' => (string) ($parsed['resume'] ?? 'Analyse automatique effectuee. Validation medicale requise.'),
                'model_version' => (string) ($parsed['model_version'] ?? ($data['model'] ?? $model)),
                'raw' => $data,
            ];

            $result = $this->applyClinicalGuardrails($result, $pdfText);

            return $this->reconcileWithRuleBased($result, $ruleBased);
        } catch (\Throwable) {
            return $ruleBased ?? $this->fallbackAnalysis($demandeAnalyse);
        }
    }

    /**
     * Fallback local en cas d'indisponibilite de l'API IA.
     *
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string
     * }
     */
    private function fallbackAnalysis(DemandeAnalyse $demandeAnalyse): array
    {
        return [
            'anomalies' => [],
            'danger_score' => 15,
            'danger_level' => 'LOW',
            'resume' => sprintf(
                'Analyse automatique initialisee pour la demande #%d (%s). Aucune anomalie critique detectee automatiquement. Validation medicale requise.',
                $demandeAnalyse->getId() ?? 0,
                $demandeAnalyse->getTypeBilan() ?? 'bilan'
            ),
            'model_version' => 'fallback-v1',
        ];
    }

    private function normalizeDangerScore(mixed $score): int
    {
        $value = (int) $score;
        if ($value < 0) {
            return 0;
        }
        if ($value > 100) {
            return 100;
        }
        return $value;
    }

    private function normalizeDangerLevel(string $level): string
    {
        $normalized = strtoupper(trim($level));
        $allowed = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];

        return in_array($normalized, $allowed, true) ? $normalized : 'LOW';
    }

    private function parseJsonFromText(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractPdfText(string $absolutePdfPath): string
    {
        $content = (string) @file_get_contents($absolutePdfPath);
        if ($content === '') {
            return '';
        }

        // First pass: extract literal PDF text operations "(...) Tj".
        if (preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches) === 1 || !empty($matches[1])) {
            $segments = [];
            foreach ($matches[1] as $raw) {
                $decoded = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], (string) $raw);
                $decoded = trim($decoded);
                if ($decoded !== '') {
                    $segments[] = $decoded;
                }
            }
            if (!empty($segments)) {
                $text = implode("\n", $segments);
                return preg_replace('/\s+/', ' ', $text) ?? '';
            }
        }

        // Fallback: broad text scrape for non-literal PDFs.
        preg_match_all('/[A-Za-z0-9\.,:;%\+\-\/\(\)\[\] ]{4,}/', $content, $matches);
        $chunks = $matches[0] ?? [];
        if (!$chunks) {
            return '';
        }
        $text = trim(implode(' ', array_slice($chunks, 0, 1200)));
        if (strlen($text) > 12000) {
            $text = substr($text, 0, 12000);
        }

        return preg_replace('/\s+/', ' ', $text) ?? '';
    }

    /**
     * Renforce la securite de classement pour certains patterns cliniques evidents.
     *
     * @param array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string,
     *   raw?: array<string, mixed>
     * } $result
     *
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string,
     *   raw?: array<string, mixed>
     * }
     */
    private function applyClinicalGuardrails(array $result, string $pdfText): array
    {
        $result = $this->sanitizeAnomaliesAgainstPdfText($result, $pdfText);

        $asat = $this->extractFirstNumericValue($pdfText, 'ASAT');
        $alat = $this->extractFirstNumericValue($pdfText, 'ALAT');
        $ggt = $this->extractFirstNumericValue($pdfText, 'GGT');
        $bilirubine = $this->extractFirstNumericValue($pdfText, 'BILIRUBINE');
        $tp = $this->extractFirstNumericValue($pdfText, 'TP');

        $severeHepaticPattern = ($asat !== null && $asat >= 200.0)
            || ($alat !== null && $alat >= 300.0)
            || ($ggt !== null && $ggt >= 200.0)
            || ($bilirubine !== null && $bilirubine >= 30.0)
            || ($tp !== null && $tp <= 60.0);

        if (!$severeHepaticPattern) {
            return $result;
        }

        $result['danger_level'] = $this->maxDangerLevel($result['danger_level'], 'HIGH');
        $result['danger_score'] = max((int) $result['danger_score'], 75);

        if (!is_array($result['anomalies'])) {
            $result['anomalies'] = [];
        }
        $result['anomalies'][] = [
            'name' => 'Hepatic pattern guardrail',
            'severity' => 'HIGH',
            'note' => 'Anomalies hepatiques majeures detectees automatiquement (regle serveur).',
        ];

        return $result;
    }

    /**
     * Drop anomalies that are clearly unrelated to the uploaded PDF content.
     */
    private function sanitizeAnomaliesAgainstPdfText(array $result, string $pdfText): array
    {
        $anomalies = $result['anomalies'] ?? [];
        if (!is_array($anomalies) || trim($pdfText) === '') {
            return $result;
        }

        $pdfUpper = strtoupper($pdfText);
        $filtered = [];
        foreach ($anomalies as $anomaly) {
            if (is_string($anomaly)) {
                $label = strtoupper(trim($anomaly));
                if ($label !== '' && str_contains($pdfUpper, $label)) {
                    $filtered[] = $anomaly;
                }
                continue;
            }

            if (!is_array($anomaly)) {
                continue;
            }

            $label = (string) ($anomaly['name'] ?? $anomaly['label'] ?? $anomaly['nom'] ?? '');
            $labelUpper = strtoupper(trim($label));
            if ($labelUpper === '' || str_contains($pdfUpper, $labelUpper)) {
                $filtered[] = $anomaly;
            }
        }

        $result['anomalies'] = $filtered;
        return $result;
    }

    private function extractFirstNumericValue(string $text, string $label): ?float
    {
        $pattern = sprintf('/%s\s*:\s*([0-9]+(?:[.,][0-9]+)?)/i', preg_quote($label, '/'));
        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        return (float) str_replace(',', '.', (string) $matches[1]);
    }

    private function maxDangerLevel(string $current, string $minimum): string
    {
        $order = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];
        $currentNorm = $this->normalizeDangerLevel($current);
        $minimumNorm = $this->normalizeDangerLevel($minimum);

        return ($order[$currentNorm] ?? 1) >= ($order[$minimumNorm] ?? 1) ? $currentNorm : $minimumNorm;
    }

    /**
     * Build deterministic analysis from extracted PDF text.
     *
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string
     * }|null
     */
    private function buildRuleBasedAnalysis(string $pdfText, DemandeAnalyse $demandeAnalyse): ?array
    {
        $cleanText = trim($pdfText);
        if ($cleanText === '') {
            return null;
        }

        $analytes = [
            'ASAT' => ['ASAT', 'AST'],
            'ALAT' => ['ALAT', 'ALT'],
            'GGT' => ['GGT'],
            'PAL' => ['PAL', 'PHOSPHATASE ALCALINE'],
            'BILIRUBINE TOTALE' => ['BILIRUBINE TOTALE', 'BILIRUBINE'],
            'TP' => ['TP', 'TAUX DE PROTHROMBINE'],
            'CRP' => ['CRP'],
            'PROCALCITONINE' => ['PROCALCITONINE'],
            'GLYCEMIE' => ['GLYCEMIE', 'GLUCOSE'],
            'HBA1C' => ['HBA1C'],
            'CREATININE' => ['CREATININE'],
            'UREE' => ['UREE'],
            'DFG' => ['DFG', 'EGFR'],
            'POTASSIUM' => ['POTASSIUM', 'K'],
            'SODIUM' => ['SODIUM', 'NA'],
            'CHLORURE' => ['CHLORURE', 'CL'],
            'CALCIUM' => ['CALCIUM'],
            'HB' => ['HEMOGLOBINE', 'HB'],
            'GB' => ['LEUCOCYTES', 'GB', 'WBC'],
            'PLAQUETTES' => ['PLAQUETTES'],
            'VGM' => ['VGM', 'MCV'],
            'TCMH' => ['TCMH', 'MCH'],
            'FERRITINE' => ['FERRITINE'],
            'CHOLESTEROL' => ['CHOLESTEROL'],
            'TRIGLYCERIDES' => ['TRIGLYCERIDES'],
        ];
        $defaultBounds = [
            'ASAT' => [null, 35.0],
            'ALAT' => [null, 45.0],
            'GGT' => [null, 55.0],
            'PAL' => [40.0, 130.0],
            'BILIRUBINE TOTALE' => [3.0, 12.0],
            'TP' => [70.0, 100.0],
            'CRP' => [null, 5.0],
            'PROCALCITONINE' => [null, 0.5],
            'GLYCEMIE' => [0.70, 1.10],
            'HBA1C' => [null, 6.5],
            'CREATININE' => [6.0, 12.0],
            'UREE' => [0.15, 0.45],
            'DFG' => [90.0, null],
            'POTASSIUM' => [3.5, 5.1],
            'SODIUM' => [135.0, 145.0],
            'CHLORURE' => [98.0, 107.0],
            'CALCIUM' => [2.20, 2.60],
            'HB' => [12.0, 16.0],
            'GB' => [4.0, 10.0],
            'PLAQUETTES' => [150.0, 400.0],
            'VGM' => [80.0, 100.0],
            'TCMH' => [27.0, 32.0],
            'FERRITINE' => [15.0, 150.0],
            'CHOLESTEROL' => [null, 200.0],
            'TRIGLYCERIDES' => [null, 150.0],
        ];

        $anomalies = [];
        $score = 10;
        $upperText = strtoupper($cleanText);

        foreach ($analytes as $marker => $aliases) {
            $line = $this->findAnalyteLine($cleanText, $aliases);
            if ($line === null) {
                continue;
            }

            $aliasPattern = implode('|', array_map(
                static fn (string $a): string => preg_quote($a, '/'),
                $aliases
            ));
            if (preg_match('/(?:\b(?:' . $aliasPattern . ')\b)\s*(?:[:=]|\s)\s*([0-9]+(?:[.,][0-9]+)?)/i', $line, $matches) !== 1) {
                continue;
            }

            $value = (float) str_replace(',', '.', (string) $matches[1]);
            $unit = '';
            if (preg_match('/\b' . preg_quote((string) $matches[1], '/') . '\b\s*([A-Za-z%\/\.0-9]+)/', $line, $um) === 1) {
                $unit = trim((string) $um[1]);
            }
            $reference = '';
            if (preg_match('/\(([^)]*)\)/', $line, $rm) === 1) {
                $reference = trim((string) $rm[1]);
            }
            [$low, $high] = $this->parseReferenceBounds($reference);
            if ($low === null && $high === null && isset($defaultBounds[$marker])) {
                [$low, $high] = $defaultBounds[$marker];
            }

            $isLow = $low !== null && $value < $low;
            $isHigh = $high !== null && $value > $high;
            if (!$isLow && !$isHigh) {
                continue;
            }

            $severity = $this->computeSeverityFromDeviation($value, $low, $high);
            $score += match ($severity) {
                'CRITICAL' => 35,
                'HIGH' => 25,
                'MEDIUM' => 15,
                default => 8,
            };

            $anomalies[] = [
                'name' => $marker,
                'value' => $value . ($unit !== '' ? ' ' . $unit : ''),
                'reference' => $reference !== '' ? $reference : $this->formatBounds($low, $high),
                'severity' => $severity,
                'direction' => $isHigh ? 'HIGHER_THAN_REFERENCE' : 'LOWER_THAN_REFERENCE',
            ];
        }

        if ($anomalies === []) {
            if (str_contains($upperText, 'ASAT') || str_contains($upperText, 'ALAT') || str_contains($upperText, 'GLYCEMIE')) {
                return [
                    'anomalies' => [],
                    'danger_score' => 25,
                    'danger_level' => 'MEDIUM',
                    'resume' => 'Valeurs detectees dans le PDF, mais references insuffisantes pour une qualification fiable de toutes les anomalies.',
                    'model_version' => 'rule-engine-v1',
                ];
            }
            return null;
        }

        $score = $this->normalizeDangerScore($score);
        $level = $this->scoreToLevel($score);

        // Specific hepatic escalation when multiple hepatic markers are altered.
        $hepaticHits = 0;
        foreach ($anomalies as $anomaly) {
            $name = strtoupper((string) ($anomaly['name'] ?? ''));
            if (in_array($name, ['ASAT', 'ALAT', 'GGT', 'PAL', 'BILIRUBINE TOTALE', 'TP'], true)) {
                $hepaticHits++;
            }
        }
        if ($hepaticHits >= 3) {
            $level = $this->maxDangerLevel($level, 'HIGH');
            $score = max($score, 75);
        }

        return [
            'anomalies' => $anomalies,
            'danger_score' => $score,
            'danger_level' => $level,
            'resume' => $this->buildRuleBasedResume($anomalies, $level, $demandeAnalyse),
            'model_version' => 'rule-engine-v1',
        ];
    }

    /**
     * Merge LLM output with deterministic rule-based output.
     */
    private function reconcileWithRuleBased(array $modelResult, ?array $ruleBased): array
    {
        if ($ruleBased === null) {
            return $modelResult;
        }

        $merged = $modelResult;
        $merged['danger_score'] = max(
            $this->normalizeDangerScore($modelResult['danger_score'] ?? 0),
            $this->normalizeDangerScore($ruleBased['danger_score'] ?? 0)
        );
        $merged['danger_level'] = $this->maxDangerLevel(
            (string) ($modelResult['danger_level'] ?? 'LOW'),
            (string) ($ruleBased['danger_level'] ?? 'LOW')
        );

        $modelAnomalies = is_array($modelResult['anomalies'] ?? null) ? $modelResult['anomalies'] : [];
        $ruleAnomalies = is_array($ruleBased['anomalies'] ?? null) ? $ruleBased['anomalies'] : [];
        $merged['anomalies'] = count($ruleAnomalies) > 0 ? $ruleAnomalies : $modelAnomalies;

        $modelResume = (string) ($modelResult['resume'] ?? '');
        $ruleResume = (string) ($ruleBased['resume'] ?? '');
        if ($modelResume === '' || strlen($modelResume) < 20) {
            $merged['resume'] = $ruleResume;
        } else {
            $importantLabels = array_map(
                static fn (array $a): string => strtoupper((string) ($a['name'] ?? '')),
                array_filter($ruleAnomalies, static fn ($a): bool => is_array($a))
            );
            $mentionsImportant = false;
            foreach ($importantLabels as $label) {
                if ($label !== '' && str_contains(strtoupper($modelResume), $label)) {
                    $mentionsImportant = true;
                    break;
                }
            }
            $merged['resume'] = $mentionsImportant && count($ruleAnomalies) === 0 ? $modelResume : $ruleResume;
        }

        $merged['model_version'] = (string) ($modelResult['model_version'] ?? 'model-unknown') . '+rule-v1';

        return $merged;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseReferenceBounds(string $reference): array
    {
        $ref = str_replace(',', '.', strtoupper(trim($reference)));
        if ($ref === '') {
            return [null, null];
        }

        if (preg_match('/<\s*([0-9]+(?:\.[0-9]+)?)/', $ref, $m) === 1) {
            return [null, (float) $m[1]];
        }

        if (preg_match('/>\s*([0-9]+(?:\.[0-9]+)?)/', $ref, $m) === 1) {
            return [(float) $m[1], null];
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*-\s*([0-9]+(?:\.[0-9]+)?)/', $ref, $m) === 1) {
            return [(float) $m[1], (float) $m[2]];
        }

        return [null, null];
    }

    private function computeSeverityFromDeviation(float $value, ?float $low, ?float $high): string
    {
        $ratio = 0.0;
        if ($high !== null && $value > $high) {
            $ratio = ($value - $high) / max($high, 1.0);
        } elseif ($low !== null && $value < $low) {
            $ratio = ($low - $value) / max($low, 1.0);
        }

        if ($ratio >= 1.0) {
            return 'CRITICAL';
        }
        if ($ratio >= 0.5) {
            return 'HIGH';
        }
        if ($ratio >= 0.2) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function scoreToLevel(int $score): string
    {
        if ($score >= 85) {
            return 'CRITICAL';
        }
        if ($score >= 65) {
            return 'HIGH';
        }
        if ($score >= 35) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function buildRuleBasedResume(array $anomalies, string $level, DemandeAnalyse $demandeAnalyse): string
    {
        $names = [];
        foreach ($anomalies as $anomaly) {
            if (!is_array($anomaly)) {
                continue;
            }
            $n = (string) ($anomaly['name'] ?? '');
            if ($n !== '') {
                $names[] = $n;
            }
        }
        $names = array_values(array_unique($names));
        $list = implode(', ', array_slice($names, 0, 6));

        return sprintf(
            'Analyse basee sur les valeurs du PDF (demande #%d, %s): anomalies detectees sur %s. Niveau global estime: %s. Validation medicale requise.',
            $demandeAnalyse->getId() ?? 0,
            (string) ($demandeAnalyse->getTypeBilan() ?? 'bilan'),
            $list !== '' ? $list : 'parametres biologiques',
            $level
        );
    }

    /**
     * @param list<string> $aliases
     */
    private function findAnalyteLine(string $text, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $pattern = '/^.*\b' . preg_quote($alias, '/') . '\b.*$/mi';
            if (preg_match($pattern, $text, $m) === 1) {
                return trim((string) $m[0]);
            }
        }

        return null;
    }

    private function formatBounds(?float $low, ?float $high): string
    {
        if ($low !== null && $high !== null) {
            return $low . '-' . $high;
        }
        if ($low !== null) {
            return '>' . $low;
        }
        if ($high !== null) {
            return '<' . $high;
        }

        return '';
    }

    private function hasEnoughMedicalText(string $pdfText): bool
    {
        $text = trim($pdfText);
        if (strlen($text) < 80) {
            return false;
        }

        $markers = [
            'ASAT', 'ALAT', 'GGT', 'PAL', 'BILIRUBINE', 'TP', 'CRP', 'PROCALCITONINE',
            'GLYCEMIE', 'HBA1C', 'CREATININE', 'UREE', 'DFG', 'HB', 'GB', 'PLAQUETTES',
            'VGM', 'TCMH', 'FERRITINE', 'CHOLESTEROL', 'TRIGLYCERIDES'
        ];

        $upper = strtoupper($text);
        foreach ($markers as $marker) {
            if (str_contains($upper, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a safe result when PDF is image-only or unreadable without OCR.
     *
     * @return array{
     *   anomalies: array<int, array<string, mixed>>,
     *   danger_score: int,
     *   danger_level: string,
     *   resume: string,
     *   model_version: string
     * }
     */
    private function unreadablePdfAnalysis(DemandeAnalyse $demandeAnalyse): array
    {
        return [
            'anomalies' => [],
            'danger_score' => 20,
            'danger_level' => 'LOW',
            'resume' => sprintf(
                'Le PDF de la demande #%d n est pas lisible automatiquement (scan/image ou texte non exploitable). OCR requis avant interpretation fiable.',
                $demandeAnalyse->getId() ?? 0
            ),
            'model_version' => 'ocr-required-v1',
        ];
    }
}
