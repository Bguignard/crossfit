<?php

namespace App\Tests;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Services\Workout\MovementGeneratorServiceInterface;

class MovementGeneratorServiceTest extends AbstractIntegrationTest
{
    private MovementGeneratorServiceInterface $movementGeneratorService;

    public function setUp(): void
    {
        parent::setUp();
        $this->movementGeneratorService = $this->getService(MovementGeneratorServiceInterface::class);
    }

    public function testGenerateMovementWithAllParameters(): void
    {
        $availableImplements = $this->getRepository(Implement::class)->findBy(['name' => [ImplementEnum::PULL_UP_BAR->name, ImplementEnum::BARBELL->name]]);
        $forbiddenMovements = $this->getRepository(Movement::class)->findBy(['name' => 'Pull Up']);
        $maxDifficulty = 80;
        $movementType = MovementTypeEnum::GYMNASTIC;

        $movement = $this->movementGeneratorService->generateMovement(
            $availableImplements,
            $maxDifficulty,
            $forbiddenMovements,
            $movementType
        );

        self::assertNotNull($movement);
        self::assertNotSame('Pull Up', $movement->getName());
        self::assertSame(MovementTypeEnum::GYMNASTIC, $movement->getMovementType());
    }
}
