<?php

namespace App\Tests;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Services\Workout\Audit\WorkoutGenerationBenchmarkLiveRunner;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use App\Services\Workout\MovementServiceInterface;
use App\Services\Workout\WorkoutOriginServiceInterface;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @group unit
 */
final class WorkoutGenerationBenchmarkLiveRunnerTest extends TestCase
{
    public function testLiveRunnerPopulatesAvailableImplementsFromCatalog(): void
    {
        $movementService = new class implements MovementServiceInterface {
            public int $maxAvailableImplementCount = 0;

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                $this->maxAvailableImplementCount = max($this->maxAvailableImplementCount, $possibleImplements->count());

                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };
        $runner = $this->runner(
            implements: [new Implement(ImplementEnum::BARBELL, null)],
            movementService: $movementService,
        );

        $entry = $runner->run('gpt-live-test', 'full_ai', (new WorkoutStimulusAuditor())->scenarios()[0]);

        self::assertSame(1, $movementService->maxAvailableImplementCount);
        self::assertSame('error', $entry['status']);
    }

    public function testLiveRunnerCapturesUsageWhenGeneratedResponseIsRejected(): void
    {
        $movement = new Movement(
            'Deadlift',
            new MovementDifficulty(MovementDifficultyEnum::RX),
            new MovementType(MovementTypeEnum::WEIGHTLIFTING),
        );
        $movementService = new class($movement) implements MovementServiceInterface {
            public function __construct(private readonly Movement $movement)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [$this->movement];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [$this->movement];
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [$this->movement];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [$this->movement];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [$this->movement];
            }
        };
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
                'model' => 'gpt-live-test-2026-07-01',
                'output_text' => json_encode([
                    'flow' => 'For time: 10 Unknown Movement',
                    'scalingOptions' => 'RX: as written. Intermediate: reduce volume. Scaled: reduce volume.',
                    'movements' => ['Unknown Movement'],
                ], JSON_THROW_ON_ERROR),
                'usage' => [
                    'input_tokens' => 1234,
                    'output_tokens' => 321,
                    'total_tokens' => 1555,
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $runner = $this->runner(
            implements: [],
            movementService: $movementService,
            httpClient: $httpClient,
        );

        $entry = $runner->run('gpt-live-test', 'full_ai', (new WorkoutStimulusAuditor())->scenarios()[0]);

        self::assertSame('error', $entry['status']);
        self::assertSame(1234, $entry['tokenUsage']['promptTokens']);
        self::assertSame(321, $entry['tokenUsage']['completionTokens']);
        self::assertSame(1555, $entry['tokenUsage']['totalTokens']);
        self::assertIsInt($entry['durationMs']);
    }

    /**
     * @param list<Implement> $implements
     */
    private function runner(
        array $implements,
        MovementServiceInterface $movementService,
        ?MockHttpClient $httpClient = null,
    ): WorkoutGenerationBenchmarkLiveRunner {
        return new WorkoutGenerationBenchmarkLiveRunner(
            $movementService,
            new class implements WorkoutOriginServiceInterface {
                public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
                {
                    throw new \RuntimeException('Workout origin should not be needed in these tests.');
                }
            },
            new WorkoutStimulusAuditor(),
            new class($implements) implements ImplementRepositoryInterface {
                /**
                 * @param list<Implement> $implements
                 */
                public function __construct(private readonly array $implements)
                {
                }

                public function findAll(): array
                {
                    return $this->implements;
                }
            },
            $httpClient ?? new MockHttpClient([]),
            'test-key',
        );
    }
}
