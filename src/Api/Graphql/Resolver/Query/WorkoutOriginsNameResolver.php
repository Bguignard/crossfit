<?php

namespace App\Api\Graphql\Resolver\Query;

use App\Enum\WorkoutOriginNameEnum;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;

final class WorkoutOriginsNameResolver implements QueryInterface
{
    public function __invoke(ResolveInfo $info, array $workoutOriginsNames): mixed
    {
        return array_map(function (string $workoutOriginsName) use ($info) {
            return match ($info->fieldName) {
                'name' => $workoutOriginsName,
                default => throw new \DomainException(sprintf('Unrecognized field %', $info->fieldName)),
            };
        }, $workoutOriginsNames);
    }

    public function resolve(): array
    {
        return array_column(WorkoutOriginNameEnum::cases(), 'value');
    }
}
