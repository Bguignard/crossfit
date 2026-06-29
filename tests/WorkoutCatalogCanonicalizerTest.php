<?php

namespace App\Tests;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use App\Services\Workout\Catalog\WorkoutCatalogCanonicalizer;
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
}
