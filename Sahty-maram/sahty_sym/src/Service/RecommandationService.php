<?php

namespace App\Service;

use App\Entity\Quiz;
use App\Entity\Recommandation;
use App\Repository\RecommandationRepository;

class RecommandationService
{
    public function __construct(private RecommandationRepository $repository)
    {
    }

    /**
     * Get recommendations filtered by score and categories
     *
     * @param Quiz $quiz
     * @param int $score
     * @param array $problems Array of problematic categories
     * @return Recommandation[]
     */
    public function getFiltered(Quiz $quiz, int $score, array $problems = []): array
    {
        $selected = [];

        foreach ($quiz->getRecommandations() as $reco) {
            if (!$this->matchesScore($score, $reco)) {
                continue;
            }

            if (!$this->matchesCategory($problems, $reco)) {
                continue;
            }

            $selected[] = $reco;
        }

        return $this->sortBySeverity($selected);
    }

    /**
     * Check if score falls within recommendation range
     */
    private function matchesScore(int $score, Recommandation $reco): bool
    {
        $minScore = $reco->getMinScore();
        $maxScore = $reco->getMaxScore();

        if ($minScore === null || $maxScore === null) {
            return true; // No restriction
        }

        return $score >= $minScore && $score <= $maxScore;
    }

    /**
     * Check if problematic categories match recommendation targets
     */
    private function matchesCategory(array $problems, Recommandation $reco): bool
    {
        $targets = $reco->getTargetCategories();

        // No target restriction = matches all
        if (!$targets) {
            return true;
        }

        // If no problems, but recommendation has targets = no match
        if (empty($problems)) {
            return false;
        }

        $targetArray = array_map('trim', explode(',', $targets));

        foreach ($targetArray as $target) {
            if (in_array($target, $problems)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sort recommendations by severity: high > medium > low
     */
    private function sortBySeverity(array $recommendations): array
    {
        usort($recommendations, function (Recommandation $a, Recommandation $b) {
            $severityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];

            $aOrder = $severityOrder[$a->getSeverity() ?? 'low'] ?? 1;
            $bOrder = $severityOrder[$b->getSeverity() ?? 'low'] ?? 1;

            return $bOrder <=> $aOrder;
        });

        return $recommendations;
    }

    /**
     * Get all recommendations for a quiz, grouped by severity
     */
    public function getGroupedBySeverity(Quiz $quiz): array
    {
        $grouped = [
            'high' => [],
            'medium' => [],
            'low' => [],
        ];

        foreach ($quiz->getRecommandations() as $reco) {
            $severity = $reco->getSeverity() ?? 'low';
            if (isset($grouped[$severity])) {
                $grouped[$severity][] = $reco;
            }
        }

        return $grouped;
    }

    /**
     * Get urgent recommendations (high severity)
     */
    public function getUrgent(Quiz $quiz): array
    {
        return array_filter(
            $quiz->getRecommandations()->toArray(),
            fn(Recommandation $r) => $r->getSeverity() === 'high'
        );
    }

    /**
     * Count recommendations by severity for a quiz
     */
    public function countBySeverity(Quiz $quiz): array
    {
        return [
            'high' => count(array_filter(
                $quiz->getRecommandations()->toArray(),
                fn(Recommandation $r) => $r->getSeverity() === 'high'
            )),
            'medium' => count(array_filter(
                $quiz->getRecommandations()->toArray(),
                fn(Recommandation $r) => $r->getSeverity() === 'medium'
            )),
            'low' => count(array_filter(
                $quiz->getRecommandations()->toArray(),
                fn(Recommandation $r) => $r->getSeverity() === 'low'
            )),
        ];
    }
}
