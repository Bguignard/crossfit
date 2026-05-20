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
        self::assertSame('135/95 lb barbell paired', $prescription->loadCandidates[0]->label());
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

    public function testGroupsLbAndKgConversionsAsOneCandidate(): void
    {
        $workout = $this->workout(
            'Age-Group Quarterfinals Workout 1 Men (35-39) Rx',
            'For time: 20 overhead squats, weight 1: 80 lb / 36 kg. Then 30 overhead squats, weight 2: 115 lb / 52 kg.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(4, $prescription->loads);
        self::assertCount(2, $prescription->loadCandidates);
        self::assertSame('80 lb ~= 36 kg barbell conversion', $prescription->loadCandidates[0]->label());
        self::assertSame('115 lb ~= 52 kg barbell conversion', $prescription->loadCandidates[1]->label());
    }

    public function testAddsContextHintsToWeightPositionLoads(): void
    {
        $workout = $this->workout(
            'Workout 5',
            '21 deadlifts, weight 1 (lightest) at 205 lb. 15 deadlifts, weight 2 (heaviest) at 315 lb. Then 15 bar muscle-ups.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(2, $prescription->loads);
        self::assertSame('weight_1', $prescription->loads[0]->positionLabel);
        self::assertSame('Deadlift', $prescription->loads[0]->movementHint);
        self::assertSame('barbell', $prescription->loads[0]->equipmentHint);
        self::assertStringContainsString('weight 1', $prescription->loads[0]->nearText);
        self::assertSame('weight_2', $prescription->loads[1]->positionLabel);
        self::assertSame(['weight_1'], $prescription->loadCandidates[0]->contextHints()['positions']);
        self::assertSame(['Deadlift'], $prescription->loadCandidates[1]->contextHints()['movements']);
    }

    public function testAddsTeamAudienceHintsToLoads(): void
    {
        $workout = $this->workout(
            'Team Workout 4 Team Rx',
            'FF/MM pairs. As many reps as possible in 15 minutes of: 30 deadlifts (FF) at 205-lb barbell, 30 deadlifts (MM) at 315-lb barbell.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(2, $prescription->loads);
        self::assertSame('ff', $prescription->loads[0]->audienceHint);
        self::assertSame('mm', $prescription->loads[1]->audienceHint);
        self::assertSame('Deadlift', $prescription->loads[0]->movementHint);
        self::assertSame(['ff'], $prescription->loadCandidates[0]->contextHints()['audiences']);
        self::assertSame(['mm'], $prescription->loadCandidates[1]->contextHints()['audiences']);
    }

    public function testInfersGenderSymbolsAndGenericWeightPositions(): void
    {
        $workout = $this->workout(
            'Workout 4',
            'Max-reps clean and jerks in time remaining, weight 4. ♀ 165 lb (75 kg) ♂ 245 lb (111 kg).'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(4, $prescription->loads);
        self::assertSame('weight_4', $prescription->loads[0]->positionLabel);
        self::assertSame('women', $prescription->loads[0]->audienceHint);
        self::assertSame('men', $prescription->loads[2]->audienceHint);
        self::assertSame('Clean and Jerk', $prescription->loads[2]->movementHint);
        self::assertSame('barbell', $prescription->loads[3]->equipmentHint);
    }

    public function testPrefersExplicitImplementOverGenericBarbellMovementHint(): void
    {
        $workout = $this->workout(
            'Team Workout 2 Team Rx',
            'Alternating dumbbell snatches (all teammates synchronized). ♀ 50 lb ♀ 22.5 kg dumbbell. ♂ 70 lb ♂ 32.5 kg dumbbell.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(4, $prescription->loads);
        foreach ($prescription->loads as $load) {
            self::assertSame('dumbbell', $load->equipmentHint);
            self::assertSame('Snatch', $load->movementHint);
        }
        self::assertSame('women', $prescription->loads[0]->audienceHint);
        self::assertSame('men', $prescription->loads[2]->audienceHint);
    }

    public function testInfersMedicineBallFromBallTargetContext(): void
    {
        $workout = $this->workout(
            'Team Workout 3 Team Rx',
            '50 synchro wall-ball shots. ♀ 14-lb ball, 9-foot target. ♂ 20-lb ball, 10-foot target.'
        );

        $prescription = (new WorkoutPrescriptionPatternInferer())->infer($workout);

        self::assertCount(2, $prescription->loads);
        self::assertSame('medicine ball', $prescription->loads[0]->equipmentHint);
        self::assertSame('Wall Ball Shot', $prescription->loads[0]->movementHint);
        self::assertSame('women', $prescription->loads[0]->audienceHint);
        self::assertSame('men', $prescription->loads[1]->audienceHint);
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
