<?php

namespace App\Tests;

use App\DataFixtures\BodyPartData;
use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Muscle;
use App\Services\Workout\MuscleServiceInterface;

class MuscleServiceTest extends AbstractIntegrationTest
{
    private MuscleServiceInterface $muscleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->muscleService = $this->getService(MuscleServiceInterface::class);
    }

    public function testGetMusclesFromBodyPartsReturnsUniqueMuscles(): void
    {
        // Use a couple of body parts from fixtures
        /** @var BodyPart $legs */
        $legs = $this->getReference(BodyPartData::BODY_PART_LEGS, BodyPart::class);
        /** @var BodyPart $shoulders */
        $shoulders = $this->getReference(BodyPartData::BODY_PART_SHOULDERS, BodyPart::class);

        $muscles = $this->muscleService->getMusclesFromBodyParts([$legs, $shoulders]);

        self::assertNotEmpty($muscles, 'Muscles should not be empty for known body parts');
        self::assertContainsOnlyInstancesOf(Muscle::class, $muscles);

        // Ensure they are unique by id
        $ids = array_map(static fn(Muscle $m) => $m->getId()->toString(), $muscles);
        self::assertSameSize(array_unique($ids), $ids);
    }
}
