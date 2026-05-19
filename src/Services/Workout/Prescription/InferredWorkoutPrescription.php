<?php

namespace App\Services\Workout\Prescription;

final readonly class InferredWorkoutPrescription
{
    /**
     * @param list<string>             $divisionHints
     * @param list<string>             $levelHints
     * @param list<string>             $movementNames
     * @param list<string>             $implementNames
     * @param list<WorkoutLoadMention> $loads
     */
    public function __construct(
        public array $divisionHints,
        public array $levelHints,
        public array $movementNames,
        public array $implementNames,
        public array $loads,
    ) {
    }

    public function hasActionableSignal(): bool
    {
        return $this->loads !== [] || $this->levelHints !== [];
    }
}
