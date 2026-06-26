<?php

namespace App\Tests;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\MovementInteractionStrategyProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class MovementInteractionStrategyProviderTest extends TestCase
{
    public function testSelectionIsStableForSameWorkoutGeneration(): void
    {
        $provider = new MovementInteractionStrategyProvider();
        $workoutGeneration = $this->workoutGeneration('Competition');

        self::assertSame(
            $provider->selectStrategy($workoutGeneration),
            $provider->selectStrategy($workoutGeneration),
        );
    }

    public function testStrategiesAreCompatibleByStimulus(): void
    {
        $provider = new MovementInteractionStrategyProvider();

        self::assertSame(
            ['engine_priority', 'engine_priority', 'complementary_fast'],
            $provider->compatibleStrategies($this->workoutGeneration('Engine')),
        );
        self::assertSame(
            ['same_limiter', 'same_limiter', 'targeted_prefatigue'],
            $provider->compatibleStrategies($this->workoutGeneration('Strength Endurance')),
        );
        self::assertSame(
            ['skill_under_fatigue', 'skill_under_fatigue', 'antagonistic_flow'],
            $provider->compatibleStrategies($this->workoutGeneration('Gymnastics / Skill')),
        );
        self::assertContains('skill_under_fatigue', $provider->compatibleStrategies($this->workoutGeneration('Competition')));
        self::assertContains('same_limiter', $provider->compatibleStrategies($this->workoutGeneration('Competition')));
        self::assertContains('complementary_fast', $provider->compatibleStrategies($this->workoutGeneration('Metcon')));
        self::assertContains('antagonistic_flow', $provider->compatibleStrategies($this->workoutGeneration('Metcon')));
    }

    public function testEngineSelectionAvoidsAggressiveGripOrSkillStrategies(): void
    {
        $provider = new MovementInteractionStrategyProvider();

        for ($index = 0; $index < 20; ++$index) {
            $strategy = $provider->selectStrategy(
                $this->workoutGeneration('Engine', sprintf('Engine sample %d', $index))
            );

            self::assertContains($strategy, ['engine_priority', 'complementary_fast']);
            self::assertNotSame('skill_under_fatigue', $strategy);
            self::assertNotSame('same_limiter', $strategy);
        }
    }

    public function testPromptGuidanceDescribesInvisibleMovementInteraction(): void
    {
        $provider = new MovementInteractionStrategyProvider();
        $guidance = $provider->buildPromptGuidance($this->workoutGeneration('Engine'));

        self::assertStringContainsString('Movement interaction strategy guidance', $guidance);
        self::assertStringContainsString('internal strategy', $guidance);
        self::assertStringContainsString('invisible to the athlete', $guidance);
        self::assertTrue(
            str_contains($guidance, 'breathing and pacing the main limiter')
            || str_contains($guidance, 'interfere little with each other')
        );
    }

    public function testOneMovementWorkoutDoesNotEmitInteractionGuidance(): void
    {
        $provider = new MovementInteractionStrategyProvider();

        self::assertSame(
            '',
            $provider->buildPromptGuidance($this->workoutGeneration('Strength', 'Single lift')->setNumberOfDifferentMovements(1)),
        );
        self::assertSame(
            '',
            $provider->buildPromptGuidance($this->workoutGeneration('Strength', 'Single lift variant')->setNumberOfDifferentMovements(1), true),
        );
    }

    private function workoutGeneration(string $stimulus, string $name = 'Strategy test'): WorkoutGeneration
    {
        return (new WorkoutGeneration())
            ->setName($name)
            ->setStimulus($stimulus)
            ->setStimulusIntent('Intentional movement interaction.')
            ->setTimeCap(14)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty(new MovementDifficulty(MovementDifficultyEnum::RX))
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);
    }
}
