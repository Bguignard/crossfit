<?php

namespace App\Services\Workout\Audit;

final readonly class WorkoutGenerationBenchmarkMatrixBuilder
{
    private const STRATEGIES = [
        'full_ai' => 'Current fully AI-framed generation through WorkoutCreatorService prompts and MonWOD validations.',
        'hybrid_monwod_ai' => 'Deterministic MonWOD movement selection/guidance with AI finalization for text, scaling and arbitration.',
        'no_ai_baseline' => 'No AI baseline; currently records that no WOD is generated without deterministic workout-building rules.',
    ];

    /**
     * @return array<string, string>
     */
    public function strategies(): array
    {
        return self::STRATEGIES;
    }

    /**
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     * @param list<string>                       $models
     * @param list<string>|null                  $strategies
     *
     * @return array<string, mixed>
     */
    public function buildDryRunReport(array $scenarios, array $models, ?array $strategies = null): array
    {
        $strategies = $this->normalizeStrategies($strategies ?? array_keys(self::STRATEGIES));
        $entries = [];

        foreach ($models as $model) {
            foreach ($strategies as $strategy) {
                foreach ($scenarios as $scenario) {
                    $entries[] = $this->dryRunEntry($model, $strategy, $scenario);
                }
            }
        }

        return $this->report(
            scenarios: $scenarios,
            models: $models,
            strategies: $strategies,
            entries: $entries,
            dryRun: true,
            live: false,
            liveMode: [
                'available' => false,
                'reason' => 'Dry run only: no OpenAI call was performed. Use --live --confirm-live with a strict --max-live-runs limit to execute benchmark calls.',
            ],
        );
    }

    /**
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     * @param list<string>                       $models
     * @param list<string>                       $strategies
     * @param list<array<string, mixed>>         $entries
     *
     * @return array<string, mixed>
     */
    public function buildLiveReport(array $scenarios, array $models, array $strategies, array $entries): array
    {
        return $this->report(
            scenarios: $scenarios,
            models: $models,
            strategies: $this->normalizeStrategies($strategies),
            entries: $entries,
            dryRun: false,
            live: true,
            liveMode: [
                'available' => true,
                'reason' => 'Live mode was explicitly enabled. Only implemented strategies perform OpenAI calls; unavailable token/cost fields remain null.',
            ],
        );
    }

    /**
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     * @param list<string>                       $models
     * @param list<string>                       $strategies
     * @param list<array<string, mixed>>         $entries
     * @param array<string, mixed>               $liveMode
     *
     * @return array<string, mixed>
     */
    private function report(array $scenarios, array $models, array $strategies, array $entries, bool $dryRun, bool $live, array $liveMode): array
    {
        return [
            'kind' => 'workout_generation_benchmark_matrix_v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'dryRun' => $dryRun,
            'live' => $live,
            'modelCount' => count($models),
            'strategyCount' => count($strategies),
            'scenarioCount' => count($scenarios),
            'entryCount' => count($entries),
            'models' => $models,
            'strategies' => array_intersect_key(self::STRATEGIES, array_flip($strategies)),
            'scenarios' => array_map(
                static fn (WorkoutStimulusAuditScenario $scenario): array => [
                    'slug' => $scenario->slug,
                    'stimulus' => $scenario->stimulus,
                    'intent' => $scenario->intent,
                    'payload' => $scenario->payload(),
                    'expectedTerms' => $scenario->expectedTerms,
                    'expectedScalingTerms' => $scenario->expectedScalingTerms,
                    'forbiddenTerms' => $scenario->forbiddenTerms,
                    'expectedStationCount' => $scenario->expectedStationCount,
                ],
                $scenarios,
            ),
            'entries' => $entries,
            'summary' => $this->summary($entries),
            'liveMode' => $liveMode,
        ];
    }

    /**
     * @param list<string> $rawModels
     *
     * @return list<string>
     */
    public function normalizeModels(array $rawModels): array
    {
        $models = [];
        foreach ($rawModels as $rawModel) {
            $model = trim($rawModel);
            if ($model === '') {
                continue;
            }

            $models[$model] = true;
        }

        return array_keys($models);
    }

    /**
     * @param list<string> $rawStrategies
     *
     * @return list<string>
     */
    public function normalizeStrategies(array $rawStrategies): array
    {
        $strategies = [];
        foreach ($rawStrategies as $rawStrategy) {
            $strategy = trim($rawStrategy);
            if ($strategy === '') {
                continue;
            }
            if (!array_key_exists($strategy, self::STRATEGIES)) {
                throw new \InvalidArgumentException(sprintf('Unknown benchmark strategy "%s". Allowed strategies: %s.', $strategy, implode(', ', array_keys(self::STRATEGIES))));
            }

            $strategies[$strategy] = true;
        }

        return array_keys($strategies);
    }

    /**
     * @return array<string, mixed>
     */
    private function dryRunEntry(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array
    {
        $status = $strategy === 'no_ai_baseline' ? 'not_generated' : 'dry_run_pending';
        $failureReason = $strategy === 'no_ai_baseline'
            ? 'No deterministic no-AI workout generation baseline exists yet.'
            : 'Dry run only; generation was not executed.';

        return [
            'model' => $model,
            'strategy' => $strategy,
            'scenario' => $scenario->slug,
            'status' => $status,
            'passed' => false,
            'failureReason' => $failureReason,
            'tokenUsage' => [
                'promptTokens' => null,
                'completionTokens' => null,
                'totalTokens' => null,
            ],
            'retryCount' => null,
            'durationMs' => null,
            'estimatedCostUsd' => null,
            'checks' => [
                'generated_workout_available' => false,
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     *
     * @return array<string, mixed>
     */
    private function summary(array $entries): array
    {
        $byStrategy = [];

        foreach ($entries as $entry) {
            $strategy = (string) $entry['strategy'];
            $byStrategy[$strategy] ??= [
                'entryCount' => 0,
                'passedCount' => 0,
                'estimatedCostUsd' => null,
            ];

            ++$byStrategy[$strategy]['entryCount'];
            if ($entry['passed'] === true) {
                ++$byStrategy[$strategy]['passedCount'];
            }
        }

        return [
            'entryCount' => count($entries),
            'passedCount' => count(array_filter($entries, static fn (array $entry): bool => $entry['passed'] === true)),
            'estimatedCostUsd' => null,
            'byStrategy' => $byStrategy,
        ];
    }
}
