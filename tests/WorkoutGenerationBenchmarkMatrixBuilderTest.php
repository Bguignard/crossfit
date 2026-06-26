<?php

namespace App\Tests;

use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use PHPUnit\Framework\TestCase;

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
}
