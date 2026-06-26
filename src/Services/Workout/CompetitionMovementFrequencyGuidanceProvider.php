<?php

namespace App\Services\Workout;

use App\Entity\Workout\Movement;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

readonly class CompetitionMovementFrequencyGuidanceProvider
{
    private const MOVEMENT_FREQUENCY_BANDS = [
        'very frequent' => ['Toes to Bar', 'Double Under', 'Front Squat', 'Pull Up', 'Wall Ball Shot', 'Muscle Up', 'Clean and Jerk'],
        'regular' => ['Chest to Bar Pull Up', 'Shoulder To Overhead', 'Box Jump Over', 'Hang Clean', 'Squat Clean', 'Rope Climb', 'Power Clean', 'Wall Walk', 'Ski Erg', 'Shuttle Run', 'Overhead Squat', 'Handstand Walk', 'Walking Lunge', 'Burpee Box Jump Over', 'Handstand Push Up', 'Assault Bike'],
        'occasional' => ['Box Jump', 'Sit Up', 'Bike Erg', 'Hang Power Clean', 'Burpee Over', 'Push Press', 'Push Up', 'Power Snatch', 'Bench Press', 'Sled Push', 'Back Squat', 'Air Squat', 'Squat Snatch', 'Single Under', 'Hang Squat Clean', 'Push Jerk', 'Sled Pull', 'Burpee Broad Jump', 'Split Jerk', 'Box Step Up'],
    ];

    private const FREQUENT_MOVEMENT_PAIRS = [
        ['Chest to Bar Pull Up', 'Muscle Up'],
        ['Muscle Up', 'Toes to Bar'],
        ['Front Squat', 'Shoulder To Overhead'],
        ['Chest to Bar Pull Up', 'Toes to Bar'],
        ['Double Under', 'Toes to Bar'],
        ['Front Squat', 'Hang Clean'],
        ['Muscle Up', 'Pull Up'],
        ['Front Squat', 'Power Clean'],
        ['Toes to Bar', 'Wall Ball Shot'],
        ['Double Under', 'Muscle Up'],
        ['Pull Up', 'Toes to Bar'],
        ['Front Squat', 'Squat Clean'],
        ['Chest to Bar Pull Up', 'Thruster'],
        ['Thruster', 'Wall Ball Shot'],
    ];

    private const RECENT_GENERATED_TEMPLATE_MOVEMENTS = [
        'Power Clean',
        'Wall Ball Shot',
        'Box Jump Over',
        'Row',
        'Chest to Bar Pull Up',
    ];

    private const RECENT_GENERATED_OVERUSED_ANCHORS = [
        'Power Clean',
        'Chest to Bar Pull Up',
        'Wall Ball Shot',
        'Thruster',
    ];

    private const REJECTED_MOVEMENT_CLUSTERS = [
        ['Chest to Bar Pull Up', 'Thruster', 'Row'],
        ['Chest to Bar Pull Up', 'Thruster', 'Power Clean'],
        ['Chest to Bar Pull Up', 'Thruster', 'Wall Ball Shot'],
    ];

    public function promptCandidatePoolMin(): int
    {
        return 8;
    }

    public function promptCandidatePoolMax(): int
    {
        return 16;
    }

    /**
     * @param Movement[] $allowedMovements
     */
    public function buildPromptGuidance(WorkoutGeneration $workoutGeneration, array $allowedMovements): string
    {
        $allowedMovementNames = $this->allowedMovementNames($allowedMovements);
        $availableFrequencyBands = $this->availableFrequencyBands($allowedMovements);
        $availablePairs = $this->availableFrequentMovementPairs($allowedMovements);

        if ($availableFrequencyBands === [] && $availablePairs === []) {
            return '';
        }

        $guidance = "Competition movement recurrence guidance:\n";
        $guidance .= "- Use real competition frequency as distribution guidance, not as hard rules. Do not ban common movements; rotate them across generations.\n";
        $guidance .= "- Prefer a balanced mix of very frequent, regular and occasional available movements when that still respects the stimulus, level and equipment.\n";
        $guidance .= "- Do not default to Thruster + Chest to Bar Pull Up, Wall Ball Shot + Chest to Bar Pull Up, or the same squat/pull/barbell core unless the user forced those movements or the stimulus clearly needs them.\n";
        $guidance .= $this->recentGeneratedCompetitionTemplateGuidance($workoutGeneration, $allowedMovementNames);

        foreach ($availableFrequencyBands as $band => $availableMovementNames) {
            $guidance .= sprintf('- %s available movements: %s.', $band, implode(', ', $availableMovementNames))."\n";
        }

        if ($availablePairs !== []) {
            $guidance .= sprintf("- Frequent pairs available in this pool: %s. Avoid repeating these pairings by default; use them only when they are the best fit for the requested stimulus or mandatory movements.\n", implode('; ', $availablePairs));
        }

        return $guidance;
    }

    public function isOverusedRotationAnchor(Movement $movement): bool
    {
        $normalizedMovementName = $this->normalizeMovementName($movement->getName());
        foreach (self::RECENT_GENERATED_OVERUSED_ANCHORS as $anchorMovementName) {
            if ($normalizedMovementName === $this->normalizeMovementName($anchorMovementName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Movement[] $selectedMovements
     */
    public function assertNoRejectedMovementCluster(array $selectedMovements): void
    {
        $selectedMovementNames = [];
        foreach ($selectedMovements as $movement) {
            $selectedMovementNames[$this->normalizeMovementName($movement->getName())] = $movement->getName();
        }

        foreach (self::REJECTED_MOVEMENT_CLUSTERS as $cluster) {
            $matchedCluster = [];
            foreach ($cluster as $movementName) {
                $normalizedMovementName = $this->normalizeMovementName($movementName);
                if (!isset($selectedMovementNames[$normalizedMovementName])) {
                    continue 2;
                }

                $matchedCluster[] = $selectedMovementNames[$normalizedMovementName];
            }

            throw new \RuntimeException(sprintf('Generated Competition workout selected an overused movement cluster (%s) without mandatory movements.', implode(' + ', $matchedCluster)));
        }
    }

    /**
     * @param Movement[] $allowedMovements
     *
     * @return array<string, list<string>>
     */
    public function availableFrequencyBands(array $allowedMovements): array
    {
        $allowedMovementNames = $this->allowedMovementNames($allowedMovements);
        $availableFrequencyBands = [];

        foreach (self::MOVEMENT_FREQUENCY_BANDS as $band => $movementNames) {
            $availableMovementNames = $this->availableGuidanceMovementNames($movementNames, $allowedMovementNames);

            if ($availableMovementNames === []) {
                continue;
            }

            $availableFrequencyBands[$band] = $availableMovementNames;
        }

        return $availableFrequencyBands;
    }

    /**
     * @param Movement[] $allowedMovements
     *
     * @return list<string>
     */
    public function availableFrequentMovementPairs(array $allowedMovements): array
    {
        $allowedMovementNames = $this->allowedMovementNames($allowedMovements);
        $availablePairs = [];

        foreach (self::FREQUENT_MOVEMENT_PAIRS as [$movementA, $movementB]) {
            $normalizedMovementA = $this->normalizeMovementName($movementA);
            $normalizedMovementB = $this->normalizeMovementName($movementB);

            if (!isset($allowedMovementNames[$normalizedMovementA], $allowedMovementNames[$normalizedMovementB])) {
                continue;
            }

            $availablePairs[] = sprintf('%s + %s', $allowedMovementNames[$normalizedMovementA], $allowedMovementNames[$normalizedMovementB]);
        }

        return $availablePairs;
    }

    /**
     * @param Movement[] $allowedMovements
     *
     * @return list<string>
     */
    public function availableRecentGeneratedTemplateMovements(array $allowedMovements): array
    {
        return $this->availableGuidanceMovementNames(
            self::RECENT_GENERATED_TEMPLATE_MOVEMENTS,
            $this->allowedMovementNames($allowedMovements),
        );
    }

    /**
     * @param Movement[] $allowedMovements
     *
     * @return list<string>
     */
    public function availableOverusedRotationAnchors(array $allowedMovements): array
    {
        return $this->availableGuidanceMovementNames(
            self::RECENT_GENERATED_OVERUSED_ANCHORS,
            $this->allowedMovementNames($allowedMovements),
        );
    }

    /**
     * @param array<string, string> $allowedMovementNames
     */
    private function recentGeneratedCompetitionTemplateGuidance(WorkoutGeneration $workoutGeneration, array $allowedMovementNames): string
    {
        $availableTemplateMovements = $this->availableGuidanceMovementNames(self::RECENT_GENERATED_TEMPLATE_MOVEMENTS, $allowedMovementNames);
        $availableOverusedAnchors = $this->availableGuidanceMovementNames(self::RECENT_GENERATED_OVERUSED_ANCHORS, $allowedMovementNames);

        if (count($availableTemplateMovements) < 3 && count($availableOverusedAnchors) < 2) {
            return '';
        }

        $guidance = '';

        if (count($availableTemplateMovements) >= 3) {
            $guidance .= sprintf(
                "- Recent generated competition workouts are overusing this cluster: %s. These movements remain allowed, but for this generation choose at most two from that cluster unless one of them is mandatory; fill the remaining slots with other coherent movements from the allowed pool.\n",
                implode(', ', $availableTemplateMovements),
            );
        }

        if (count($availableOverusedAnchors) >= 2 && count($workoutGeneration->getMandatoryMovements()) === 0) {
            $guidance .= sprintf(
                "- Strong rotation rule for this generation: no movement is mandatory, so choose at most one from these currently overused generated anchors: %s. This is a per-generation rotation rule, not a permanent ban; common competition movements should reappear in other generations, just not together by default here.\n",
                implode(', ', $availableOverusedAnchors),
            );
        }

        if (
            isset(
                $allowedMovementNames[$this->normalizeMovementName('Power Clean')],
                $allowedMovementNames[$this->normalizeMovementName('Chest to Bar Pull Up')]
            )
            && count($workoutGeneration->getMandatoryMovements()) === 0
        ) {
            $guidance .= "- Current audit shows Power Clean + Chest to Bar Pull Up is recurring too often. Do not select both together unless the user explicitly forced both movements.\n";
        }

        return $guidance;
    }

    /**
     * @param Movement[] $movements
     *
     * @return array<string, string>
     */
    private function allowedMovementNames(array $movements): array
    {
        $allowedMovementNames = [];
        foreach ($movements as $movement) {
            $allowedMovementNames[$this->normalizeMovementName($movement->getName())] = $movement->getName();
        }

        return $allowedMovementNames;
    }

    /**
     * @param list<string>          $movementNames
     * @param array<string, string> $allowedMovementNames
     *
     * @return list<string>
     */
    private function availableGuidanceMovementNames(array $movementNames, array $allowedMovementNames): array
    {
        $availableMovementNames = [];

        foreach ($movementNames as $movementName) {
            $normalizedMovementName = $this->normalizeMovementName($movementName);

            if (!isset($allowedMovementNames[$normalizedMovementName])) {
                continue;
            }

            $availableMovementNames[] = $allowedMovementNames[$normalizedMovementName];
        }

        return $availableMovementNames;
    }

    private function normalizeMovementName(string $name): string
    {
        return strtolower(trim($name));
    }
}
