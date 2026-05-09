<?php

namespace App\Tests;

use App\DataFixtures\WorkoutOriginNameData;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Services\Workout\WorkoutOriginServiceInterface;

class WorkoutOriginServiceTest extends AbstractIntegrationTest
{
    private WorkoutOriginServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(WorkoutOriginServiceInterface::class);
    }

    public function testGetExistingReturnsExistingOrigin(): void
    {
        /** @var WorkoutOriginName $girlsName */
        $girlsName = $this->getReference(WorkoutOriginNameData::WORKOUT_ORIGIN_NAME_GIRLS_WORKOUT, WorkoutOriginName::class);

        // Create an origin with a given year
        $existing = new WorkoutOrigin($girlsName, 2020);
        $em = $this->getEntityManager();
        $em->persist($existing);
        $em->flush();

        $returned = $this->service->getExistingOrInsertNewWorkoutOrigin($girlsName->getName(), 2020);

        self::assertSame($existing->getId()->toString(), $returned->getId()->toString());
    }

    public function testInsertNewWhenNotExistsCreatesCustomOrigin(): void
    {
        $year = 2024;
        $returned = $this->service->getExistingOrInsertNewWorkoutOrigin('NON_EXISTING_ORIGIN', $year);

        self::assertInstanceOf(WorkoutOrigin::class, $returned);
        self::assertSame($year, $returned->getYear());
        // The service creates a WorkoutOrigin with CUSTOM name when not found
        self::assertSame('Custom', $returned->getName()->getName());
    }
}
