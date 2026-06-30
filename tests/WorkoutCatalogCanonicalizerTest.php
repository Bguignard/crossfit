<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use App\Services\Workout\Catalog\CanonicalWorkoutCatalogEntry;
use App\Services\Workout\Catalog\WorkoutCatalogCanonicalizer;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class WorkoutCatalogCanonicalizerTest extends TestCase
{
    public function testCanonicalizeMergesFormattingOnlyDuplicatesAndAggregatesProvenance(): void
    {
        $first = $this->workout(
            'Fran',
            "For time:\n21-15-9\nThrusters (95/65 lb)\nPull-Ups",
            sourceName: 'crossfit_games',
            externalId: 'games-fran',
        );
        $second = $this->workout(
            ' fran ',
            "For time:\n\n21 15 9\nThrusters 95/65 lb\nPull Ups",
            sourceName: 'competition_corner',
            externalId: 'corner-fran',
        );

        $entries = (new WorkoutCatalogCanonicalizer())->canonicalize([$first, $second]);

        self::assertCount(1, $entries);
        self::assertSame($first, $entries[0]->representative);
        self::assertSame(2, $entries[0]->occurrenceCount());
        self::assertSame(['competition_corner', 'crossfit_games'], $entries[0]->sourceNames());
        self::assertCount(2, $entries[0]->sourceReferences());
    }

    public function testCanonicalizeDoesNotMergeSameNameWithDifferentContent(): void
    {
        $entries = (new WorkoutCatalogCanonicalizer())->canonicalize([
            $this->workout('Qualifier 1', "For time:\n30 Cleans"),
            $this->workout('Qualifier 1', "For time:\n30 Snatches"),
        ]);

        self::assertCount(2, $entries);
    }

    public function testCanonicalizeUsesImportedFingerprintWhenAvailable(): void
    {
        $first = $this->workout('Open 25.1 RX', "For time:\n10 Cleans")
            ->setCanonicalFingerprint('imported-workout-fingerprint');
        $second = $this->workout('Open 25 1 RX', "For time:\n10 cleans")
            ->setCanonicalFingerprint('imported-workout-fingerprint');

        $entries = (new WorkoutCatalogCanonicalizer())->canonicalize([$first, $second]);

        self::assertCount(1, $entries);
        self::assertSame('imported-workout-fingerprint', $entries[0]->fingerprint);
        self::assertSame(2, $entries[0]->occurrenceCount());
    }

    public function testCanonicalEntryMergesDivisionsForSameCompetitionContext(): void
    {
        $competition = (new Competition('Shared Event Throwdown', 'competition_corner', 'shared-event'))
            ->setSeason(2026);
        $womenEvent = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', 'shared-event-women'))
            ->setEventOrder(1);
        $menEvent = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', 'shared-event-men'))
            ->setEventOrder(1);
        $athlete = new Athlete('Canonical Athlete', 'competition_corner', 'canonical-entry-athlete');
        $womenResult = (new WorkoutResult($athlete, $womenEvent, new Score(ScoreTypeEnum::TIME, '3:01'), 'competition_corner', 'shared-event-women-result'))
            ->setDivision('Elite Women');
        $menResult = (new WorkoutResult($athlete, $menEvent, new Score(ScoreTypeEnum::TIME, '2:59'), 'competition_corner', 'shared-event-men-result'))
            ->setDivision('Elite Men');
        $womenEvent->getResults()->add($womenResult);
        $menEvent->getResults()->add($menResult);

        $womenWorkout = $this->workout('Shared Event', "For time:\n10 Burpees");
        $menWorkout = $this->workout('Shared Event', "For time:\n10 Burpees");
        $this->attachCompetitionEvents($womenWorkout, [$womenEvent]);
        $this->attachCompetitionEvents($menWorkout, [$menEvent]);

        $contexts = (new CanonicalWorkoutCatalogEntry('fingerprint', $womenWorkout, [$womenWorkout, $menWorkout]))
            ->competitionContexts();

        self::assertCount(1, $contexts);
        self::assertSame('Shared Event Throwdown', $contexts[0]['competitionName']);
        self::assertSame(['Elite Men', 'Elite Women'], $contexts[0]['divisions']);
    }

    public function testCanonicalEntryMergesEventProvenancesForSameCompetitionContext(): void
    {
        $competition = (new Competition('Shared Event Throwdown', 'competition_corner', 'shared-event'))
            ->setSeason(2026);
        $womenEvent = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', 'shared-event-women'))
            ->setEventOrder(1)
            ->setProvenances([
                ['sourceWorkoutId' => 'workout-1', 'division' => 'Elite Women'],
            ]);
        $menEvent = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', 'shared-event-men'))
            ->setEventOrder(1)
            ->setProvenances([
                ['sourceWorkoutId' => 'workout-1', 'division' => 'Elite Men'],
            ]);

        $womenWorkout = $this->workout('Shared Event', "For time:\n10 Burpees");
        $menWorkout = $this->workout('Shared Event', "For time:\n10 Burpees");
        $this->attachCompetitionEvents($womenWorkout, [$womenEvent]);
        $this->attachCompetitionEvents($menWorkout, [$menEvent]);

        $contexts = (new CanonicalWorkoutCatalogEntry('fingerprint', $womenWorkout, [$womenWorkout, $menWorkout]))
            ->competitionContexts();

        self::assertCount(1, $contexts);
        self::assertSame([
            ['sourceWorkoutId' => 'workout-1', 'division' => 'Elite Women'],
            ['sourceWorkoutId' => 'workout-1', 'division' => 'Elite Men'],
        ], $contexts[0]['provenances']);
    }

    private function workout(
        string $name,
        string $flow,
        ?string $sourceName = null,
        ?string $externalId = null,
    ): Workout {
        return (new Workout(
            $name,
            $flow,
            1,
            10,
            new WorkoutType(WorkoutTypeEnum::FOR_TIME),
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026),
        ))
            ->setSourceName($sourceName)
            ->setExternalId($externalId);
    }

    /**
     * @param list<CompetitionEvent> $events
     */
    private function attachCompetitionEvents(Workout $workout, array $events): void
    {
        $property = new \ReflectionProperty(Workout::class, 'competitionEvents');
        $property->setValue($workout, new ArrayCollection($events));
    }
}
