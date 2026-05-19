<?php

namespace App\Services\Workout\Prescription;

final readonly class WorkoutLoadMention
{
    /**
     * @param list<float> $values
     */
    public function __construct(
        public string $raw,
        public array $values,
        public string $unit,
        public string $equipmentHint,
    ) {
    }

    public function label(): string
    {
        $values = implode('/', array_map(static function (float $value): string {
            return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
        }, $this->values));

        $equipment = $this->equipmentHint === 'unknown' ? '' : ' '.$this->equipmentHint;

        return sprintf('%s %s%s', $values, $this->unit, $equipment);
    }
}
