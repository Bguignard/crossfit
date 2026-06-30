<?php

namespace App\Tests;

use App\Command\AuditCompetitionMovementFrequenciesCommand;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class AuditCompetitionMovementFrequenciesCommandTest extends TestCase
{
    public function testCanonicalDeduplicationOptionIsAvailable(): void
    {
        $command = new AuditCompetitionMovementFrequenciesCommand($this->createMock(Connection::class));

        self::assertTrue($command->getDefinition()->hasOption('deduplicate-canonical'));
    }

    public function testDetectedRowsCanBeGroupedByCanonicalWorkoutKey(): void
    {
        $command = new AuditCompetitionMovementFrequenciesCommand($this->createMock(Connection::class));
        $groupDetectedRows = new \ReflectionMethod($command, 'groupDetectedRows');
        $groupDetectedRows->setAccessible(true);

        $detected = $groupDetectedRows->invoke($command, [
            [
                'workout_key' => 'canonical-fran',
                'workout_id' => 'workout-1',
                'movement_id' => 'movement-thruster',
                'movement' => 'Thruster',
                'movement_type' => 'Weightlifting',
            ],
            [
                'workout_key' => 'canonical-fran',
                'workout_id' => 'workout-2',
                'movement_id' => 'movement-thruster',
                'movement' => 'Thruster',
                'movement_type' => 'Weightlifting',
            ],
            [
                'workout_key' => 'canonical-fran',
                'workout_id' => 'workout-2',
                'movement_id' => 'movement-pull-up',
                'movement' => 'Pull Up',
                'movement_type' => 'Gymnastics',
            ],
        ]);

        self::assertSame(['canonical-fran'], array_keys($detected));
        self::assertSame([
            [
                'id' => 'movement-pull-up',
                'name' => 'Pull Up',
                'movementType' => 'Gymnastics',
            ],
            [
                'id' => 'movement-thruster',
                'name' => 'Thruster',
                'movementType' => 'Weightlifting',
            ],
        ], $detected['canonical-fran']);
    }

    public function testCanonicalWorkoutKeySqlFallsBackToWorkoutId(): void
    {
        $command = new AuditCompetitionMovementFrequenciesCommand($this->createMock(Connection::class));
        $workoutKeySql = new \ReflectionMethod($command, 'workoutKeySql');
        $workoutKeySql->setAccessible(true);

        self::assertSame('w.id::TEXT', $workoutKeySql->invoke($command, false, 'w'));
        self::assertSame(
            'COALESCE(NULLIF(w.canonical_fingerprint, \'\'), w.id::TEXT)',
            $workoutKeySql->invoke($command, true, 'w'),
        );
    }
}
