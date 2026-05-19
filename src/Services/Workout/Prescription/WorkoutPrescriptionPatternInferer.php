<?php

namespace App\Services\Workout\Prescription;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;

final class WorkoutPrescriptionPatternInferer
{
    private const LOAD_PATTERN = '/(?<![a-z0-9])(?P<first>\d+(?:[\.,]\d+)?)\s*(?:\/|and|or|&)\s*(?P<second>\d+(?:[\.,]\d+)?)\s*(?P<unit>kg|kgs|kilograms?|lb|lbs|pounds?)(?![a-z])/i';
    private const SINGLE_LOAD_PATTERN = '/(?<![a-z0-9])(?:(?P<count>\d+)\s*x\s*)?(?P<value>\d+(?:[\.,]\d+)?)\s*-?\s*(?P<unit>kg|kgs|kilograms?|lb|lbs|pounds?)(?![a-z])/i';

    public function infer(Workout $workout): InferredWorkoutPrescription
    {
        $divisionHints = $this->divisionHints($workout);
        $text = implode("\n", array_filter([
            $workout->getName(),
            $workout->getFlow(),
            implode("\n", $divisionHints),
        ]));

        return new InferredWorkoutPrescription(
            $divisionHints,
            $this->levelHints($text),
            $this->movementNames($workout),
            $this->implementNames($workout),
            $this->loads($text),
        );
    }

    /**
     * @return list<WorkoutLoadMention>
     */
    private function loads(string $text): array
    {
        $loads = [];
        $seen = [];

        if (preg_match_all(self::LOAD_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $raw = $match[0][0];
                $load = new WorkoutLoadMention(
                    $raw,
                    [$this->floatValue($match['first'][0]), $this->floatValue($match['second'][0])],
                    $this->unit($match['unit'][0]),
                    $this->equipmentHint($text, (int) $match[0][1], strlen($raw)),
                );
                $this->addLoad($loads, $seen, $load);
            }
        }

        if (preg_match_all(self::SINGLE_LOAD_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $raw = $match[0][0];
                $offset = (int) $match[0][1];

                if ($this->isPartOfPairedLoad($text, $offset, strlen($raw))) {
                    continue;
                }

                $values = [$this->floatValue($match['value'][0])];
                if (isset($match['count'][0]) && $match['count'][0] !== '') {
                    $values = array_fill(0, (int) $match['count'][0], $values[0]);
                }

                $load = new WorkoutLoadMention(
                    $raw,
                    $values,
                    $this->unit($match['unit'][0]),
                    $this->equipmentHint($text, $offset, strlen($raw)),
                );
                $this->addLoad($loads, $seen, $load);
            }
        }

        return $loads;
    }

    /**
     * @param list<WorkoutLoadMention> $loads
     * @param array<string, true>      $seen
     */
    private function addLoad(array &$loads, array &$seen, WorkoutLoadMention $load): void
    {
        $key = strtolower($load->raw.'|'.$load->equipmentHint);
        if (isset($seen[$key])) {
            return;
        }

        $loads[] = $load;
        $seen[$key] = true;
    }

    private function isPartOfPairedLoad(string $text, int $offset, int $length): bool
    {
        $before = substr($text, max(0, $offset - 8), 8);
        $after = substr($text, $offset + $length, 8);

        return str_contains($before, '/') || str_contains($after, '/');
    }

    private function equipmentHint(string $text, int $offset, int $length): string
    {
        $windowStart = max(0, $offset - 48);
        $window = strtolower(substr($text, $windowStart, $length + 96));
        $loadCenter = $offset - $windowStart + (int) floor($length / 2);

        $hints = [
            'dumbbell' => ['dumbbell', 'dumbbells', 'db'],
            'kettlebell' => ['kettlebell', 'kettlebells', 'kb'],
            'barbell' => ['barbell', 'clean', 'snatch', 'deadlift', 'thruster', 'squat', 'jerk'],
            'medicine ball' => ['medicine ball', 'wall ball'],
            'sandbag' => ['sandbag'],
            'sled' => ['sled'],
            'vest' => ['vest', 'ruck'],
        ];
        $closestHint = null;
        $closestDistance = PHP_INT_MAX;

        foreach ($hints as $hint => $terms) {
            foreach ($terms as $term) {
                $position = strpos($window, $term);
                while ($position !== false) {
                    $distance = abs(($position + (int) floor(strlen($term) / 2)) - $loadCenter);
                    if ($distance < $closestDistance) {
                        $closestHint = $hint;
                        $closestDistance = $distance;
                    }
                    $position = strpos($window, $term, $position + 1);
                }
            }
        }

        return $closestHint ?? 'unknown';
    }

    /**
     * @return list<string>
     */
    private function levelHints(string $text): array
    {
        $levels = [
            'elite' => '/\belite\b/i',
            'rx' => '/\brx(?:d|\'d)?\b/i',
            'intermediate' => '/\b(?:intermediate|inter)\b/i',
            'scaled' => '/\bscaled\b/i',
            'beginner' => '/\b(?:beginner|beginners?|rookie)\b/i',
            'masters' => '/\bmasters?\b/i',
            'teen' => '/\bteens?\b/i',
        ];
        $matches = [];

        foreach ($levels as $level => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $matches[] = $level;
            }
        }

        return $matches;
    }

    /**
     * @return list<string>
     */
    private function divisionHints(Workout $workout): array
    {
        $divisions = [];
        foreach ($workout->getCompetitionContexts() as $context) {
            foreach ($context['divisions'] as $division) {
                $divisions[$division] = true;
            }
        }

        $divisionNames = array_keys($divisions);
        sort($divisionNames, SORT_NATURAL | SORT_FLAG_CASE);

        return $divisionNames;
    }

    /**
     * @return list<string>
     */
    private function movementNames(Workout $workout): array
    {
        $names = array_filter(array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray(),
        ));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($names);
    }

    /**
     * @return list<string>
     */
    private function implementNames(Workout $workout): array
    {
        $names = array_map(
            static fn (Implement $implement): string => $implement->getName(),
            $workout->getImplements()->toArray(),
        );
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($names);
    }

    private function unit(string $unit): string
    {
        $unit = strtolower($unit);

        return str_starts_with($unit, 'k') ? 'kg' : 'lb';
    }

    private function floatValue(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }
}
