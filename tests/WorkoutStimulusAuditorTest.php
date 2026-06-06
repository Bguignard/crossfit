<?php

namespace App\Tests;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use App\Services\Workout\Audit\WorkoutStimulusAuditScenario;
use PHPUnit\Framework\TestCase;

final class WorkoutStimulusAuditorTest extends TestCase
{
    public function testScenariosCoverCurrentStimuli(): void
    {
        $auditor = new WorkoutStimulusAuditor();

        $scenarios = $auditor->scenarios();
        $slugs = array_map(static fn ($scenario): string => $scenario->slug, $scenarios);

        self::assertCount(9, $scenarios);
        self::assertContains('strength', $slugs);
        self::assertContains('hyrox_training', $slugs);
        self::assertContains('hyrox_simulation', $slugs);
        self::assertContains('competition', $slugs);
        foreach ($scenarios as $scenario) {
            self::assertNotSame([], $scenario->payload());
            self::assertGreaterThan(0, $scenario->timeCap);
            self::assertGreaterThan(0, $scenario->movementCount);
        }
    }

    public function testHyroxSimulationPassesWithEightStationsAndScaling(): void
    {
        $auditor = new WorkoutStimulusAuditor();
        $scenario = $this->scenario($auditor, 'hyrox_simulation');
        $workout = $this->workout(
            name: 'Race rehearsal',
            flow: <<<'FLOW'
For time:
Run segment, station 1 Ski Erg
Run segment, station 2 Sled Push
Run segment, station 3 Sled Pull
Run segment, station 4 Burpee broad jump
Run segment, station 5 Row
Run segment, station 6 Farmer Carry
Run segment, station 7 Sandbag Lunge
Run segment, station 8 Wall Ball
Scaling options:
RX: standard race loads.
Intermediate: reduce sled and wall ball load.
Scaled: shorter runs and lighter carries.
FLOW,
            timeCap: 75,
            type: WorkoutTypeEnum::FOR_TIME
        );

        $result = $auditor->evaluate($scenario, $workout);

        self::assertTrue($result->passed);
        self::assertTrue($result->checks['expected_station_count']);
        self::assertSame(8, $result->stationCount);
        self::assertContains('wall ball', $result->termHits);
    }

    public function testAuditFlagsMissingScaling(): void
    {
        $auditor = new WorkoutStimulusAuditor();
        $scenario = $this->scenario($auditor, 'sprint');
        $workout = $this->workout(
            name: 'Fast couplet',
            flow: 'Sprint fast and intense. Time cap 6 minutes. Go unbroken.',
            timeCap: 6,
            type: WorkoutTypeEnum::FOR_TIME
        );

        $result = $auditor->evaluate($scenario, $workout);

        self::assertFalse($result->passed);
        self::assertFalse($result->checks['scaling_present']);
    }

    public function testHyroxTrainingRejectsFullSimulationOveremphasis(): void
    {
        $auditor = new WorkoutStimulusAuditor();
        $scenario = $this->scenario($auditor, 'hyrox_training');
        $workout = $this->workout(
            name: 'Too much Hyrox',
            flow: <<<'FLOW'
Complete Hyrox with run, row, wall ball, farmer carry, sled and station work.
This is a complete Hyrox simulation with 8 stations.
Scaling options:
RX: standard.
Intermediate: reduce load.
Scaled: reduce distance.
FLOW,
            timeCap: 60,
            type: WorkoutTypeEnum::FOR_TIME
        );

        $result = $auditor->evaluate($scenario, $workout);

        self::assertFalse($result->passed);
        self::assertFalse($result->checks['no_forbidden_overemphasis']);
        self::assertContains('8 stations', $result->forbiddenHits);
    }

    private function scenario(WorkoutStimulusAuditor $auditor, string $slug): WorkoutStimulusAuditScenario
    {
        foreach ($auditor->scenarios() as $scenario) {
            if ($scenario->slug === $slug) {
                return $scenario;
            }
        }

        self::fail(sprintf('Scenario "%s" was not found.', $slug));
    }

    private function workout(string $name, string $flow, int $timeCap, WorkoutTypeEnum $type): Workout
    {
        return new Workout(
            $name,
            $flow,
            null,
            $timeCap,
            new WorkoutType($type),
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), null)
        );
    }
}
