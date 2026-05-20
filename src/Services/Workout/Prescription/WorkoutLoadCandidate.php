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

    /**
     * @return array<string, list<string>>
     */
    public function contextHints(): array
    {
        return [
            'positions' => $this->uniqueMentionValues(static fn (WorkoutLoadMention $mention): ?string => $mention->positionLabel),
            'audiences' => $this->uniqueMentionValues(static fn (WorkoutLoadMention $mention): ?string => $mention->audienceHint),
            'movements' => $this->uniqueMentionValues(static fn (WorkoutLoadMention $mention): ?string => $mention->movementHint),
        ];
    }

    /**
     * @param callable(WorkoutLoadMention): ?string $value
     *
     * @return list<string>
     */
    private function uniqueMentionValues(callable $value): array
    {
        $values = [];
        foreach ($this->mentions as $mention) {
            $hint = $value($mention);
            if ($hint !== null) {
                $values[$hint] = true;
            }
        }

        return array_keys($values);
    }
}
