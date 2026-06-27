<?php

namespace App\Tests;

use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\TeamWorkoutStructureGuidanceProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class TeamWorkoutStructureGuidanceProviderTest extends TestCase
{
    public function testIndividualWorkoutGuidanceForbidsTeamStructures(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(false)
        );

        self::assertStringContainsString('Team workout guidance: this is an individual workout', $guidance);
        self::assertStringContainsString('Do not use partner relay, shared reps, split-anyhow rules, synchronized work', $guidance);
        self::assertStringContainsString('partner holds/carries/static constraints', $guidance);
        self::assertStringNotContainsString('synchronized work, holds, carries', $guidance);
        self::assertStringNotContainsString('Audit-informed team structure weighting', $guidance);
        self::assertStringNotContainsString('Alternating work / short relay', $guidance);
        self::assertStringNotContainsString('Use team of 2 by default', $guidance);
    }

    public function testTeamWorkoutGuidanceProvidesAuditInformedWeightingAndShortRelayConstraint(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(true)
        );

        self::assertStringContainsString('Audit-informed team structure weighting for this generation', $guidance);
        self::assertStringContainsString('major structure: synchronized / mixed synchro', $guidance);
        self::assertStringContainsString('regular structures: shared total reps/calories; split anyhow; alternating work / short relay', $guidance);
        self::assertStringContainsString('occasional structure: active hold/carry/static constraint while partner works', $guidance);
        self::assertStringContainsString('Alternating work / short relay includes "you go, I go", relay stations and partner alternating rounds', $guidance);
        self::assertStringNotContainsString('Available team concept structures', $guidance);
        self::assertStringContainsString('active hold/carry/static constraint while partner works', $guidance);
        self::assertStringContainsString('Pick exactly one main structure', $guidance);
        self::assertStringContainsString('Prefer short relays in small sets, small calorie chunks, short distances or one compact movement at a time', $guidance);
        self::assertStringContainsString('Whole-round alternating is allowed only when each round is very compact', $guidance);
        self::assertStringContainsString('do not prescribe long row/run segments, full long stations, large unbroken sets or whole long rounds', $guidance);
        self::assertStringContainsString('If a station is long, split it into short distance/repetition/calorie switches', $guidance);
        self::assertStringContainsString('For machines, ergs and calories, state explicitly whether athletes share one machine', $guidance);
    }

    public function testTeamWorkoutGuidanceRestrictsStandardTeamSize(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(true)
        );

        self::assertStringContainsString('Use team of 2 by default', $guidance);
        self::assertStringContainsString('Use team of 3 only when the stimulus, format or logistics clearly justify it', $guidance);
        self::assertStringContainsString('Do not create team sizes above 3 for standard MonWOD generation', $guidance);
    }

    public function testTeamWorkoutVariantGuidanceDoesNotAskForFinalFlow(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildVariantPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(true)
        );

        self::assertStringContainsString('Team workout concept guidance', $guidance);
        self::assertStringContainsString('Do not write the final workout flow yet', $guidance);
        self::assertStringContainsString('describe the chosen team structure in the concept intent, format or summary', $guidance);
        self::assertStringContainsString('Audit-informed team concept weighting', $guidance);
        self::assertStringContainsString('major structure = synchronized / mixed synchro', $guidance);
        self::assertStringContainsString('regular structures = shared total reps/calories, split anyhow, alternating work / short relay', $guidance);
        self::assertStringContainsString('Alternating work / short relay covers "you go, I go", relay stations and partner alternating rounds', $guidance);
        self::assertStringContainsString('Use team of 2 by default; use team of 3 only when justified; do not create team sizes above 3', $guidance);
        self::assertStringNotContainsString('then write the flow so the work-sharing rule is impossible to miss', $guidance);
    }
}
