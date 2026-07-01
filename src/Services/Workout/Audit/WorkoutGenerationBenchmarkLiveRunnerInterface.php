<?php

namespace App\Services\Workout\Audit;

interface WorkoutGenerationBenchmarkLiveRunnerInterface
{
    public function isConfigured(): bool;

    public function requiresOpenAi(string $strategy): bool;

    /**
     * @return array<string, mixed>
     */
    public function run(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array;
}
