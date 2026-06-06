<?php

namespace App\Tests;

use App\Command\AuditWorkoutStimuliCommand;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AuditWorkoutStimuliCommandTest extends TestCase
{
    public function testCommandWritesDryRunReports(): void
    {
        $command = new AuditWorkoutStimuliCommand(new WorkoutStimulusAuditor());
        $tester = new CommandTester($command);
        $directory = sys_get_temp_dir().'/monwod-workout-stimulus-audit-'.bin2hex(random_bytes(4));
        $jsonPath = $directory.'/audit.json';
        $markdownPath = $directory.'/audit.md';

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--output' => $jsonPath,
            '--markdown-output' => $markdownPath,
        ]));

        self::assertFileExists($jsonPath);
        self::assertFileExists($markdownPath);

        $report = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('workout_stimulus_audit_v1', $report['kind']);
        self::assertTrue($report['dryRun']);
        self::assertSame(9, $report['scenarioCount']);
        self::assertSame(0, $report['generatedWorkoutCount']);
        self::assertCount(9, $report['scenarios']);
        self::assertSame('Simulation Hyrox', $report['scenarios'][5]['stimulus']);
        self::assertSame(8, $report['scenarios'][5]['expectedStationCount']);
        self::assertFalse($report['results'][0]['checks']['generated_workout_available']);

        $markdown = (string) file_get_contents($markdownPath);
        self::assertStringContainsString('# Workout Stimulus Audit', $markdown);
        self::assertStringContainsString('Simulation Hyrox', $markdown);
        self::assertStringContainsString('Dry Run Checks', $markdown);
    }
}
