<?php

declare(strict_types=1);

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Workout\Workout;
use App\Services\Workout\Catalog\PublicWorkoutCatalogVisibility;
use Doctrine\ORM\QueryBuilder;

final readonly class PublicWorkoutCatalogVisibilityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private PublicWorkoutCatalogVisibility $visibility)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Workout::class) {
            return;
        }

        $this->visibility->applyPublicConstraint(
            $queryBuilder,
            $queryBuilder->getRootAliases()[0],
            $queryNameGenerator->generateJoinAlias('publicWorkoutGeneration'),
            $queryNameGenerator->generateParameterName('publicWorkout'),
        );
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Workout::class) {
            return;
        }

        $this->visibility->applyPublicConstraint(
            $queryBuilder,
            $queryBuilder->getRootAliases()[0],
            $queryNameGenerator->generateJoinAlias('publicWorkoutGeneration'),
            $queryNameGenerator->generateParameterName('publicWorkout'),
        );
    }
}
