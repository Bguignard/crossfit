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

    public function testDoesNotTreatSharedEquipmentAsSplitAnyhow(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Partners share one barbell and work synchronized for 10 rounds.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SYNCHRONIZED, $detection['patterns']);
        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SPLIT_ANYHOW, $detection['patterns']);
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

    public function testDetectsNormalizedHandOffRelayCue(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2: hand-off every 100 m between stations.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::RELAY, $detection['patterns']);
    }

    public function testDetectsSharedTotalCaloriesAndReps(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'In teams of 3, complete as a team: 240 cals bike, 180 total reps, 120 box jumps.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
        self::assertContains('team_of_3', $detection['teamSizes']);
    }

    public function testDetectsSharedTotalWithTrailingTeamWording(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2: complete 240 calories as a team, split reps anyhow.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDetectsSharedTotalWithTrailingTotalWording(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2: complete 100 reps total before moving to the next station.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDoesNotTreatBareAsATeamAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2, synchronized burpees as a team, then switch every minute on the bike.'
        );

        self::assertContains(TeamWorkoutStructurePatternClassifier::SYNCHRONIZED, $detection['patterns']);
        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDoesNotTreatPerAthleteCompletionAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Both athletes complete 10 reps each before switching stations.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDoesNotTreatPerAthleteRoundTotalAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2: complete 10 reps each for 5 rounds total.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDoesNotTreatEachAthleteTotalAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Each athlete must complete 100 reps total before advancing.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDoesNotTreatBothAthletesTotalAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Both athletes complete 50 calories total on the bike.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
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

    public function testDoesNotTreatAlternatingMovementNamesPerRoundAsYouGoIGo(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2, 5 rounds: 20 alternating lunges per round, 15 box jumps, 200 m run.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::YOU_GO_I_GO, $detection['patterns']);
        self::assertNotContains(TeamWorkoutStructurePatternClassifier::PARTNER_ALTERNATING_ROUNDS, $detection['patterns']);
    }

    public function testDoesNotTreatTotalRoundsAsSharedTotal(): void
    {
        $detection = (new TeamWorkoutStructurePatternClassifier())->classify(
            'Team of 2: 3 total rounds, both athletes complete 10 reps each.'
        );

        self::assertNotContains(TeamWorkoutStructurePatternClassifier::SHARED_TOTAL, $detection['patterns']);
    }

    public function testDetectsWrittenTeamSizes(): void
    {
        $classifier = new TeamWorkoutStructurePatternClassifier();

        self::assertContains('team_of_2', $classifier->classify('Teams of two, split the work anyhow.')['teamSizes']);
        self::assertContains('team_of_3', $classifier->classify('Team of three, complete as a team.')['teamSizes']);
        self::assertContains('team_of_4', $classifier->classify('Team of four relay stations.')['teamSizes']);
        self::assertContains('team_of_5', $classifier->classify('Team of 5, split the reps anyhow.')['teamSizes']);
        self::assertContains('team_of_6', $classifier->classify('Teams of six, relay stations.')['teamSizes']);
    }
}
