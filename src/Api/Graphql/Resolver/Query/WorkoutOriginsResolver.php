<?php

namespace App\Api\Graphql\Resolver\Query;

use App\Dto\Workout\WorkoutOriginDTO;
use App\Entity\Workout\WorkoutOrigin;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;

final class WorkoutOriginsResolver implements QueryInterface
{
    public function __construct(
        private readonly WorkoutOriginRepositoryInterface $workoutOriginRepository,
    ) {
    }

    public function __invoke(ResolveInfo $info, array $workoutOrigins): mixed
    {
        return array_map(function (WorkoutOriginDTO $workoutOriginDTO) use ($info) {
            return match ($info->fieldName) {
                'id' => $workoutOriginDTO->id,
                'name' => $workoutOriginDTO->name,
                'year' => $workoutOriginDTO->numberOfRounds,
                default => throw new \DomainException(sprintf('Unrecognized field %', $info->fieldName)),
            };
        }, $workoutOrigins);
    }

    /**
     * @return WorkoutOriginDTO[]
     */
    public function getWorkoutOriginsByName(string $workoutOriginName): array
    {
        return array_map(
            fn (WorkoutOrigin $workoutOrigin) => WorkoutOriginDTO::createFromEntity($workoutOrigin),
            $this->workoutOriginRepository->findBy(['name' => $workoutOriginName])
        );
    }
}
