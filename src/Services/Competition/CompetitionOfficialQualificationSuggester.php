<?php

namespace App\Services\Competition;

use App\Entity\Competition\Competition;

final class CompetitionOfficialQualificationSuggester
{
    public const CIRCUIT_CROSSFIT_GAMES = 'crossfit_games';
    public const STAGE_SEMIFINALS = 'semifinals';
    public const DIVISION_PATTERN_ELITE = 'elite';

    /**
     * @return list<array{circuit: string, stage: string, divisionPattern: string, notes: string}>
     */
    public function suggest(Competition $competition): array
    {
        if (!$this->looksLikeCrossFitGamesSemifinal($competition)) {
            return [];
        }

        return [[
            'circuit' => self::CIRCUIT_CROSSFIT_GAMES,
            'stage' => self::STAGE_SEMIFINALS,
            'divisionPattern' => self::DIVISION_PATTERN_ELITE,
            'notes' => 'Auto-suggested from competition name/source metadata; requires admin confirmation.',
        ]];
    }

    private function looksLikeCrossFitGamesSemifinal(Competition $competition): bool
    {
        $haystack = $this->normalize(implode(' ', array_filter([
            $competition->getName(),
            $competition->getCompetitionType(),
            $competition->getLocationLabel(),
            $competition->getSourceName(),
            $competition->getSourceUrl(),
        ])));

        if ($haystack === '') {
            return false;
        }

        if (preg_match('/\bsemi[\s-]?finals?\b/', $haystack) === 1) {
            return str_contains($haystack, 'crossfit')
                || str_contains($haystack, 'games')
                || $competition->getSeason() !== null;
        }

        foreach ($this->knownSemifinalNameFragments() as $fragment) {
            if (str_contains($haystack, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function knownSemifinalNameFragments(): array
    {
        return [
            'mad fitness festival',
            'the progrm crown series',
            'syndicate crown',
            'west coast classic',
            'west coast classics',
            'torian pro',
        ];
    }

    private function normalize(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
