<?php

declare(strict_types=1);

namespace App\Services\Workout\Catalog;

use Doctrine\ORM\QueryBuilder;

final class PublicWorkoutCatalogVisibility
{
    private const PRIVATE_SOURCE_NAMES = [
        'audit',
        'diagnostic',
        'monwod_audit',
        'monwod_diagnostic',
        'monwod_internal',
        'monwod_sample',
        'monwod_test',
        'sample',
        'test',
    ];

    private const INTERNAL_GENERATION_MARKERS = [
        '%audit%',
        '%diagnostic%',
        '%sample%',
        '%test%',
    ];

    public function applyPublicConstraint(
        QueryBuilder $queryBuilder,
        string $workoutAlias,
        string $generationAlias = 'publicWorkoutGeneration',
        string $parameterPrefix = 'publicWorkout',
    ): void {
        $queryBuilder
            ->leftJoin(sprintf('%s.workoutGeneration', $workoutAlias), $generationAlias)
            ->andWhere(sprintf('(%s.sourceName IS NULL OR LOWER(%s.sourceName) NOT IN (:%sPrivateSourceNames))', $workoutAlias, $workoutAlias, $parameterPrefix))
            ->setParameter(sprintf('%sPrivateSourceNames', $parameterPrefix), self::PRIVATE_SOURCE_NAMES);

        $markerConditions = [];
        foreach (self::INTERNAL_GENERATION_MARKERS as $index => $marker) {
            $parameterName = sprintf('%sInternalMarker%d', $parameterPrefix, $index);
            $markerConditions[] = sprintf('LOWER(%s.name) NOT LIKE :%s', $workoutAlias, $parameterName);
            $markerConditions[] = sprintf('LOWER(%s.name) NOT LIKE :%s', $generationAlias, $parameterName);
            $queryBuilder->setParameter($parameterName, $marker);
        }

        $queryBuilder->andWhere(sprintf(
            '(%s.id IS NULL OR (%s))',
            $generationAlias,
            implode(' AND ', $markerConditions),
        ));
    }
}
