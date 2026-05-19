<?php

namespace App\Tests;

use App\Command\InferWorkoutPrescriptionPatternsCommand;
use Symfony\Component\Console\Tester\CommandTester;

final class InferWorkoutPrescriptionPatternsCommandTest extends AbstractIntegrationTest
{
    public function testReportUsesTextInferredMovementsForImportedWorkouts(): void
    {
        $tester = new CommandTester($this->getService(InferWorkoutPrescriptionPatternsCommand::class));
        $exitCode = $tester->execute(['--name' => 'JT', '--limit' => 1]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Workout prescription pattern report', $tester->getDisplay());
        self::assertStringContainsString('Handstand Push Up', $tester->getDisplay());
        self::assertStringContainsString('rings', $tester->getDisplay());
    }
}
