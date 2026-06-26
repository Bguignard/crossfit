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
    }

    public function testTeamWorkoutGuidanceProvidesTaxonomyAndShortRelayConstraint(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(true)
        );

        self::assertStringContainsString('Team structure taxonomy available for this generation', $guidance);
        self::assertStringContainsString('synchronized block', $guidance);
        self::assertStringContainsString('shared total reps/calories, split anyhow', $guidance);
        self::assertStringContainsString('short "you go, I go" switches', $guidance);
        self::assertStringContainsString('partner alternating rounds', $guidance);
        self::assertStringContainsString('relay stations', $guidance);
        self::assertStringContainsString('mixed synchro + shared work', $guidance);
        self::assertStringContainsString('active hold/carry/static constraint while partner works', $guidance);
        self::assertStringContainsString('Pick exactly one main structure', $guidance);
        self::assertStringContainsString('Do not prescribe long row/run segments, full long stations, large unbroken sets or whole long rounds as "you go, I go"', $guidance);
        self::assertStringContainsString('If a station is long, split it into short distance/repetition/calorie switches', $guidance);
        self::assertStringContainsString('For machines, ergs and calories, state explicitly whether athletes share one machine', $guidance);
    }

    public function testTeamWorkoutVariantGuidanceDoesNotAskForFinalFlow(): void
    {
        $guidance = (new TeamWorkoutStructureGuidanceProvider())->buildVariantPromptGuidance(
            (new WorkoutGeneration())->setIsTeamWorkout(true)
        );

        self::assertStringContainsString('Team workout concept guidance', $guidance);
        self::assertStringContainsString('Do not write the final workout flow yet', $guidance);
        self::assertStringContainsString('describe the chosen team structure in the concept intent, format or summary', $guidance);
        self::assertStringContainsString('Central "you go, I go" constraint for concepts: short relays only', $guidance);
        self::assertStringNotContainsString('then write the flow so the work-sharing rule is impossible to miss', $guidance);
    }
}
