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

        $loads = $this->loads($text);

        return new InferredWorkoutPrescription(
            $divisionHints,
            $this->levelHints($text),
            $this->movementNames($workout),
            $this->implementNames($workout),
            $loads,
            $this->loadCandidates($loads),
        );
    }

    /**
     * @param list<WorkoutLoadMention> $loads
     *
     * @return list<WorkoutLoadCandidate>
     */
    private function loadCandidates(array $loads): array
    {
        $candidates = [];
        $used = [];

        foreach ($loads as $index => $load) {
            if (isset($used[$index])) {
                continue;
            }

            $conversionIndex = $this->matchingConversionLoadIndex($load, $loads, $used, $index);
            if ($conversionIndex !== null) {
                $candidates[] = new WorkoutLoadCandidate(
                    'conversion',
                    $this->candidateEquipmentHint($load, $loads[$conversionIndex]),
                    [$load, $loads[$conversionIndex]],
                );
                $used[$index] = true;
                $used[$conversionIndex] = true;
                continue;
            }

            $candidates[] = new WorkoutLoadCandidate(
                count($load->values) > 1 ? 'paired_load' : 'single_load',
                $load->equipmentHint,
                [$load],
            );
            $used[$index] = true;
        }

        return $candidates;
    }

    /**
     * @param list<WorkoutLoadMention> $loads
     * @param array<int, true>         $used
     */
    private function matchingConversionLoadIndex(
        WorkoutLoadMention $load,
        array $loads,
        array $used,
        int $currentIndex,
    ): ?int {
        foreach ($loads as $candidateIndex => $candidate) {
            if ($candidateIndex === $currentIndex || isset($used[$candidateIndex])) {
                continue;
            }
            if (!$this->sameOrUnknownEquipment($load, $candidate)) {
                continue;
            }
            if ($load->unit === $candidate->unit || count($load->values) !== count($candidate->values)) {
                continue;
            }
            if ($this->areLoadValuesConversions($load, $candidate)) {
                return $candidateIndex;
            }
        }

        return null;
    }

    private function areLoadValuesConversions(WorkoutLoadMention $left, WorkoutLoadMention $right): bool
    {
        $lbValues = $left->unit === 'lb' ? $left->values : $right->values;
        $kgValues = $left->unit === 'kg' ? $left->values : $right->values;

        foreach ($lbValues as $index => $lbValue) {
            $expectedKg = $lbValue * 0.45359237;
            $actualKg = $kgValues[$index];
            $tolerance = max(1.0, $expectedKg * 0.025);

            if (abs($actualKg - $expectedKg) > $tolerance) {
                return false;
            }
        }

        return true;
    }

    private function candidateEquipmentHint(WorkoutLoadMention $left, WorkoutLoadMention $right): string
    {
        if ($left->equipmentHint !== 'unknown') {
            return $left->equipmentHint;
        }

        return $right->equipmentHint;
    }

    private function sameOrUnknownEquipment(WorkoutLoadMention $left, WorkoutLoadMention $right): bool
    {
        return $left->equipmentHint === $right->equipmentHint
            || $left->equipmentHint === 'unknown'
            || $right->equipmentHint === 'unknown';
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
                    (int) $match[0][1],
                    $this->nearText($text, (int) $match[0][1], strlen($raw)),
                    $this->positionLabel($text, (int) $match[0][1], strlen($raw)),
                    $this->audienceHint($text, (int) $match[0][1], strlen($raw)),
                    $this->movementHint($text, (int) $match[0][1], strlen($raw)),
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
                    $offset,
                    $this->nearText($text, $offset, strlen($raw)),
                    $this->positionLabel($text, $offset, strlen($raw)),
                    $this->audienceHint($text, $offset, strlen($raw)),
                    $this->movementHint($text, $offset, strlen($raw)),
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
        $key = strtolower($load->raw.'|'.$load->equipmentHint.'|'.$load->offset);
        if (isset($seen[$key])) {
            return;
        }

        $loads[] = $load;
        $seen[$key] = true;
    }

    private function isPartOfPairedLoad(string $text, int $offset, int $length): bool
    {
        $before = substr($text, max(0, $offset - 16), 16);

        return str_contains($before, '/') && preg_match('/(?:kg|kgs|lb|lbs)\s*\/\s*$/i', $before) !== 1;
    }

    private function equipmentHint(string $text, int $offset, int $length): string
    {
        $segment = $this->divisionSegment($text, $offset);
        $explicitHint = $segment === null
            ? $this->explicitEquipmentHint($text, $offset, $length)
            : $this->explicitEquipmentHint($segment['text'], $segment['offset'], $length);
        if ($explicitHint === null && $segment !== null) {
            $explicitHint = $this->explicitEquipmentHint($text, $offset, $length);
        }
        if ($explicitHint !== null) {
            return $explicitHint;
        }

        $movementHint = $this->movementHint($text, $offset, $length);
        if ($movementHint === 'Wall Ball Shot') {
            return 'medicine ball';
        }
        $movementContext = substr($text, max(0, $offset - 140), $length + 180);
        if ($movementHint === 'Snatch' && preg_match('/\bdumbbells?\b/i', $movementContext) === 1) {
            return 'dumbbell';
        }

        $windowStart = max(0, $offset - 48);
        $window = strtolower(substr($text, $windowStart, $length + 96));
        $loadCenter = $offset - $windowStart + (int) floor($length / 2);

        $hints = [
            'barbell' => ['barbell', 'clean', 'snatch', 'deadlift', 'thruster', 'squat', 'jerk'],
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

        if ($closestHint !== null) {
            return $closestHint;
        }

        return match ($movementHint) {
            'Deadlift', 'Clean', 'Clean and Jerk', 'Front Squat', 'Overhead Squat', 'Snatch', 'Thruster' => 'barbell',
            'Farmer Carry' => 'kettlebell',
            'Sled Push', 'Sled Pull' => 'sled',
            'Walking Lunge' => 'sandbag',
            default => 'unknown',
        };
    }

    private function explicitEquipmentHint(string $text, int $offset, int $length): ?string
    {
        $afterClause = $this->afterLoadClause($text, $offset);
        $afterHint = $this->equipmentHintInText($afterClause);
        if ($afterHint !== null) {
            return $afterHint;
        }

        $clause = $this->loadClause($text, $offset);
        $clauseHint = $this->equipmentHintInText($clause);
        if ($clauseHint !== null) {
            return $clauseHint;
        }

        $beforeHint = $this->equipmentHintInText($this->beforeLoadClause($text, $offset));
        if ($beforeHint !== null) {
            return $beforeHint;
        }

        return $this->closestPatternLabel($text, $offset, $length, [
            'medicine ball' => '/\b(?:medicine ball|med ball|wall[- ]ball|ball)\b/i',
            'dumbbell' => '/\b(?:dumbbells?|dbs?)\b/i',
            'kettlebell' => '/\b(?:kettlebells?|kbs?)\b/i',
            'barbell' => '/\bbarbell\b/i',
            'sandbag' => '/\bsand ?bag\b/i',
            'sled' => '/\bsled\b/i',
            'vest' => '/\b(?:vest|ruck)\b/i',
        ], 72);
    }

    private function afterLoadClause(string $text, int $offset): string
    {
        $end = $this->nextSeparatorOffset($text, $offset);

        return substr($text, $offset, $end - $offset);
    }

    private function beforeLoadClause(string $text, int $offset): string
    {
        $start = max(
            $this->previousSeparatorOffset($text, $offset, ','),
            $this->previousSentenceSeparatorOffset($text, $offset),
            $this->previousSeparatorOffset($text, $offset, "\n"),
            $this->previousSeparatorOffset($text, $offset, '♀'),
            $this->previousSeparatorOffset($text, $offset, '♂'),
        );
        $start = $start < 0 ? 0 : $start + 1;

        return substr($text, $start, $offset - $start);
    }

    private function equipmentHintInText(string $text): ?string
    {
        foreach ([
            'medicine ball' => '/\b(?:medicine ball|med ball|wall[- ]ball|ball)\b/i',
            'dumbbell' => '/\b(?:dumbbells?|dbs?)\b/i',
            'kettlebell' => '/\b(?:kettlebells?|kbs?)\b/i',
            'barbell' => '/\bbarbell\b/i',
            'sandbag' => '/\bsand ?bag\b/i',
            'sled' => '/\bsled\b/i',
            'vest' => '/\b(?:vest|ruck)\b/i',
        ] as $hint => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return $hint;
            }
        }

        return null;
    }

    private function loadClause(string $text, int $offset): string
    {
        $start = max(
            $this->previousSeparatorOffset($text, $offset, ','),
            $this->previousSeparatorOffset($text, $offset, '.'),
            $this->previousSeparatorOffset($text, $offset, "\n"),
            $this->previousSeparatorOffset($text, $offset, '♀'),
            $this->previousSeparatorOffset($text, $offset, '♂'),
        );
        $start = $start < 0 ? 0 : $start + 1;

        return substr($text, $start, $this->nextSeparatorOffset($text, $offset) - $start);
    }

    private function nextSeparatorOffset(string $text, int $offset): int
    {
        $ends = array_filter(
            [
                strpos($text, ',', $offset),
                $this->nextSentenceSeparatorOffset($text, $offset),
                strpos($text, "\n", $offset),
                strpos($text, '♀', $offset),
                strpos($text, '♂', $offset),
            ],
            static fn (int|false $position): bool => $position !== false,
        );

        return $ends === [] ? strlen($text) : min($ends);
    }

    private function previousSeparatorOffset(string $text, int $offset, string $separator): int
    {
        $position = strrpos(substr($text, 0, $offset), $separator);

        return $position === false ? -1 : $position;
    }

    private function nextSentenceSeparatorOffset(string $text, int $offset): int|false
    {
        $position = strpos($text, '.', $offset);
        while ($position !== false && $this->isDecimalPoint($text, $position)) {
            $position = strpos($text, '.', $position + 1);
        }

        return $position;
    }

    private function previousSentenceSeparatorOffset(string $text, int $offset): int
    {
        $position = strrpos(substr($text, 0, $offset), '.');
        while ($position !== false && $this->isDecimalPoint($text, $position)) {
            $position = strrpos(substr($text, 0, $position), '.');
        }

        return $position === false ? -1 : $position;
    }

    private function isDecimalPoint(string $text, int $position): bool
    {
        return isset($text[$position - 1], $text[$position + 1])
            && ctype_digit($text[$position - 1])
            && ctype_digit($text[$position + 1]);
    }

    private function nearText(string $text, int $offset, int $length): string
    {
        $windowStart = max(0, $offset - 90);
        $window = substr($text, $windowStart, $length + 180);

        return trim((string) preg_replace('/\s+/', ' ', $window));
    }

    private function positionLabel(string $text, int $offset, int $length): ?string
    {
        $weightPosition = $this->closestPatternMatch($text, $offset, $length, '/\b(?:weight|load)\s*(?P<number>\d+)\b/i', 120);
        if ($weightPosition !== null && isset($weightPosition['number'])) {
            return 'weight_'.$weightPosition['number'];
        }

        return $this->closestPatternLabel($text, $offset, $length, [
            'heaviest' => '/\bheaviest\b/i',
            'lightest' => '/\blightest\b/i',
        ], 120);
    }

    private function audienceHint(string $text, int $offset, int $length): ?string
    {
        $segment = $this->divisionSegment($text, $offset);
        if ($segment !== null) {
            return $segment['audience'];
        }

        return $this->closestPatternLabel($text, $offset, $length, [
            'ff' => '/\bFF\b|\(FF\)/',
            'mm' => '/\bMM\b|\(MM\)/',
            'mixed' => '/\b(?:mixed|MF|FM)\b/',
            'women' => '/♀|\b(?:women|woman|female|girls?)\b/i',
            'men' => '/♂|\b(?:men|man|male|boys?)\b/i',
            'team_pair' => '/\b(?:team|pairs?|partner|teammates?|synchronized)\b/i',
        ], 100);
    }

    private function movementHint(string $text, int $offset, int $length): ?string
    {
        $nearText = $this->nearText($text, $offset, $length);
        $explicitEquipmentHint = $this->explicitEquipmentHint($text, $offset, $length);
        if (
            ($explicitEquipmentHint === 'medicine ball' || preg_match('/\bball\b/i', $nearText) === 1)
            && preg_match('/\bwall[- ]ball(?: shots?)?\b/i', $nearText) === 1
        ) {
            return 'Wall Ball Shot';
        }

        if (
            $explicitEquipmentHint === 'medicine ball'
            && $this->looksLikeWallBallLoadContext($text, $offset, $length)
        ) {
            return 'Wall Ball Shot';
        }

        if (
            $explicitEquipmentHint === 'dumbbell'
            && preg_match('/\bsnatch(?:es)?\b/i', $nearText) === 1
        ) {
            return 'Snatch';
        }

        return $this->closestPatternLabel($text, $offset, $length, [
            'Clean and Jerk' => '/\bclean(?:s)? and jerk(?:s)?\b/i',
            'Hang Power Clean' => '/\bhang power clean(?:s)?\b/i',
            'Squat Clean' => '/\bsquat clean(?:s)?\b/i',
            'Front Squat' => '/\bfront squat(?:s)?\b/i',
            'Overhead Squat' => '/\boverhead squat(?:s)?\b/i',
            'Deadlift' => '/\bdeadlift(?:s)?\b/i',
            'Thruster' => '/\bthruster(?:s)?\b/i',
            'Snatch' => '/\bsnatch(?:es)?\b/i',
            'Wall Ball Shot' => '/\bwall[- ]ball(?: shots?)?\b/i',
            'Farmer Carry' => '/\bfarmer(?:\'s)? carr(?:y|ies)\b/i',
            'Sled Push' => '/\bsled push\b/i',
            'Sled Pull' => '/\bsled pull\b/i',
            'Walking Lunge' => '/\b(?:walking )?lunge(?:s)?\b/i',
            'Box Jump' => '/\bbox jump(?:s)?\b/i',
            'Handstand Push Up' => '/\bhandstand push[- ]ups?\b/i',
            'Clean' => '/\bclean(?:s)?\b/i',
        ], 120);
    }

    private function looksLikeWallBallLoadContext(string $text, int $offset, int $length): bool
    {
        $loadContext = $this->loadClause($text, $offset).' '.$this->afterLoadClause($text, $offset);
        if (preg_match('/\b(?:medicine ball|med ball|wall[- ]ball|ball)\b/i', $loadContext) !== 1) {
            return false;
        }

        if (preg_match('/\b(?:target|wall[- ]ball)\b/i', $this->nearText($text, $offset, $length)) === 1) {
            return true;
        }

        return preg_match('/\bwall[- ]ball(?: shots?)?\b/i', substr($text, max(0, $offset - 220), $length + 260)) === 1;
    }

    /**
     * @param array<string, string> $patterns
     */
    private function closestPatternLabel(string $text, int $offset, int $length, array $patterns, int $radius): ?string
    {
        $windowStart = max(0, $offset - $radius);
        $window = substr($text, $windowStart, $length + ($radius * 2));
        $loadCenter = $offset - $windowStart + (int) floor($length / 2);
        $closestLabel = null;
        $closestDistance = PHP_INT_MAX;

        foreach ($patterns as $label => $pattern) {
            if (preg_match_all($pattern, $window, $matches, PREG_OFFSET_CAPTURE) < 1) {
                continue;
            }

            foreach ($matches[0] as $match) {
                $position = (int) $match[1];
                $distance = abs(($position + (int) floor(strlen($match[0]) / 2)) - $loadCenter);
                if ($distance < $closestDistance) {
                    $closestLabel = $label;
                    $closestDistance = $distance;
                }
            }
        }

        return $closestLabel;
    }

    /**
     * @return array<string, string>|null
     */
    private function closestPatternMatch(string $text, int $offset, int $length, string $pattern, int $radius): ?array
    {
        $windowStart = max(0, $offset - $radius);
        $window = substr($text, $windowStart, $length + ($radius * 2));
        $loadCenter = $offset - $windowStart + (int) floor($length / 2);
        $closestMatch = null;
        $closestDistance = PHP_INT_MAX;

        if (preg_match_all($pattern, $window, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) < 1) {
            return null;
        }

        foreach ($matches as $match) {
            $position = (int) $match[0][1];
            $distance = abs(($position + (int) floor(strlen($match[0][0]) / 2)) - $loadCenter);
            if ($distance >= $closestDistance) {
                continue;
            }

            $closestDistance = $distance;
            $closestMatch = [];
            foreach ($match as $key => $value) {
                if (is_string($key)) {
                    $closestMatch[$key] = $value[0];
                }
            }
        }

        return $closestMatch;
    }

    /**
     * @return array{text: string, offset: int, audience: 'women'|'men'}|null
     */
    private function divisionSegment(string $text, int $offset): ?array
    {
        $before = substr($text, 0, $offset + 1);
        $womenPosition = strrpos($before, '♀');
        $menPosition = strrpos($before, '♂');
        if ($womenPosition === false && $menPosition === false) {
            return null;
        }

        $start = max($womenPosition === false ? -1 : $womenPosition, $menPosition === false ? -1 : $menPosition);
        $audience = $womenPosition === $start ? 'women' : 'men';
        $nextOppositePosition = strpos($text, $audience === 'women' ? '♂' : '♀', $start + 1);
        $end = $nextOppositePosition === false ? strlen($text) : $nextOppositePosition;
        $segmentText = substr($text, $start, $end - $start);

        return [
            'text' => $segmentText,
            'offset' => $offset - $start,
            'audience' => $audience,
        ];
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
