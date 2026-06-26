<?php

namespace App\Tests;

use App\Command\BenchmarkWorkoutGenerationCommand;
use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
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
}
