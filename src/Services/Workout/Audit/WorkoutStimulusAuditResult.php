<?php

namespace App\Services\Workout\Audit;

final readonly class WorkoutStimulusAuditResult
{
    /**
     * @param array<string, bool> $checks
     * @param list<string>        $termHits
     * @param list<string>        $scalingHits
     * @param list<string>        $forbiddenHits
     */
    public function __construct(
        public string $scenarioSlug,
        public bool $passed,
        public array $checks,
        public array $termHits,
        public array $scalingHits,
        public array $forbiddenHits,
        public int $stationCount,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario' => $this->scenarioSlug,
            'passed' => $this->passed,
            'checks' => $this->checks,
            'termHits' => $this->termHits,
            'scalingHits' => $this->scalingHits,
            'forbiddenHits' => $this->forbiddenHits,
            'stationCount' => $this->stationCount,
        ];
    }
}
