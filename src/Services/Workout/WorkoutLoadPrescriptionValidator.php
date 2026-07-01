<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Movement;

final class WorkoutLoadPrescriptionValidator
{
    public function containsStrictToesToBar(string $flow): bool
    {
        return preg_match('/(?:^| )strict (?:toes to bars?|t2bs?|ttbs?)(?: |$)/', $this->normalizeMovementSearchText($this->flowWithoutScalingOptions($flow))) === 1;
    }

    /**
     * @param Movement[] $selectedMovements
     *
     * @return list<Movement>
     */
    public function movementsMissingMainFlowLoadPrescription(string $flow, array $selectedMovements): array
    {
        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $missingMovements = [];

        foreach ($selectedMovements as $movement) {
            if (!$this->mainFlowHasRequiredLoadPrescriptionForMovement($mainFlow, $selectedMovements, $movement)) {
                $missingMovements[] = $movement;
            }
        }

        return $missingMovements;
    }

    /**
     * @param Movement[] $selectedMovements
     */
    private function mainFlowHasRequiredLoadPrescriptionForMovement(string $mainFlow, array $selectedMovements, Movement $movement): bool
    {
        $lines = preg_split('/\R+/', $mainFlow) ?: [$mainFlow];
        foreach ($lines as $line) {
            $normalizedLine = $this->normalizedFlowWithoutOtherMovementNames($line, $selectedMovements, $movement);
            if (!$this->normalizedFlowContainsMovement($normalizedLine, $movement)) {
                continue;
            }

            $movementSegment = $this->lineSegmentForMovement($line, $selectedMovements, $movement);
            if (!$this->movementLineRequiresLoadPrescription($movement, $movementSegment)) {
                continue;
            }

            if (!$this->textHasLoadPrescription($movementSegment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Movement[] $selectedMovements
     */
    private function lineSegmentForMovement(string $line, array $selectedMovements, Movement $movement): string
    {
        $segments = preg_split('/(?<=\S)\s*\+\s*(?=\d)|\s+then\s+|;+|(?<!\d),(?!\d)(?=\s*\d+\s+[a-z])|\s+and\s+(?=\d+\s+[a-z])/i', $line) ?: [$line];
        foreach ($segments as $segment) {
            $normalizedSegment = $this->normalizedFlowWithoutOtherMovementNames($segment, $selectedMovements, $movement);
            if ($this->normalizedFlowContainsMovement($normalizedSegment, $movement)) {
                return $segment;
            }
        }

        return $line;
    }

    private function movementLineRequiresLoadPrescription(Movement $movement, string $line): bool
    {
        if (count($movement->getPossibleImplements()) === 0) {
            return $this->isLoadedMovementNameRequiringMainPrescription($movement);
        }

        if (!$this->hasLoadableImplement($movement)) {
            return false;
        }

        if ($this->isObstacleMovementExemptFromLoadPrescription($movement)) {
            return false;
        }

        if ($this->isPlateSupportMovementExemptFromLoadPrescription($movement)) {
            return false;
        }

        if ($this->hasOnlyLoadableImplements($movement)) {
            return true;
        }

        if ($this->isLoadedMovementNameRequiringMainPrescription($movement)) {
            return true;
        }

        return $this->lineMentionsLoadableImplement($line) || $this->lineMentionsLoadedVariant($line);
    }

    private function textHasLoadPrescription(string $text): bool
    {
        $hasConcreteLoad = preg_match('/\b\d+(?:[.,]\d+)?(?:\s*\/\s*\d+(?:[.,]\d+)?)?(?:\s*-\s*|\s*)?(?:kg|kgs|kilograms?|lb|lbs|pounds?)\b/i', $text) === 1
            || preg_match('/\b(?:@|at\s+)?\d+(?:[.,]\d+)?\s*%/', $text) === 1
            || preg_match('/\b\d+(?:[.,]\d+)?\s*%\s*(?:1\s*rm|one\s*rep\s*max)\b/i', $text) === 1
            || preg_match('/\b(?:0[.,]\d+|[1-4](?:[.,]\d+)?|5(?:[.,]0+)?)\s*x\s*(?:bodyweight|bw)\b/i', $text) === 1;

        if ($hasConcreteLoad) {
            return true;
        }

        if (preg_match('/\b(?:bodyweight|bw)\b/i', $text) === 1) {
            return false;
        }

        return preg_match('/\b(?:empty bar|moderate(?:ly)?|heavy|light|challenging|loading|load|unbroken load)\b/i', $text) === 1;
    }

    private function hasLoadableImplement(Movement $movement): bool
    {
        foreach ($movement->getPossibleImplements() as $implement) {
            if ($this->isLoadableImplementName($implement->getName())) {
                return true;
            }
        }

        return false;
    }

    private function isLoadableImplementName(string $implementName): bool
    {
        return isset([
            ImplementEnum::BARBELL->value => true,
            ImplementEnum::DUMBBELL->value => true,
            ImplementEnum::KETTLEBELL->value => true,
            ImplementEnum::MEDICINE_BALL->value => true,
            ImplementEnum::DOUBLE_KETTLEBELLS->value => true,
            ImplementEnum::DOUBLE_DUMBBELLS->value => true,
            ImplementEnum::PLATE->value => true,
            ImplementEnum::SLAM_BALL->value => true,
            ImplementEnum::SLED->value => true,
            ImplementEnum::TIRE->value => true,
            ImplementEnum::HAMMER->value => true,
            ImplementEnum::SLEDGE->value => true,
            ImplementEnum::SAND_BAG->value => true,
            ImplementEnum::HUSAFELL_BAG->value => true,
            ImplementEnum::YOKE->value => true,
            ImplementEnum::AXLE_BARBELL->value => true,
            ImplementEnum::PIG->value => true,
            ImplementEnum::WEIGHTED_VEST->value => true,
            ImplementEnum::WORM->value => true,
        ][$implementName]);
    }

    private function isLoadedMovementNameRequiringMainPrescription(Movement $movement): bool
    {
        $name = $this->normalizeMovementName($movement->getName());

        if (preg_match('/\b(?:air|pistol|alternate pistol)\s+squats?\b/', $name) === 1) {
            return false;
        }

        return preg_match('/\b(?:clean|snatch|deadlift|press|thruster|jerk|shoulder to overhead|dumbbell|kettlebell|db|kb|barbell|wall ball|farmer carry|sled)\b/', $name) === 1
            || preg_match('/\b(?:back|front|overhead|goblet|sandbag|dumbbell|kettlebell|barbell)\s+squats?\b/', $name) === 1;
    }

    private function isObstacleMovementExemptFromLoadPrescription(Movement $movement): bool
    {
        return preg_match('/\bburpees?\s+over(?:\s+facing)?\b/', $this->normalizeMovementName($movement->getName())) === 1;
    }

    private function isPlateSupportMovementExemptFromLoadPrescription(Movement $movement): bool
    {
        return preg_match('/\bdeficit\s+(?:strict\s+)?(?:handstand\s+push\s+ups?|hspu)\b/', $this->normalizeMovementName($movement->getName())) === 1;
    }

    private function hasOnlyLoadableImplements(Movement $movement): bool
    {
        foreach ($movement->getPossibleImplements() as $implement) {
            if (!$this->isLoadableImplementName($implement->getName())) {
                return false;
            }
        }

        return count($movement->getPossibleImplements()) > 0;
    }

    private function lineMentionsLoadableImplement(string $line): bool
    {
        return preg_match('/\b(?:barbell|bb|dumbbell|dumbbells|db|dbs|kettlebell|kettlebells|kb|kbs|medicine ball|med ball|wall ball|plate|slam ball|sled|tire|hammer|sledge|sandbag|sand bag|husafell|yoke|axle|pig|weighted vest|worm)\b/i', $line) === 1;
    }

    private function lineMentionsLoadedVariant(string $line): bool
    {
        return preg_match('/\b(?:weighted|loaded)\b/i', $line) === 1;
    }

    private function flowWithoutScalingOptions(string $flow): string
    {
        $parts = preg_split('/^\s*scaling(?: options)?\s*:/mi', $flow, 2);

        return is_array($parts) ? $parts[0] : $flow;
    }

    private function normalizeMovementName(?string $name): string
    {
        return trim(mb_strtolower((string) $name));
    }

    private function normalizeMovementSearchText(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($text)) ?? '');
    }

    private function normalizedFlowContainsMovement(string $normalizedFlow, Movement $movement): bool
    {
        foreach ($this->movementSearchTexts([$movement]) as $movementSearchText) {
            if (preg_match('/(?:^| )'.preg_quote($movementSearchText, '/').'s?(?: |$)/', $normalizedFlow) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Movement[] $movements
     *
     * @return list<string>
     */
    private function movementSearchTexts(array $movements): array
    {
        $searchTexts = [];
        foreach ($movements as $movement) {
            $movementName = $movement->getName();
            array_push($searchTexts, ...$this->movementSearchTextVariants($this->normalizeMovementSearchText($movementName)));

            foreach ($this->movementSearchAliases($movementName) as $alias) {
                array_push($searchTexts, ...$this->movementSearchTextVariants($this->normalizeMovementSearchText($alias)));
            }
        }

        return array_values(array_unique(array_filter($searchTexts)));
    }

    /**
     * @return list<string>
     */
    private function movementSearchTextVariants(string $searchText): array
    {
        $variants = [$searchText];

        if (str_ends_with($searchText, 'y')) {
            $variants[] = substr($searchText, 0, -1).'ies';
        }

        if (!str_ends_with($searchText, 's')) {
            $variants[] = $searchText.'s';
        }

        return $variants;
    }

    /**
     * @return list<string>
     */
    private function movementSearchAliases(string $movementName): array
    {
        return match ($this->normalizeMovementName($movementName)) {
            'assault bike' => ['Echo Bike', 'Rogue Echo Bike'],
            'bike erg' => ['BikeErg', 'Bike Erg Calories', 'Bike Erg Calorie'],
            'box jump over' => ['Box Jump-Over', 'Box Jump-Overs', 'Box Jump Overs'],
            'box step up' => ['Box Step-Up', 'Box Step-Ups', 'Box Step Ups'],
            'burpee box jump over' => ['BBJO', 'BBJOs', 'Burpee Box Jump-Over', 'Burpee Box Jump-Overs', 'Burpee Box Jump Overs'],
            'chest to bar pull up' => ['C2B', 'C2B Pull Up', 'C2B Pull Ups', 'Chest to Bar Pull Ups', 'Chest to Bars'],
            'clean and jerk' => ['C&J', 'Clean & Jerk', 'Clean + Jerk', 'Clean Jerks'],
            'double under' => ['DU', 'DUs', 'Double Unders'],
            'farmer carry' => ['Farmer Carrys', 'Farmer Carries', 'Farmer\'s Carry', 'Farmer\'s Carries', 'Farmers Carry', 'Farmers Carries'],
            'ghd sit up' => ['GHD Sit-Up', 'GHD Sit-Ups', 'GHD Sit Ups'],
            'handstand walk' => ['HS Walk', 'HS Walks', 'HSW', 'HSWs', 'Handstand Walks'],
            'handstand push up' => ['HSPU', 'HSPUs', 'Handstand Push Ups'],
            'muscle up' => ['BMU', 'BMUs', 'Bar Muscle Up', 'Bar Muscle Ups', 'RMU', 'RMUs', 'Ring Muscle Up', 'Ring Muscle Ups'],
            'overhead squat' => ['OHS', 'Overhead Squats'],
            'pistol squat' => ['Pistol', 'Pistols', 'Pistol Squats'],
            'row' => ['Rowing', 'Rower', 'Calorie Row', 'Calorie Rows', 'Row Calories', 'Rowing for Calories'],
            'shoulder to overhead' => ['S2OH', 'STOH', 'Shoulder-to-Overhead', 'Shoulder-to-Overheads', 'Shoulder Overhead'],
            'sled drag' => ['Sled Drags', 'Sled-Drag', 'Sled-Drags'],
            'sled pull' => ['Sled Pulls', 'Sled-Pull', 'Sled-Pulls'],
            'sled push' => ['Sled Pushes', 'Sled-Push', 'Sled-Pushes'],
            'ski erg' => ['Ski', 'SkiErg', 'Ski Erg Calories', 'Ski Calories', 'Skiing'],
            'strict handstand push up' => ['Strict HSPU', 'Strict HSPUs', 'SHSPU', 'SHSPUs', 'Strict Handstand Push Ups'],
            'toes to bar' => ['T2B', 'TTB', 'Toes to Bars'],
            'wall ball shot' => ['Wall Ball', 'Wall Balls', 'Wall Ball Shots'],
            'wall walk' => ['Wall Walks'],
            default => [],
        };
    }

    /**
     * @param Movement[] $movements
     */
    private function normalizedFlowWithoutOtherMovementNames(string $flow, array $movements, Movement $movementToKeep): string
    {
        $movementToKeepName = $this->normalizeMovementName($movementToKeep->getName());
        $movementsToRemove = array_values(array_filter(
            $movements,
            fn (Movement $movement): bool => $this->normalizeMovementName($movement->getName()) !== $movementToKeepName
        ));
        $movementToKeepSearchTexts = $this->movementSearchTexts([$movementToKeep]);

        return $this->normalizedFlowWithMovementsRemoved($flow, $movementsToRemove, $movementToKeepSearchTexts);
    }

    /**
     * @param Movement[]   $movements
     * @param list<string> $protectedSearchTexts
     */
    private function normalizedFlowWithMovementsRemoved(string $flow, array $movements, array $protectedSearchTexts = []): string
    {
        $normalizedFlow = $this->normalizeMovementSearchText($flow);
        $movementSearchTexts = array_filter(
            $this->movementSearchTexts($movements),
            static fn (string $movementSearchText): bool => !array_any(
                $protectedSearchTexts,
                static fn (string $protectedSearchText): bool => $movementSearchText !== $protectedSearchText
                    && str_contains($protectedSearchText, $movementSearchText)
            )
        );
        usort($movementSearchTexts, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return str_replace($movementSearchTexts, '', $normalizedFlow);
    }
}
