<?php

namespace App\Services\Workout\Audit;

final readonly class WorkoutStimulusAuditScenario
{
    /**
     * @param list<string> $expectedTerms
     * @param list<string> $expectedScalingTerms
     * @param list<string> $forbiddenTerms
     */
    public function __construct(
        public string $slug,
        public string $stimulus,
        public string $intent,
        public string $workoutType,
        public int $timeCap,
        public int $movementCount,
        public array $expectedTerms,
        public array $expectedScalingTerms = ['rx', 'intermediate', 'scaled'],
        public array $forbiddenTerms = [],
        public ?int $expectedStationCount = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name' => sprintf('Audit %s', $this->stimulus),
            'stimulus' => $this->stimulus,
            'stimulusIntent' => $this->intent,
            'workoutType' => $this->workoutType,
            'timeCap' => $this->timeCap,
            'numberOfDifferentMovements' => $this->movementCount,
            'movementDifficulty' => 'RX',
            'isTeamWorkout' => false,
        ];
    }
}
