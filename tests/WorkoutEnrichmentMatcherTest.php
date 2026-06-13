<?php

namespace App\Tests;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Services\Workout\Enrichment\WorkoutEnrichmentMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WorkoutEnrichmentMatcherTest extends TestCase
{
    public function testItMatchesAbmatImplementAliases(): void
    {
        $workout = new Workout(
            'AbMat test',
            "For time:\n50 abmat sit-ups",
            null,
            10,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), 2026),
        );
        $abmat = new Implement(ImplementEnum::ABMAT, null);
        $this->setEntityId($abmat);

        $match = (new WorkoutEnrichmentMatcher())->match($workout, [], [$abmat]);

        self::assertCount(1, $match->implements);
        self::assertSame('abmat', $match->implements[0]->getName());
    }

    private function setEntityId(object $entity): void
    {
        $id = new \ReflectionProperty($entity, 'id');
        $id->setValue($entity, Uuid::v4());
    }
}
