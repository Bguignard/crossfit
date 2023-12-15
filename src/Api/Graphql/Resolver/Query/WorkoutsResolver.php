<?php

namespace App\Api\Graphql\Resolver\Query;

use App\Dto\Workout\WorkoutDTO;
use App\Repository\Workout\WorkoutRepositoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;
use Symfony\Component\Runtime\ResolverInterface;

// final class WorkoutsResolver implements ResolverInterface
final class WorkoutsResolver implements QueryInterface
{
    public function __construct(
        private WorkoutRepositoryInterface $workoutRepository,
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
        $workoutDtos = [];
        foreach ($this->workoutRepository->findAll() as $workoutEntity) {
            $workoutDtos[] = WorkoutDTO::createFromEntity($workoutEntity);
        }

        return $workoutDtos;
    }

    public function getWorkoutById(string $id): WorkoutDTO
    {
        $workout = $this->workoutRepository->find($id);

        return WorkoutDTO::createFromEntity($workout);
    }

    public function getOneWorkoutByName(string $name): WorkoutDTO
    {
        $workout = $this->workoutRepository->findOneBy(['name' => $name]);

        return WorkoutDTO::createFromEntity($workout);
    }

    /**
     * @return WorkoutDTO[]
     */
    public function getAllWorkouts(): array
    {
        $workoutDtos = [];
        foreach ($this->workoutRepository->findAll() as $workoutEntity) {
            $workoutDtos[] = WorkoutDTO::createFromEntity($workoutEntity);
        }

        return $workoutDtos;
    }
}
