<?php

namespace App\Services\Workout\Prescription;

final readonly class WorkoutLoadCandidate
{
    /**
     * @param list<WorkoutLoadMention> $mentions
     */
    public function __construct(
        public string $kind,
        public string $equipmentHint,
        public array $mentions,
    ) {
    }

    public function label(): string
    {
        $mentions = implode(' ~= ', array_map(
            static fn (WorkoutLoadMention $mention): string => $mention->loadLabel(),
            $this->mentions,
        ));
        $equipment = $this->equipmentHint === 'unknown' ? '' : ' '.$this->equipmentHint;
        $suffix = match ($this->kind) {
            'conversion' => ' conversion',
            'paired_load' => ' paired',
            default => '',
        };

        return $mentions.$equipment.$suffix;
    }
}
