<?php

namespace App\Tests;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Services\Workout\WorkoutPlaceholderFlowDetector;
use PHPUnit\Framework\TestCase;

final class WorkoutPlaceholderFlowDetectorTest extends TestCase
{
    public function testHidesEmptyAndDashPlaceholderFlows(): void
    {
        $detector = new WorkoutPlaceholderFlowDetector();

        self::assertNull($detector->displayableFlow($this->workout('Workout 1', '')));
        self::assertNull($detector->displayableFlow($this->workout('Workout 1', '-')));
    }

    public function testHidesFlowMatchingWorkoutOrEventLabel(): void
    {
        $detector = new WorkoutPlaceholderFlowDetector();

        self::assertNull($detector->displayableFlow($this->workout('Workout WOD 1', 'WOD 1'), 'WOD 1'));
        self::assertNull($detector->displayableFlow($this->workout('WOD 2', 'Workout WOD 2'), 'WOD 2'));
    }

    public function testKeepsUsefulWorkoutFlow(): void
    {
        $detector = new WorkoutPlaceholderFlowDetector();

        self::assertSame(
            "For time:\n21-15-9\nThrusters\nPull-ups",
            $detector->displayableFlow($this->workout('Fran', "For time:\n21-15-9\nThrusters\nPull-ups"), 'Fran')
        );
    }

    private function workout(string $name, string $flow): Workout
    {
        return new Workout(
            $name,
            $flow,
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2025),
        );
    }
}
