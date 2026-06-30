<?php

namespace App\Services\Workout\AiGeneration;

use App\Entity\Security\User;

readonly class WorkoutAiGenerationActor
{
    public function __construct(
        public ?User $user,
        public string $type,
        public ?string $visitorHash,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }
}
