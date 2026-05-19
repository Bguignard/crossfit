<?php

namespace App\Tests;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Services\Workout\Prescription\WorkoutPrescriptionPatternInferer;
use PHPUnit\Framework\TestCase;

final class WorkoutPrescriptionPatternInfererTest extends TestCase
{
    public function testInfersPairedBarbellLoadsAndLevelHints(): void
    {
        $workout = $this->workout(
            'Elite/RX event',
            'For time: 21-15-9 thrusters at 135/95 lb and chest-to-bar pull-ups. Elite athletes use RX loading.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertSame(['elite', 'rx'], $prescription->levelHints);
        self::assertCount(1, $prescription->loads);
        self::assertSame([135.0, 95.0], $prescription->loads[0]->values);
        self::assertSame('lb', $prescription->loads[0]->unit);
        self::assertSame('barbell', $prescription->loads[0]->equipmentHint);
    }

    public function testInfersSingleDumbbellAndRepeatedKettlebellLoads(): void
    {
        $workout = $this->workout(
            'Scaled team event',
            'AMRAP with 50-lb dumbbell snatches, then 2 x 24 kg kettlebell front-rack walking lunges. Scaled pairs split reps.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertSame(['scaled'], $prescription->levelHints);
        self::assertCount(2, $prescription->loads);
        self::assertSame('50 lb dumbbell', $prescription->loads[0]->label());
        self::assertSame('24/24 kg kettlebell', $prescription->loads[1]->label());
    }

    private function workout(string $name, string $flow): Workout
    {
        return new Workout(
            $name,
            $flow,
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), null),
        );
    }
}
