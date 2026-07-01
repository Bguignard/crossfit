<?php

namespace App\Tests;

use App\Command\BenchmarkWorkoutGenerationCommand;
use App\Services\Workout\Audit\WorkoutGenerationBenchmarkLiveRunnerInterface;
use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use App\Services\Workout\Audit\WorkoutStimulusAuditScenario;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group unit
 */
final class BenchmarkWorkoutGenerationCommandTest extends TestCase
{
    public function testCommandWritesDryRunBenchmarkMatrixReports(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
        );
        $tester = new CommandTester($command);
        $directory = sys_get_temp_dir().'/monwod-workout-generation-benchmark-'.bin2hex(random_bytes(4));
        $jsonPath = $directory.'/benchmark.json';
        $markdownPath = $directory.'/benchmark.md';

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--models' => 'gpt-5.4-mini,gpt-5.4',
            '--output' => $jsonPath,
            '--markdown-output' => $markdownPath,
        ]));

        self::assertFileExists($jsonPath);
        self::assertFileExists($markdownPath);

        $report = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('workout_generation_benchmark_matrix_v1', $report['kind']);
        self::assertTrue($report['dryRun']);
        self::assertFalse($report['live']);
        self::assertSame(['gpt-5.4-mini', 'gpt-5.4'], $report['models']);
        self::assertSame(54, $report['entryCount']);
        self::assertSame('full_ai', $report['entries'][0]['strategy']);
        self::assertSame('hybrid_monwod_ai', $report['entries'][9]['strategy']);
        self::assertSame('no_ai_baseline', $report['entries'][18]['strategy']);
        self::assertNull($report['entries'][0]['tokenUsage']['totalTokens']);
        self::assertFalse($report['liveMode']['available']);

        $markdown = (string) file_get_contents($markdownPath);
        self::assertStringContainsString('# Workout Generation Benchmark Matrix', $markdown);
        self::assertStringContainsString('`full_ai`', $markdown);
        self::assertStringContainsString('`hybrid_monwod_ai`', $markdown);
        self::assertStringContainsString('`no_ai_baseline`', $markdown);
        self::assertStringContainsString('Dry run only: no OpenAI call is performed.', $tester->getDisplay());
    }

    public function testLiveCommandWritesProtectedBenchmarkReportWithExplicitOptions(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            new class implements WorkoutGenerationBenchmarkLiveRunnerInterface {
                public function isConfigured(): bool
                {
                    return true;
                }

                public function requiresOpenAi(string $strategy): bool
                {
                    return $strategy === 'full_ai';
                }

                public function run(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array
                {
                    return [
                        'model' => $model,
                        'strategy' => $strategy,
                        'scenario' => $scenario->slug,
                        'status' => 'success',
                        'passed' => true,
                        'failureReason' => null,
                        'tokenUsage' => [
                            'promptTokens' => 30,
                            'completionTokens' => 12,
                            'totalTokens' => 42,
                        ],
                        'retryCount' => null,
                        'durationMs' => 123,
                        'estimatedCostUsd' => null,
                        'checks' => [
                            'generated_workout_available' => true,
                        ],
                    ];
                }
            },
        );
        $tester = new CommandTester($command);
        $directory = sys_get_temp_dir().'/monwod-workout-generation-live-benchmark-'.bin2hex(random_bytes(4));
        $jsonPath = $directory.'/benchmark.json';
        $markdownPath = $directory.'/benchmark.md';

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai',
            '--scenarios' => 'strength',
            '--max-live-runs' => '1',
            '--output' => $jsonPath,
            '--markdown-output' => $markdownPath,
        ]));

        $report = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($report['dryRun']);
        self::assertTrue($report['live']);
        self::assertTrue($report['liveMode']['available']);
        self::assertSame(1, $report['entryCount']);
        self::assertSame('success', $report['entries'][0]['status']);
        self::assertSame(42, $report['entries'][0]['tokenUsage']['totalTokens']);
        self::assertStringContainsString('Live mode was explicitly enabled', $tester->getDisplay());
    }

    public function testLiveCommandRequiresExplicitScenarioSelection(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            $this->configuredLiveRunner(),
        );
        $tester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Live benchmark requires --scenarios');

        $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai',
            '--output' => sys_get_temp_dir().'/unused.json',
            '--markdown-output' => sys_get_temp_dir().'/unused.md',
        ]);
    }

    public function testLiveCommandRejectsPlansAboveConfiguredLimit(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            $this->configuredLiveRunner(),
        );
        $tester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('above --max-live-runs=1');

        $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai,no_ai_baseline',
            '--scenarios' => 'strength',
            '--max-live-runs' => '1',
            '--output' => sys_get_temp_dir().'/unused.json',
            '--markdown-output' => sys_get_temp_dir().'/unused.md',
        ]);
    }

    public function testLiveCommandRequiresConfiguredApiKeyForOpenAiStrategies(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            new class implements WorkoutGenerationBenchmarkLiveRunnerInterface {
                public function isConfigured(): bool
                {
                    return false;
                }

                public function requiresOpenAi(string $strategy): bool
                {
                    return $strategy === 'full_ai';
                }

                public function run(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array
                {
                    throw new \RuntimeException('The runner must not execute when the API key is missing.');
                }
            },
        );
        $tester = new CommandTester($command);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CHAT_GPT_API_KEY must be configured');

        $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai',
            '--scenarios' => 'strength',
            '--max-live-runs' => '1',
            '--output' => sys_get_temp_dir().'/unused.json',
            '--markdown-output' => sys_get_temp_dir().'/unused.md',
        ]);
    }

    public function testLiveCommandValidatesReportPathsBeforeRunningBenchmark(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            $this->configuredLiveRunner(),
        );
        $tester = new CommandTester($command);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option must be a non-empty string.');

        $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai',
            '--scenarios' => 'strength',
            '--max-live-runs' => '1',
            '--output' => '',
            '--markdown-output' => sys_get_temp_dir().'/unused.md',
        ]);
    }

    public function testLiveCommandValidatesReportTargetBeforeRunningBenchmark(): void
    {
        $command = new BenchmarkWorkoutGenerationCommand(
            new WorkoutStimulusAuditor(),
            new WorkoutGenerationBenchmarkMatrixBuilder(),
            $this->configuredLiveRunner(),
        );
        $tester = new CommandTester($command);
        $invalidParent = sys_get_temp_dir().'/monwod-benchmark-parent-file-'.bin2hex(random_bytes(4));
        file_put_contents($invalidParent, 'not a directory');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exists but is not a directory');

        $tester->execute([
            '--live' => true,
            '--confirm-live' => true,
            '--models' => 'gpt-live-test',
            '--strategies' => 'full_ai',
            '--scenarios' => 'strength',
            '--max-live-runs' => '1',
            '--output' => $invalidParent.'/benchmark.json',
            '--markdown-output' => sys_get_temp_dir().'/unused.md',
        ]);
    }

    private function configuredLiveRunner(): WorkoutGenerationBenchmarkLiveRunnerInterface
    {
        return new class implements WorkoutGenerationBenchmarkLiveRunnerInterface {
            public function isConfigured(): bool
            {
                return true;
            }

            public function requiresOpenAi(string $strategy): bool
            {
                return $strategy === 'full_ai';
            }

            public function run(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array
            {
                throw new \RuntimeException('This test should stop before live execution.');
            }
        };
    }
}
