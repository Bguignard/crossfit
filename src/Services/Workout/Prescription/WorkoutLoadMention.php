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
        public int $offset,
        public string $nearText,
        public ?string $positionLabel,
        public ?string $audienceHint,
        public ?string $movementHint,
    ) {
    }

    public function label(): string
    {
        $equipment = $this->equipmentHint === 'unknown' ? '' : ' '.$this->equipmentHint;

        return $this->loadLabel().$equipment;
    }

    public function loadLabel(): string
    {
        $values = implode('/', array_map(static function (float $value): string {
            return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
        }, $this->values));

        return sprintf('%s %s', $values, $this->unit);
    }
}
