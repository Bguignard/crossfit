<?php

namespace App\Api\Graphql\Resolver\Query;

use App\Dto\Workout\WorkoutDTO;
use App\Repository\Workout\WorkoutRepositoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;

// final class WorkoutResolver implements ResolverInterface, AliasedInterface
final class WorkoutResolver implements QueryInterface
{
    public function __construct(
        private readonly WorkoutRepositoryInterface $workoutRepository,
    ) {
    }

    public function __invoke(ResolveInfo $info, WorkoutDTO $workout): mixed
    {
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
    }

    public function resolve(string $id): WorkoutDTO
    {
        $workout = $this->getWorkoutById($id);
        if (null === $workout) {
            throw new \DomainException(sprintf('Workout with id %s not found', $id));
        }

        return $workout;
    }

    public function getWorkoutById(string $id): ?WorkoutDTO
    {
        $workout = $this->workoutRepository->find($id);

        return WorkoutDTO::createFromEntity($workout);
    }

    public function getWorkoutByName(string $name): ?WorkoutDTO
    {
        $workout = $this->workoutRepository->findOneby(['name' => $name]);

        return WorkoutDTO::createFromEntity($workout);
    }
}
