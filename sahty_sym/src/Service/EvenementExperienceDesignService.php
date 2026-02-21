<?php

namespace App\Service;

use App\Entity\Evenement;

class EvenementExperienceDesignService
{
    public function buildExperiencePack(Evenement $evenement): array
    {
        $durationMinutes = $this->computeDurationMinutes($evenement);
        $phases = $this->extractPhasesFromDescription((string) $evenement->getDescription());
        if (count($phases) < 3) {
            $phases = $this->defaultPhasesByType((string) $evenement->getType());
        }

        $phases = array_slice($phases, 0, 7);
        $phasesWithTiming = $this->allocateTiming($phases, $durationMinutes, $evenement->getDateDebut());
        $theme = $this->themeForEvent($evenement);

        return [
            'poster' => [
                'title' => (string) $evenement->getTitre(),
                'subtitle' => $this->buildSubtitle($evenement, $durationMinutes),
                'date_label' => $evenement->getDateDebut() ? $evenement->getDateDebut()->format('d/m/Y H:i') : 'Date a definir',
                'mode_label' => ucfirst((string) $evenement->getMode()),
                'lieu_label' => $evenement->getLieu() ?: (($evenement->getMode() === 'en_ligne') ? 'Session en ligne' : 'Lieu a confirmer'),
                'theme' => $theme,
            ],
            'cards' => $phasesWithTiming,
            'highlights' => $this->buildHighlights($evenement, $durationMinutes, count($phasesWithTiming)),
        ];
    }

    private function computeDurationMinutes(Evenement $evenement): int
    {
        $start = $evenement->getDateDebut();
        $end = $evenement->getDateFin();
        if (!$start || !$end || $end <= $start) {
            return 120;
        }

        $seconds = $end->getTimestamp() - $start->getTimestamp();
        $minutes = (int) floor($seconds / 60);
        return max(45, min(600, $minutes));
    }

    private function extractPhasesFromDescription(string $description): array
    {
        $description = trim($description);
        if ($description === '') {
            return [];
        }

        $lines = preg_split('/\R+/', $description) ?: [];
        $phases = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $isBullet = preg_match('/^[-*•]\s+/u', $line) === 1 || preg_match('/^\d+[\).\-\s]+/u', $line) === 1;
            if (!$isBullet) {
                continue;
            }

            $clean = preg_replace('/^[-*•]\s+/u', '', $line) ?? $line;
            $clean = preg_replace('/^\d+[\).\-\s]+/u', '', $clean) ?? $clean;
            $clean = trim($clean);
            if ($clean === '') {
                continue;
            }

            $phases[] = ['title' => $clean, 'icon' => $this->iconForTitle($clean), 'intensity' => $this->intensityForTitle($clean)];
        }

        return $phases;
    }

    private function defaultPhasesByType(string $type): array
    {
        $type = mb_strtolower(trim($type));
        return match ($type) {
            'atelier' => [
                ['title' => 'Accueil et cadrage', 'icon' => 'fa-door-open', 'intensity' => 2],
                ['title' => 'Demonstration guidee', 'icon' => 'fa-chalkboard-teacher', 'intensity' => 3],
                ['title' => 'Travail en sous-groupes', 'icon' => 'fa-users-cog', 'intensity' => 4],
                ['title' => 'Restitution et feedback', 'icon' => 'fa-comments', 'intensity' => 3],
                ['title' => 'Synthese finale', 'icon' => 'fa-flag-checkered', 'intensity' => 2],
            ],
            'webinaire' => [
                ['title' => 'Ouverture digitale', 'icon' => 'fa-play-circle', 'intensity' => 2],
                ['title' => 'Intervention principale', 'icon' => 'fa-microphone', 'intensity' => 4],
                ['title' => 'Cas pratiques', 'icon' => 'fa-laptop-medical', 'intensity' => 3],
                ['title' => 'Q&A live', 'icon' => 'fa-question-circle', 'intensity' => 3],
                ['title' => 'Cloture', 'icon' => 'fa-check-circle', 'intensity' => 2],
            ],
            default => [
                ['title' => 'Ouverture', 'icon' => 'fa-flag', 'intensity' => 2],
                ['title' => 'Session 1', 'icon' => 'fa-microphone', 'intensity' => 4],
                ['title' => 'Pause networking', 'icon' => 'fa-coffee', 'intensity' => 1],
                ['title' => 'Session 2', 'icon' => 'fa-chalkboard', 'intensity' => 4],
                ['title' => 'Invite special', 'icon' => 'fa-star', 'intensity' => 3],
                ['title' => 'Cloture', 'icon' => 'fa-check-circle', 'intensity' => 2],
            ],
        };
    }

    private function allocateTiming(array $phases, int $totalMinutes, ?\DateTimeInterface $start): array
    {
        $weights = array_map(static fn (array $p): int => max(1, (int) ($p['intensity'] ?? 3)), $phases);
        $weightSum = array_sum($weights);
        $result = [];
        $cursor = $start ? \DateTimeImmutable::createFromInterface($start) : null;

        foreach ($phases as $index => $phase) {
            $duration = (int) round(($weights[$index] / max(1, $weightSum)) * $totalMinutes);
            $duration = max(10, min(90, $duration));

            $timeLabel = null;
            if ($cursor instanceof \DateTimeImmutable) {
                $phaseEnd = $cursor->modify(sprintf('+%d minutes', $duration));
                $timeLabel = $cursor->format('H:i') . ' - ' . $phaseEnd->format('H:i');
                $cursor = $phaseEnd;
            }

            $result[] = [
                'title' => (string) $phase['title'],
                'icon' => (string) ($phase['icon'] ?? 'fa-circle'),
                'duration' => $duration,
                'time_label' => $timeLabel ?: sprintf('~ %d min', $duration),
                'intensity' => (int) ($phase['intensity'] ?? 3),
            ];
        }

        return $result;
    }

    private function themeForEvent(Evenement $evenement): array
    {
        $key = mb_strtolower((string) $evenement->getType());
        return match ($key) {
            'atelier' => ['bg' => 'linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%)', 'chip' => '#dbeafe'],
            'webinaire' => ['bg' => 'linear-gradient(135deg, #14b8a6 0%, #0f766e 100%)', 'chip' => '#ccfbf1'],
            'depistage' => ['bg' => 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)', 'chip' => '#ffedd5'],
            default => ['bg' => 'linear-gradient(135deg, #6366f1 0%, #7c3aed 100%)', 'chip' => '#ede9fe'],
        };
    }

    private function buildSubtitle(Evenement $evenement, int $durationMinutes): string
    {
        $mode = (string) $evenement->getMode();
        $modeText = match ($mode) {
            'en_ligne' => 'Experience digitale',
            'hybride' => 'Experience hybride',
            default => 'Experience presentielle',
        };

        return sprintf('Programme officiel | %s | %d min', $modeText, $durationMinutes);
    }

    private function buildHighlights(Evenement $evenement, int $durationMinutes, int $phases): array
    {
        $parts = [];
        $parts[] = sprintf('%d phases structurees', $phases);
        $parts[] = sprintf('Duree totale %d min', $durationMinutes);
        $parts[] = $evenement->getMode() === 'hybride' ? 'Flux pense pour presentiel + en ligne' : 'Flux pense pour une execution fluide';
        return $parts;
    }

    private function iconForTitle(string $title): string
    {
        $t = mb_strtolower($title);
        if (str_contains($t, 'pause') || str_contains($t, 'coffee')) return 'fa-coffee';
        if (str_contains($t, 'atelier') || str_contains($t, 'workshop')) return 'fa-tools';
        if (str_contains($t, 'q&a') || str_contains($t, 'question')) return 'fa-question-circle';
        if (str_contains($t, 'invit') || str_contains($t, 'guest')) return 'fa-star';
        if (str_contains($t, 'ouverture') || str_contains($t, 'accueil')) return 'fa-door-open';
        if (str_contains($t, 'cloture') || str_contains($t, 'closing')) return 'fa-check-circle';
        return 'fa-microphone';
    }

    private function intensityForTitle(string $title): int
    {
        $t = mb_strtolower($title);
        if (str_contains($t, 'pause')) return 1;
        if (str_contains($t, 'q&a') || str_contains($t, 'question')) return 3;
        if (str_contains($t, 'atelier') || str_contains($t, 'workshop')) return 4;
        return 3;
    }
}
