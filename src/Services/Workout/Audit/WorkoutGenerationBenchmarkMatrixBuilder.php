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
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     * @param list<string>                       $models
     *
     * @return array<string, mixed>
     */
    public function buildDryRunReport(array $scenarios, array $models): array
    {
        $entries = [];

        foreach ($models as $model) {
            foreach (array_keys(self::STRATEGIES) as $strategy) {
                foreach ($scenarios as $scenario) {
                    $entries[] = $this->dryRunEntry($model, $strategy, $scenario);
                }
            }
        }

        return [
            'kind' => 'workout_generation_benchmark_matrix_v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'dryRun' => true,
            'live' => false,
            'modelCount' => count($models),
            'strategyCount' => count(self::STRATEGIES),
            'scenarioCount' => count($scenarios),
            'entryCount' => count($entries),
            'models' => $models,
            'strategies' => self::STRATEGIES,
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
            'liveMode' => [
                'available' => false,
                'reason' => 'This MVP intentionally does not call OpenAI. Live multi-model generation should be added once prompt/model injection is isolated for benchmark runs.',
            ],
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
