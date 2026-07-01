<?php

namespace App\Services\Workout\AiGeneration;

readonly class WorkoutAiGenerationQuota
{
    public function __construct(
        public ?int $limit,
        public int $used,
        public ?int $remaining,
        public \DateTimeImmutable $resetAt,
        public bool $isAllowed,
        public bool $isUnlimited = false,
    ) {
    }

    /**
     * @return array{limit: int|null, used: int, remaining: int|null, resetAt: string, isAllowed: bool, isUnlimited: bool}
     */
    public function toArray(): array
    {
        return [
            'limit' => $this->limit,
            'used' => $this->used,
            'remaining' => $this->remaining,
            'resetAt' => $this->resetAt->format(\DateTimeInterface::ATOM),
            'isAllowed' => $this->isAllowed,
            'isUnlimited' => $this->isUnlimited,
        ];
    }
}
