<?php

namespace App\Tests;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\MovementDifficulty;
use App\Services\Workout\MovementDifficultyServiceInterface;

class MovementDifficultyServiceTest extends AbstractIntegrationTest
{
    private MovementDifficultyServiceInterface $movementDifficultiesService;

    public function setUp(): void
    {
        parent::setUp();
        $this->movementDifficultiesService = $this->getService(MovementDifficultyServiceInterface::class);
    }

    public function testGetWorkoutDifficultiesFromOne(): void
    {
        $allDifficulties = $this->getRepository(MovementDifficulty::class)->findAll();

        foreach ($allDifficulties as $difficulty) {
            $difficulties = $this->movementDifficultiesService->getWorkoutDifficultiesFromOne($difficulty);
            if($difficulty->getNameAsEnum() === MovementDifficultyEnum::ELITE) {
                self::assertCount(4, $difficulties);
            } elseif ($difficulty->getNameAsEnum() === MovementDifficultyEnum::RX) {
                self::assertCount(3, $difficulties);
            } elseif ($difficulty->getNameAsEnum() === MovementDifficultyEnum::INTERMEDIATE) {
                self::assertCount(2, $difficulties);
            } else {
                self::assertCount(1, $difficulties);
            }
        }
    }
}
