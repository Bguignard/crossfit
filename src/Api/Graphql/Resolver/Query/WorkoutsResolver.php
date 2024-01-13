<?php

namespace App\Api\Graphql\Resolver\Query;

use App\Dto\Workout\WorkoutDTO;
use App\Entity\Workout\Workout;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;
use App\Repository\Workout\WorkoutRepositoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;
use Symfony\Component\Runtime\ResolverInterface;
use Symfony\Component\Uid\Uuid;

// final class WorkoutsResolver implements ResolverInterface
final class WorkoutsResolver implements QueryInterface
{
    public function __construct(
        private readonly WorkoutRepositoryInterface $workoutRepository,
        private readonly WorkoutOriginRepositoryInterface $workoutOriginRepository,
    ) {
    }

    public function __invoke(ResolveInfo $info, array $workouts): mixed
    {
        return array_map(function (WorkoutDTO $workout) use ($info) {
            return match ($info->fieldName) {
                'id' => $workout->id,
                'name' => $workout->name,
                'numberOfRounds' => $workout->numberOfRounds,
                'blocks' => $workout->blocks,
                'timeCap' => $workout->timeCap,
                'workoutType' => $workout->workoutType,
                'workoutOrigin' => $workout->workoutOrigin,
                default => throw new \DomainException(sprintf('Unrecognized field %', $info->fieldName)),
            };
        }, $workouts);
    }

    public function resolve(): array
    {
        return array_map(
            fn (Workout $workout) => WorkoutDTO::createFromEntity($workout),
            $this->workoutRepository->findAll()
        );
    }

    /**
     * @return WorkoutDTO[]
     */
    public function getWorkoutsByName(string $name): array
    {
        return array_map(
            fn (Workout $workout) => WorkoutDTO::createFromEntity($workout),
            $this->workoutRepository->getWorkoutsByNameLike($name)
        );
    }

    /**
     * @return WorkoutDTO[]
     */
    public function getAllWorkouts(): array
    {
        return array_map(
            fn (Workout $workout) => WorkoutDTO::createFromEntity($workout),
            $this->workoutRepository->findAll()
        );
    }

    /**
     * @return WorkoutDTO[]
     */
    public function getWorkoutByWorkoutOrigin(string $workoutOriginId): array
    {
        $workoutOrigin = $this->workoutOriginRepository->find(Uuid::fromString($workoutOriginId));

        return array_map(
            fn (Workout $workout) => WorkoutDTO::createFromEntity($workout),
            $this->workoutRepository->findBy(['workoutOrigin' => $workoutOrigin])
        );
    }
}
