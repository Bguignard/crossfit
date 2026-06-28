<?php

namespace App\Services\Workout;

/**
 * Empirical Competition guidance extracted from audits and recent generations.
 *
 * This snapshot is distribution guidance for prompts and rotation checks, not a banlist.
 * Refresh it after post-deployment audits when the observed movement mix changes.
 */
readonly class CompetitionMovementGuidanceSnapshot
{
    /**
     * @param array<string, list<string>>       $movementFrequencyBands
     * @param list<array{0: string, 1: string}> $frequentMovementPairs
     * @param list<string>                      $recentGeneratedTemplateMovements
     * @param list<string>                      $overusedRotationAnchors
     * @param list<list<string>>                $rejectedMovementClusters
     */
    public function __construct(
        private array $movementFrequencyBands,
        private array $frequentMovementPairs,
        private array $recentGeneratedTemplateMovements,
        private array $overusedRotationAnchors,
        private array $rejectedMovementClusters,
    ) {
    }

    public static function default(): self
    {
        return new self(
            [
                'very frequent' => ['Toes to Bar', 'Double Under', 'Front Squat', 'Pull Up', 'Wall Ball Shot', 'Muscle Up', 'Clean and Jerk'],
                'regular' => ['Chest to Bar Pull Up', 'Shoulder To Overhead', 'Box Jump Over', 'Hang Clean', 'Squat Clean', 'Rope Climb', 'Power Clean', 'Wall Walk', 'Ski Erg', 'Shuttle Run', 'Overhead Squat', 'Handstand Walk', 'Walking Lunge', 'Burpee Box Jump Over', 'Handstand Push Up', 'Assault Bike'],
                'occasional' => ['Box Jump', 'Sit Up', 'Bike Erg', 'Hang Power Clean', 'Burpee Over', 'Push Press', 'Push Up', 'Power Snatch', 'Bench Press', 'Sled Push', 'Back Squat', 'Air Squat', 'Squat Snatch', 'Single Under', 'Hang Squat Clean', 'Push Jerk', 'Sled Pull', 'Burpee Broad Jump', 'Split Jerk', 'Box Step Up'],
            ],
            [
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
            ],
            [
                'Power Clean',
                'Wall Ball Shot',
                'Box Jump Over',
                'Row',
                'Chest to Bar Pull Up',
            ],
            [
                'Power Clean',
                'Chest to Bar Pull Up',
                'Wall Ball Shot',
                'Thruster',
            ],
            [
                ['Chest to Bar Pull Up', 'Thruster', 'Row'],
                ['Chest to Bar Pull Up', 'Thruster', 'Power Clean'],
                ['Chest to Bar Pull Up', 'Thruster', 'Wall Ball Shot'],
            ],
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function movementFrequencyBands(): array
    {
        return $this->movementFrequencyBands;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public function frequentMovementPairs(): array
    {
        return $this->frequentMovementPairs;
    }

    /**
     * @return list<string>
     */
    public function recentGeneratedTemplateMovements(): array
    {
        return $this->recentGeneratedTemplateMovements;
    }

    /**
     * @return list<string>
     */
    public function overusedRotationAnchors(): array
    {
        return $this->overusedRotationAnchors;
    }

    /**
     * @return list<list<string>>
     */
    public function rejectedMovementClusters(): array
    {
        return $this->rejectedMovementClusters;
    }
}
