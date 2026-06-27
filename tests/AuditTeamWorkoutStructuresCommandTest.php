<?php

namespace App\Tests;

use App\Command\AuditTeamWorkoutStructuresCommand;
use App\Services\Workout\Audit\TeamWorkoutStructurePatternClassifier;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class AuditTeamWorkoutStructuresCommandTest extends TestCase
{
    public function testReportPreservesEventAndCompetitionCountsAfterWorkoutAggregation(): void
    {
        $command = new AuditTeamWorkoutStructuresCommand(
            $this->createMock(Connection::class),
            new TeamWorkoutStructurePatternClassifier(),
        );
        $buildReport = new \ReflectionMethod($command, 'buildReport');
        $buildReport->setAccessible(true);

        $report = $buildReport->invoke($command, [], [
            [
                'id' => 'workout-1',
                'name' => 'Shared team workout',
                'flow' => 'Teams of two, complete 200 total reps as a team, split reps anyhow.',
                'source' => 'fixture',
                'competitions' => [
                    ['id' => 'competition-1', 'name' => 'Competition A'],
                    ['id' => 'competition-2', 'name' => 'Competition B'],
                ],
                'events' => [
                    ['id' => 'event-1', 'name' => 'Event 1'],
                    ['id' => 'event-2', 'name' => 'Event 2'],
                ],
            ],
        ], 1);

        self::assertSame(1, $report['summary']['workoutCount']);
        self::assertSame(2, $report['summary']['competitionCount']);
        self::assertSame(2, $report['summary']['eventCount']);
        self::assertSame(['Competition A', 'Competition B'], $report['examplesPerPattern'][TeamWorkoutStructurePatternClassifier::SPLIT_ANYHOW][0]['competitions']);
        self::assertSame(['Event 1', 'Event 2'], $report['examplesPerPattern'][TeamWorkoutStructurePatternClassifier::SPLIT_ANYHOW][0]['events']);
    }
}
