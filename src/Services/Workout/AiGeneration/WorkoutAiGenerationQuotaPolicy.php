<?php

namespace App\Services\Workout\AiGeneration;

readonly class WorkoutAiGenerationQuotaPolicy
{
    public function __construct(
        private int $anonymousDailyLimit,
        private int $freeUserDailyLimit,
    ) {
    }

    public function dailyLimitFor(WorkoutAiGenerationActor $actor): ?int
    {
        if ($actor->isAdmin()) {
            return null;
        }

        // Keep the policy centralized so paid-plan entitlements can be added here
        // without scattering quota decisions across controllers.
        return $actor->user === null ? $this->anonymousDailyLimit : $this->freeUserDailyLimit;
    }
}
