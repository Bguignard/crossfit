<?php

namespace App\Tests;

use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class WorkoutGenerationBenchmarkMatrixBuilderTest extends TestCase
{
    public function testBuildDryRunReportCreatesModelStrategyScenarioMatrix(): void
    {
        $builder = new WorkoutGenerationBenchmarkMatrixBuilder();
        $scenarios = (new WorkoutStimulusAuditor())->scenarios();

        $report = $builder->buildDryRunReport($scenarios, ['gpt-5.4-mini', 'gpt-5.4']);

        self::assertSame('workout_generation_benchmark_matrix_v1', $report['kind']);
        self::assertTrue($report['dryRun']);
        self::assertFalse($report['live']);
        self::assertSame(2, $report['modelCount']);
        self::assertSame(3, $report['strategyCount']);
        self::assertSame(9, $report['scenarioCount']);
        self::assertSame(54, $report['entryCount']);
        self::assertArrayHasKey('full_ai', $report['strategies']);
        self::assertArrayHasKey('hybrid_monwod_ai', $report['strategies']);
        self::assertArrayHasKey('no_ai_baseline', $report['strategies']);
        self::assertSame('gpt-5.4-mini', $report['entries'][0]['model']);
        self::assertSame('full_ai', $report['entries'][0]['strategy']);
        self::assertSame('strength', $report['entries'][0]['scenario']);
        self::assertSame('dry_run_pending', $report['entries'][0]['status']);
        self::assertFalse($report['entries'][0]['passed']);
        self::assertNull($report['entries'][0]['tokenUsage']['totalTokens']);
        self::assertNull($report['entries'][0]['retryCount']);
        self::assertNull($report['entries'][0]['estimatedCostUsd']);
        self::assertFalse($report['liveMode']['available']);
    }

    public function testNormalizeModelsTrimsDeduplicatesAndDropsEmptyValues(): void
    {
        $builder = new WorkoutGenerationBenchmarkMatrixBuilder();

        self::assertSame(
            ['gpt-5.4-mini', 'gpt-5.4'],
            $builder->normalizeModels([' gpt-5.4-mini ', '', 'gpt-5.4-mini', 'gpt-5.4'])
        );
    }

    public function testBuildLiveReportKeepsExecutionMetricsShape(): void
    {
        $builder = new WorkoutGenerationBenchmarkMatrixBuilder();
        $scenario = (new WorkoutStimulusAuditor())->scenarios()[0];

        $report = $builder->buildLiveReport(
            [$scenario],
            ['gpt-live-test'],
            ['full_ai'],
            [[
                'model' => 'gpt-live-test',
                'strategy' => 'full_ai',
                'scenario' => $scenario->slug,
                'status' => 'validation_failed',
                'passed' => false,
                'failureReason' => 'Generated workout did not pass scenario validation checks.',
                'tokenUsage' => [
                    'promptTokens' => 1200,
                    'completionTokens' => 300,
                    'totalTokens' => 1500,
                ],
                'retryCount' => null,
                'durationMs' => 987,
                'estimatedCostUsd' => null,
                'checks' => [
                    'generated_workout_available' => true,
                    'stimulus_terms_present' => false,
                ],
            ]]
        );

        self::assertFalse($report['dryRun']);
        self::assertTrue($report['live']);
        self::assertSame(1, $report['modelCount']);
        self::assertSame(1, $report['strategyCount']);
        self::assertSame(1, $report['scenarioCount']);
        self::assertSame(1, $report['entryCount']);
        self::assertSame(1500, $report['entries'][0]['tokenUsage']['totalTokens']);
        self::assertNull($report['entries'][0]['estimatedCostUsd']);
        self::assertTrue($report['liveMode']['available']);
    }

    public function testNormalizeStrategiesRejectsUnknownValues(): void
    {
        $builder = new WorkoutGenerationBenchmarkMatrixBuilder();

        self::assertSame(['full_ai', 'no_ai_baseline'], $builder->normalizeStrategies([' full_ai ', '', 'no_ai_baseline', 'full_ai']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown benchmark strategy');

        $builder->normalizeStrategies(['full_ai', 'future_strategy']);
    }
}
