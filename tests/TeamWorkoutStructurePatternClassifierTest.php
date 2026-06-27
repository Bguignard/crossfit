<?php

namespace App\Tests;

use App\Services\Workout\Audit\TeamWorkoutStructurePatternClassifier;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class TeamWorkoutStructurePatternClassifierTest extends TestCase
{
    public function testDetectsSynchroAndSharedMix(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            "Team of 2\nComplete 180 total wall balls as a team, split reps anyhow.\nEvery 20 reps: 8 synchronized burpees."
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SYNCHRONIZED, $detection['patterns']);
        self::assertContains(TeamWorkoutStructurePatternClassifier::SPLIT_ANYHOW, $detection['patterns']);
        self::assertContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
        self::assertContains('team_of_2', $detection['teamSizes']);
    }

    public function testDetectsShortYouGoIGoRelay(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Pairs: you-go-I-go relay, alternate every 8 calories on the rower before tagging your partner.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::YOU_GO_I_GO, $detection['patterns']);
        self::assertContains(TeamWorkoutStructurePatternClassifier::RELAY, $detection['patterns']);
        self::assertContains('team_of_2', $detection['teamSizes']);
    }

    public function testDetectsSharedTotalCaloriesAndReps(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'In teams of 3, complete as a team: 240 cals bike, 180 total reps, 120 box jumps.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
        self::assertContains('team_of_3', $detection['teamSizes']);
    }

    public function testDetectsActiveHoldWhilePartnerWorks(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'One partner holds a sandbag bear hug while the other athlete completes 12 burpees, then switch.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::ACTIVE_HOLD_CONSTRAINT, $detection['patterns']);
    }

    public function testDetectsPartnerAlternatingRounds(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 4: partners alternate rounds of 10 deadlifts and 12 toes-to-bar.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::PARTNER_ALTERNATING_ROUNDS, $detection['patterns']);
        self::assertContains(TeamWorkoutStructurePatternClassifier::YOU_GO_I_GO, $detection['patterns']);
        self::assertContains('team_of_4', $detection['teamSizes']);
    }

    public function testDoesNotTreatAlternatingMovementNamesAsYouGoIGo(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2, AMRAP 12: 20 alternating dumbbell snatches, 15 box jumps, 200 m run.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::YOU_GO_I_GO, $detection['patterns']);
    }
}
