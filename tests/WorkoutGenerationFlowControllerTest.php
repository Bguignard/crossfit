<?php

namespace App\Tests;

use App\Controller\WorkoutGeneration\WorkoutGenerationFlowController;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementDifficultyRepositoryInterface;
use App\Repository\Workout\MovementRepository;
use App\Repository\WorkoutGeneration\WorkoutAiGenerationUsageRepository;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationActor;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationActorResolver;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationQuotaPolicy;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationUsageTracker;
use App\Services\Workout\MovementDifficultyService;
use App\Services\Workout\WorkoutCreatorServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @group unit
 */
class WorkoutGenerationFlowControllerTest extends TestCase
{
    public function testStimulusContextCanonicalizesClientAliases(): void
    {
        $controller = $this->controller();
        $method = new \ReflectionMethod($controller, 'stimulusContext');

        self::assertSame([
            'normalized' => 'force endurance',
            'family' => 'strength_endurance',
            'canonical' => 'Strength Endurance',
            'supported' => true,
        ], $method->invoke($controller, 'Force endurance'));
        self::assertSame([
            'normalized' => 'seuil threshold',
            'family' => 'threshold',
            'canonical' => null,
            'supported' => true,
        ], $method->invoke($controller, 'Seuil/Threshold'));
        self::assertSame([
            'normalized' => 'seuil',
            'family' => 'threshold',
            'canonical' => 'Threshold',
            'supported' => true,
        ], $method->invoke($controller, 'Seuil'));
        self::assertSame([
            'normalized' => 'complet competition',
            'family' => 'competition',
            'canonical' => null,
            'supported' => true,
        ], $method->invoke($controller, 'Complet/Competition'));
        self::assertSame([
            'normalized' => 'mixed modal',
            'family' => 'competition',
            'canonical' => 'Mixed Modal / Competition',
            'supported' => true,
        ], $method->invoke($controller, 'Mixed Modal'));
    }

    public function testWorkoutGenerationLogContextToleratesMissingCatalogRelations(): void
    {
        $controller = $this->controller();
        $draft = (new WorkoutGeneration())
            ->setName('Legacy incomplete draft')
            ->setStimulus('Force')
            ->setTimeCap(12)
            ->setNumberOfDifferentMovements(2)
            ->setIsTeamWorkout(false);
        $actor = new WorkoutAiGenerationActor(null, WorkoutAiGenerationUsage::ACTOR_ANONYMOUS, 'visitor-hash');

        $method = new \ReflectionMethod($controller, 'workoutGenerationLogContext');
        $context = $method->invoke($controller, $draft, $actor, 'workout:strength', [
            'normalized' => 'force',
            'family' => 'strength',
            'canonical' => 'Strength',
            'supported' => true,
        ]);

        self::assertSame('Force', $context['stimulus']);
        self::assertSame('force', $context['normalizedStimulus']);
        self::assertSame('strength', $context['stimulusFamily']);
        self::assertSame('Strength', $context['canonicalStimulus']);
        self::assertNull($context['workoutType']);
        self::assertNull($context['level']);
        self::assertSame(2, $context['movementCount']);
        self::assertSame(12, $context['timeCap']);
        self::assertFalse($context['isTeamWorkout']);
    }

    private function controller(): WorkoutGenerationFlowController
    {
        return new WorkoutGenerationFlowController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MovementRepository::class),
            new MovementDifficultyService($this->createMock(MovementDifficultyRepositoryInterface::class)),
            $this->createMock(WorkoutCreatorServiceInterface::class),
            new WorkoutAiGenerationActorResolver('test-secret'),
            new WorkoutAiGenerationUsageTracker(
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(WorkoutAiGenerationUsageRepository::class),
                new WorkoutAiGenerationQuotaPolicy(5, 10),
                'UTC',
            ),
            $this->createMock(LoggerInterface::class),
        );
    }
}
