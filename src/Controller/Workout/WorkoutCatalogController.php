<?php

declare(strict_types=1);

namespace App\Controller\Workout;

use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Services\Workout\Catalog\CanonicalWorkoutCatalogEntry;
use App\Services\Workout\Catalog\PublicWorkoutCatalogVisibility;
use App\Services\Workout\Catalog\WorkoutCatalogCanonicalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WorkoutCatalogController extends AbstractController
{
    private const DEFAULT_PAGE_SIZE = 25;
    private const MAX_PAGE_SIZE = 50;
    private const CANONICAL_SCAN_BATCH_SIZE = 200;
    private const AMBIGUOUS_FLOW_FALLBACK_MOVEMENTS = [
        'clean',
        'jerk',
        'press',
        'snatch',
        'squat',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkoutCatalogCanonicalizer $canonicalizer,
        private readonly PublicWorkoutCatalogVisibility $publicWorkoutCatalogVisibility,
    ) {
    }

    #[Route('/api/workout-catalog/random-generated', name: 'workout_catalog_random_generated', methods: ['GET'])]
    public function randomGenerated(): JsonResponse
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout')
            ->andWhere('workout.workoutGeneration IS NOT NULL');
        $this->publicWorkoutCatalogVisibility->applyPublicConstraint($queryBuilder, 'workout', 'randomGeneratedWorkoutGeneration', 'randomGeneratedWorkout');

        $totalItems = (int) (clone $queryBuilder)
            ->select('COUNT(workout.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($totalItems === 0) {
            return $this->json(['error' => 'No generated workout available.'], Response::HTTP_NOT_FOUND);
        }

        /** @var Workout|null $workout */
        $workout = $queryBuilder
            ->select('workout')
            ->orderBy('workout.createdAt', 'DESC')
            ->setFirstResult(random_int(0, $totalItems - 1))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$workout instanceof Workout) {
            return $this->json(['error' => 'No generated workout available.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeWorkout($workout));
    }

    #[Route('/api/workout-catalog', name: 'workout_catalog', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $page = $this->positiveInt($request->query->get('page'), 1);
        $pageSize = min($this->positiveInt($request->query->get('itemsPerPage'), self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);
        $filters = $this->filtersFromRequest($request);
        $includeDuplicates = $this->booleanQuery($request->query->get('includeDuplicates'));
        $queryBuilder = $this->filteredQueryBuilder($filters);

        if ($includeDuplicates) {
            $totalItems = (int) (clone $queryBuilder)
                ->select('COUNT(DISTINCT workout.id)')
                ->resetDQLPart('orderBy')
                ->getQuery()
                ->getSingleScalarResult();

            $pageWorkoutIds = $this->workoutPageIds($queryBuilder, ($page - 1) * $pageSize, $pageSize);
            $workouts = $this->sortWorkoutsByIds($this->loadCatalogWorkoutsByIds($pageWorkoutIds), $pageWorkoutIds);

            $competitionContextsByWorkoutId = $this->competitionContextsByWorkoutId($workouts);
            $members = array_map(
                fn (Workout $workout): array => $this->serializeWorkout(
                    $workout,
                    $filters,
                    $competitionContextsByWorkoutId[(string) $workout->getId()] ?? [],
                ),
                $workouts,
            );
            $hasNext = $page * $pageSize < $totalItems;
        } else {
            [$canonicalEntries, $totalItems, $hasNext] = $this->canonicalPage($queryBuilder, $this->provenanceFilters($filters), $page, $pageSize);
            $occurrences = [];
            foreach ($canonicalEntries as $entry) {
                array_push($occurrences, ...$entry->occurrences());
            }

            $competitionContextsByWorkoutId = $this->competitionContextsByWorkoutId($occurrences);
            $members = array_map(
                fn (CanonicalWorkoutCatalogEntry $entry): array => $this->serializeCanonicalWorkout($entry, $filters, $competitionContextsByWorkoutId),
                $canonicalEntries,
            );
        }

        $next = null;
        if ($hasNext) {
            $query = $this->paginationQuery($request, $filters);
            $query['page'] = $page + 1;
            $next = '/api/workout-catalog?'.http_build_query($query);
        }

        return $this->json([
            'totalItems' => $totalItems,
            'member' => $members,
            'view' => [
                'next' => $next,
            ],
        ]);
    }

    /**
     * @return array{0: list<CanonicalWorkoutCatalogEntry>, 1: int, 2: bool}
     */
    private function canonicalPage(QueryBuilder $matchingQueryBuilder, array $provenanceFilters, int $page, int $pageSize): array
    {
        $matchingFingerprints = [];
        $matchingNames = [];
        $matchingRepresentativeIds = [];
        $matchingOrder = [];
        $groups = [];

        $this->scanWorkoutRows($matchingQueryBuilder, function (array $row) use (&$matchingFingerprints, &$matchingNames, &$matchingRepresentativeIds, &$matchingOrder): void {
            $fingerprint = $this->fingerprintFromWorkoutRow($row);
            $matchingNames[$fingerprint][mb_strtolower((string) $row['name'])] = true;

            if (isset($matchingFingerprints[$fingerprint])) {
                return;
            }

            $matchingFingerprints[$fingerprint] = true;
            $matchingRepresentativeIds[$fingerprint] = (string) $row['id'];
            $matchingOrder[] = $fingerprint;
        });

        if ($matchingOrder === []) {
            return [[], 0, false];
        }

        $totalItems = count($matchingOrder);
        $pageFingerprints = array_slice($matchingOrder, ($page - 1) * $pageSize, $pageSize);
        if ($pageFingerprints === []) {
            return [[], $totalItems, false];
        }

        $pageFingerprintLookup = array_fill_keys($pageFingerprints, true);
        $pageNames = [];
        $pageRepresentativeIds = [];
        foreach ($pageFingerprints as $fingerprint) {
            $pageNames = array_merge($pageNames, array_keys($matchingNames[$fingerprint] ?? []));
            $pageRepresentativeIds[] = $matchingRepresentativeIds[$fingerprint];
        }

        $representativesById = $this->loadCatalogWorkoutsByIds($pageRepresentativeIds);
        $provenanceQueryBuilder = $this->filteredQueryBuilder($provenanceFilters)
            ->andWhere('(LOWER(workout.name) IN (:canonicalCandidateNames) OR workout.canonicalFingerprint IN (:canonicalCandidateFingerprints))')
            ->setParameter('canonicalCandidateNames', array_values(array_unique($pageNames)))
            ->setParameter('canonicalCandidateFingerprints', $pageFingerprints);

        $this->scanWorkouts($provenanceQueryBuilder, function (Workout $workout) use (&$groups, $pageFingerprintLookup): void {
            $fingerprint = $this->canonicalizer->fingerprint($workout);
            if (!isset($pageFingerprintLookup[$fingerprint])) {
                return;
            }

            $groups[$fingerprint] ??= [];
            $groups[$fingerprint][] = $workout;
        });

        $canonicalEntries = [];
        foreach ($pageFingerprints as $fingerprint) {
            if (!isset($groups[$fingerprint])) {
                continue;
            }

            $occurrences = $groups[$fingerprint];
            $representative = $representativesById[$matchingRepresentativeIds[$fingerprint]] ?? $occurrences[0];
            $canonicalEntries[] = new CanonicalWorkoutCatalogEntry($fingerprint, $representative, $occurrences);
        }

        return [
            $canonicalEntries,
            $totalItems,
            $page * $pageSize < $totalItems,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function fingerprintFromWorkoutRow(array $row): string
    {
        return $this->canonicalizer->fingerprintFromParts(
            is_string($row['canonicalFingerprint'] ?? null) ? $row['canonicalFingerprint'] : null,
            is_string($row['name'] ?? null) ? $row['name'] : null,
            is_string($row['flow'] ?? null) ? $row['flow'] : null,
            is_string($row['workoutTypeName'] ?? null) ? $row['workoutTypeName'] : null,
            $row['numberOfRounds'] === null ? null : (int) $row['numberOfRounds'],
            $row['timeCap'] === null ? null : (int) $row['timeCap'],
        );
    }

    /**
     * @param callable(array<string, mixed>): void $consume
     */
    private function scanWorkoutRows(QueryBuilder $queryBuilder, callable $consume): void
    {
        $offset = 0;
        do {
            /** @var list<array<string, mixed>> $batch */
            $batch = (clone $queryBuilder)
                ->select('DISTINCT workout.id AS id, workout.name AS name, workout.flow AS flow, workout.numberOfRounds AS numberOfRounds, workout.timeCap AS timeCap, workout.canonicalFingerprint AS canonicalFingerprint, catalogScalarWorkoutType.name AS workoutTypeName, workout.createdAt AS createdAt')
                ->leftJoin('workout.workoutType', 'catalogScalarWorkoutType')
                ->orderBy('workout.name', 'ASC')
                ->addOrderBy('workout.createdAt', 'DESC')
                ->setFirstResult($offset)
                ->setMaxResults(self::CANONICAL_SCAN_BATCH_SIZE)
                ->getQuery()
                ->getArrayResult();

            if ($batch === []) {
                break;
            }

            foreach ($batch as $row) {
                $consume($row);
            }

            $offset += self::CANONICAL_SCAN_BATCH_SIZE;
        } while (count($batch) === self::CANONICAL_SCAN_BATCH_SIZE);
    }

    /**
     * @param callable(Workout): void $consume
     */
    private function scanWorkouts(QueryBuilder $queryBuilder, callable $consume): void
    {
        $offset = 0;
        do {
            $batchIds = $this->workoutPageIds($queryBuilder, $offset, self::CANONICAL_SCAN_BATCH_SIZE);

            if ($batchIds === []) {
                break;
            }

            $workoutsById = $this->loadCatalogWorkoutsByIds($batchIds);
            foreach ($batchIds as $workoutId) {
                if (!isset($workoutsById[$workoutId])) {
                    continue;
                }

                $consume($workoutsById[$workoutId]);
            }

            $offset += self::CANONICAL_SCAN_BATCH_SIZE;
        } while (count($batchIds) === self::CANONICAL_SCAN_BATCH_SIZE);
    }

    /**
     * @return list<string>
     */
    private function workoutPageIds(QueryBuilder $queryBuilder, int $offset, int $limit): array
    {
        /** @var list<array{id: mixed}> $rows */
        $rows = (clone $queryBuilder)
            ->select('DISTINCT workout.id AS id, workout.name AS name, workout.createdAt AS createdAt')
            ->orderBy('workout.name', 'ASC')
            ->addOrderBy('workout.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (string) $row['id'];
        }

        return $ids;
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, Workout>
     */
    private function loadCatalogWorkoutsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<Workout> $workouts */
        $workouts = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout')
            ->select('DISTINCT workout')
            ->leftJoin('workout.workoutType', 'catalogWorkoutType')
            ->addSelect('catalogWorkoutType')
            ->leftJoin('workout.workoutOrigin', 'catalogWorkoutOrigin')
            ->addSelect('catalogWorkoutOrigin')
            ->leftJoin('catalogWorkoutOrigin.name', 'catalogWorkoutOriginName')
            ->addSelect('catalogWorkoutOriginName')
            ->leftJoin('workout.implements', 'catalogImplement')
            ->addSelect('catalogImplement')
            ->leftJoin('workout.movements', 'catalogMovement')
            ->addSelect('catalogMovement')
            ->andWhere('workout.id IN (:catalogWorkoutIds)')
            ->setParameter('catalogWorkoutIds', array_values(array_unique($ids)))
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($workouts as $workout) {
            $byId[(string) $workout->getId()] = $workout;
        }

        return $byId;
    }

    /**
     * @param array<string, Workout> $workoutsById
     * @param list<string>           $ids
     *
     * @return list<Workout>
     */
    private function sortWorkoutsByIds(array $workoutsById, array $ids): array
    {
        $workouts = [];
        foreach ($ids as $id) {
            if (!isset($workoutsById[$id])) {
                continue;
            }

            $workouts[] = $workoutsById[$id];
        }

        return $workouts;
    }

    /**
     * @param list<Workout> $workouts
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function competitionContextsByWorkoutId(array $workouts): array
    {
        $workoutIds = [];
        foreach ($workouts as $workout) {
            $workoutIds[] = (string) $workout->getId();
        }

        $workoutIds = array_values(array_unique($workoutIds));
        if ($workoutIds === []) {
            return [];
        }

        /** @var list<array<string, mixed>> $eventRows */
        $eventRows = $this->entityManager->createQueryBuilder()
            ->select('event.id AS eventId, IDENTITY(event.workout) AS workoutId, competition.id AS competitionId, competition.name AS competitionName, competition.season AS competitionSeason, competition.logoUrl AS competitionLogoUrl, event.name AS eventName, event.eventOrder AS eventOrder, event.sourceName AS sourceName, event.provenances AS provenances')
            ->from(CompetitionEvent::class, 'event')
            ->innerJoin('event.competition', 'competition')
            ->andWhere('event.workout IN (:catalogContextWorkoutIds)')
            ->setParameter('catalogContextWorkoutIds', $workoutIds)
            ->getQuery()
            ->getArrayResult();

        if ($eventRows === []) {
            return [];
        }

        $eventIds = [];
        foreach ($eventRows as $row) {
            $eventIds[] = (string) $row['eventId'];
        }

        $divisionsByEventId = [];
        /** @var list<array<string, mixed>> $divisionRows */
        $divisionRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(result.event) AS eventId, competitionDivision.name AS competitionDivisionName, result.division AS resultDivision')
            ->from(WorkoutResult::class, 'result')
            ->leftJoin('result.competitionDivision', 'competitionDivision')
            ->andWhere('result.event IN (:catalogContextEventIds)')
            ->groupBy('result.event', 'competitionDivision.name', 'result.division')
            ->setParameter('catalogContextEventIds', array_values(array_unique($eventIds)))
            ->getQuery()
            ->getArrayResult();

        foreach ($divisionRows as $row) {
            $eventId = (string) $row['eventId'];
            $division = $row['competitionDivisionName'] ?? $row['resultDivision'] ?? null;
            if (!is_string($division) || $division === '') {
                continue;
            }

            $divisionsByEventId[$eventId][$division] = true;
        }

        $contextsByWorkoutId = [];
        foreach ($eventRows as $row) {
            $workoutId = (string) $row['workoutId'];
            $eventId = (string) $row['eventId'];
            $divisions = array_keys($divisionsByEventId[$eventId] ?? []);
            sort($divisions, SORT_NATURAL | SORT_FLAG_CASE);

            $context = [
                'competitionId' => (string) $row['competitionId'],
                'competitionName' => (string) $row['competitionName'],
                'competitionSeason' => $row['competitionSeason'] === null ? null : (int) $row['competitionSeason'],
                'competitionLogoUrl' => is_string($row['competitionLogoUrl'] ?? null) ? $row['competitionLogoUrl'] : null,
                'eventName' => (string) $row['eventName'],
                'eventOrder' => $row['eventOrder'] === null ? null : (int) $row['eventOrder'],
                'sourceName' => (string) $row['sourceName'],
                'divisions' => $divisions,
                'provenances' => is_array($row['provenances'] ?? null) ? $row['provenances'] : [],
            ];

            $contextsByWorkoutId[$workoutId] ??= [];
            $this->mergeCompetitionContext($contextsByWorkoutId[$workoutId], $context);
        }

        foreach ($contextsByWorkoutId as &$contexts) {
            $this->sortCompetitionContexts($contexts);
        }

        return $contextsByWorkoutId;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $competitionContextsByWorkoutId
     *
     * @return list<array<string, mixed>>
     */
    private function canonicalCompetitionContexts(CanonicalWorkoutCatalogEntry $entry, array $competitionContextsByWorkoutId): array
    {
        $contexts = [];
        foreach ($entry->occurrences() as $workout) {
            foreach ($competitionContextsByWorkoutId[(string) $workout->getId()] ?? [] as $context) {
                $this->mergeCompetitionContext($contexts, $context);
            }
        }

        $this->sortCompetitionContexts($contexts);

        return $contexts;
    }

    /**
     * @param list<array<string, mixed>> $contexts
     * @param array<string, mixed>       $context
     */
    private function mergeCompetitionContext(array &$contexts, array $context): void
    {
        $key = implode('|', [
            $context['competitionId'],
            $context['eventName'],
            (string) ($context['eventOrder'] ?? ''),
            $context['sourceName'],
        ]);

        foreach ($contexts as &$existingContext) {
            $existingKey = implode('|', [
                $existingContext['competitionId'],
                $existingContext['eventName'],
                (string) ($existingContext['eventOrder'] ?? ''),
                $existingContext['sourceName'],
            ]);

            if ($existingKey !== $key) {
                continue;
            }

            $existingContext['divisions'] = $this->mergeSortedStrings($existingContext['divisions'], $context['divisions']);
            $existingContext['provenances'] = $this->mergeProvenances($existingContext['provenances'], $context['provenances']);

            return;
        }

        $contexts[] = $context;
    }

    /**
     * @param list<array<string, mixed>> $contexts
     */
    private function sortCompetitionContexts(array &$contexts): void
    {
        usort($contexts, static function (array $left, array $right): int {
            return [
                $right['competitionSeason'] ?? 0,
                $left['competitionName'],
                $left['eventOrder'] ?? PHP_INT_MAX,
                $left['eventName'],
            ] <=> [
                $left['competitionSeason'] ?? 0,
                $right['competitionName'],
                $right['eventOrder'] ?? PHP_INT_MAX,
                $right['eventName'],
            ];
        });
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     *
     * @return list<string>
     */
    private function mergeSortedStrings(array $left, array $right): array
    {
        $values = array_fill_keys(array_merge($left, $right), true);
        $strings = array_keys($values);
        sort($strings, SORT_NATURAL | SORT_FLAG_CASE);

        return $strings;
    }

    /**
     * @param list<array<string, mixed>> $current
     * @param list<array<string, mixed>> $additional
     *
     * @return list<array<string, mixed>>
     */
    private function mergeProvenances(array $current, array $additional): array
    {
        $merged = [];
        $seen = [];

        foreach (array_merge($current, $additional) as $provenance) {
            $key = json_encode($provenance, JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) {
                continue;
            }

            $merged[] = $provenance;
            $seen[$key] = true;
        }

        return $merged;
    }

    /**
     * Text filters select matching canonical fingerprints. Provenance then comes
     * from the full canonical group so formatting variants remain visible.
     *
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     *
     * @return array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * }
     */
    private function provenanceFilters(array $filters): array
    {
        $filters['query'] = null;
        $filters['name'] = null;
        $filters['flow'] = null;

        return $filters;
    }

    /**
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     */
    private function filteredQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout');
        $this->publicWorkoutCatalogVisibility->applyPublicConstraint($queryBuilder, 'workout');

        if ($filters['query'] !== null) {
            $queryBuilder
                ->andWhere('(LOWER(workout.name) LIKE :query OR LOWER(workout.flow) LIKE :query)')
                ->setParameter('query', '%'.$filters['query'].'%');
        }

        if ($filters['name'] !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.name) LIKE :name')
                ->setParameter('name', '%'.$filters['name'].'%');
        }

        if ($filters['flow'] !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.flow) LIKE :flow')
                ->setParameter('flow', '%'.$filters['flow'].'%');
        }

        if ($filters['workoutType'] !== null) {
            $queryBuilder
                ->innerJoin('workout.workoutType', 'filterWorkoutType')
                ->andWhere('LOWER(filterWorkoutType.name) = :workoutType')
                ->setParameter('workoutType', $filters['workoutType']);
        }

        if ($filters['timeCap'] !== null) {
            $queryBuilder
                ->andWhere('workout.timeCap = :timeCap')
                ->setParameter('timeCap', $filters['timeCap']);
        }

        if ($filters['timeCapMin'] !== null) {
            $queryBuilder
                ->andWhere('workout.timeCap >= :timeCapMin')
                ->setParameter('timeCapMin', $filters['timeCapMin']);
        }

        if ($filters['timeCapMax'] !== null) {
            $queryBuilder
                ->andWhere('workout.timeCap <= :timeCapMax')
                ->setParameter('timeCapMax', $filters['timeCapMax']);
        }

        if ($filters['sourceNames'] !== []) {
            $sourceConditions = [];
            $exactSourceNames = [];

            if (array_any($filters['sourceNames'], fn (string $sourceName): bool => $this->isCompetitionSourceAlias($sourceName))) {
                $sourceConditions[] = 'workout.competitionEvents IS NOT EMPTY';
            }

            if (in_array('monwod_catalog', $filters['sourceNames'], true)) {
                $queryBuilder
                    ->leftJoin('workout.workoutOrigin', 'sourceWorkoutOrigin')
                    ->leftJoin('sourceWorkoutOrigin.name', 'sourceWorkoutOriginName')
                    ->setParameter('monwodCatalogOrigins', [
                        WorkoutOriginNameEnum::GIRLS_WORKOUT->value,
                        WorkoutOriginNameEnum::HERO_WORKOUT->value,
                    ]);
                $sourceConditions[] = 'sourceWorkoutOriginName.name IN (:monwodCatalogOrigins)';
            }

            foreach ($filters['sourceNames'] as $sourceName) {
                if ($this->isCompetitionSourceAlias($sourceName) || $sourceName === 'monwod_catalog') {
                    continue;
                }

                $exactSourceNames[] = $sourceName;
            }

            if ($exactSourceNames !== []) {
                $queryBuilder
                    ->setParameter('sourceNames', array_values(array_unique($exactSourceNames)));
                $sourceConditions[] = 'LOWER(workout.sourceName) IN (:sourceNames)';
            }

            $queryBuilder->andWhere('('.implode(' OR ', $sourceConditions).')');
        }

        foreach ($filters['movementNames'] as $index => $movementName) {
            $alias = sprintf('movement%d', $index);
            $parameterName = sprintf('movementName%d', $index);
            $flowConditions = [];
            $queryBuilder
                ->leftJoin('workout.movements', $alias)
                ->setParameter($parameterName, $movementName);

            foreach ($this->movementFlowFallbackPatterns($movementName) as $patternIndex => $pattern) {
                $flowParameterName = sprintf('movementFlow%d_%d', $index, $patternIndex);
                $flowConditions[] = sprintf('LOWER(CONCAT(CONCAT(\' \', workout.flow), \' \')) LIKE :%s', $flowParameterName);
                $queryBuilder->setParameter($flowParameterName, $pattern);
            }

            if ($flowConditions === []) {
                $queryBuilder->andWhere(sprintf('LOWER(%s.name) = :%s', $alias, $parameterName));

                continue;
            }

            $queryBuilder->andWhere(sprintf('(LOWER(%s.name) = :%s OR (workout.movements IS EMPTY AND (%s)))', $alias, $parameterName, implode(' OR ', $flowConditions)));
        }

        foreach ($filters['implementNames'] as $index => $implementName) {
            $alias = sprintf('implement%d', $index);
            $queryBuilder
                ->innerJoin('workout.implements', $alias)
                ->andWhere(sprintf('LOWER(%s.name) = :implementName%d', $alias, $index))
                ->setParameter(sprintf('implementName%d', $index), $implementName);
        }

        return $queryBuilder;
    }

    private function isCompetitionSourceAlias(string $sourceName): bool
    {
        return in_array($sourceName, ['competition', 'competitions'], true);
    }

    /**
     * Imported competition workouts may not have structured movements yet. Use
     * bounded flow patterns only as a fallback for those flow-only workouts.
     *
     * @return list<string>
     */
    private function movementFlowFallbackPatterns(string $movementName): array
    {
        if (in_array($movementName, self::AMBIGUOUS_FLOW_FALLBACK_MOVEMENTS, true)) {
            return [];
        }

        $terms = $this->movementFlowFallbackTerms($movementName);

        $patterns = [];
        foreach (array_values(array_unique($terms)) as $term) {
            foreach ($this->movementFlowLeftBoundaries() as $leftBoundary) {
                foreach (["\n", "\r", ',', '.', ':', ';', ')', ' ('] as $rightBoundary) {
                    $patterns[] = '%'.$leftBoundary.$term.$rightBoundary.'%';
                }
            }
        }

        return array_values(array_unique($patterns));
    }

    /**
     * @return list<string>
     */
    private function movementFlowFallbackTerms(string $movementName): array
    {
        $terms = [$movementName];
        if (!str_ends_with($movementName, 's')) {
            $terms[] = $movementName.'s';
        }

        $hyphenatedTerms = [];
        foreach ($terms as $term) {
            if (str_contains($term, ' ')) {
                $hyphenatedTerms[] = str_replace(' ', '-', $term);
            }
        }

        return array_values(array_unique(array_merge($terms, $hyphenatedTerms)));
    }

    /**
     * @return list<string>
     */
    private function movementFlowLeftBoundaries(): array
    {
        return [
            "\n",
            "\r",
            '(',
            '[',
            ': ',
            '; ',
            ', ',
            '. ',
            '0 ',
            '1 ',
            '2 ',
            '3 ',
            '4 ',
            '5 ',
            '6 ',
            '7 ',
            '8 ',
            '9 ',
        ];
    }

    /**
     * @return array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * }
     */
    private function filtersFromRequest(Request $request): array
    {
        $sourceNames = $this->queryStringList($request, 'sourceNames', 'sourceName', 'source');

        return [
            'query' => $this->normalizedString($request->query->get('q')),
            'name' => $this->normalizedString($request->query->get('name')),
            'flow' => $this->normalizedString($request->query->get('flow')),
            'workoutType' => $this->normalizedString($request->query->get('workoutType')),
            'timeCap' => $this->nullablePositiveInt($request->query->get('timeCap')),
            'timeCapMin' => $this->nullablePositiveInt($request->query->get('timeCapMin')),
            'timeCapMax' => $this->nullablePositiveInt($request->query->get('timeCapMax')),
            'sourceName' => $sourceNames[0] ?? null,
            'sourceNames' => $sourceNames,
            'movementNames' => $this->queryStringList($request, 'movements.name', 'movement'),
            'implementNames' => $this->queryStringList($request, 'implements.name', 'implement'),
        ];
    }

    /**
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     *
     * @return array<string, mixed>
     */
    private function paginationQuery(Request $request, array $filters): array
    {
        $query = $request->query->all();
        unset($query['source'], $query['sourceName'], $query['sourceNames']);

        if ($filters['sourceNames'] !== []) {
            $query['sourceNames'] = $filters['sourceNames'];
        }

        return $query;
    }

    /**
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * }|null $filters
     * @param list<array<string, mixed>>|null $competitionContexts
     *
     * @return array<string, mixed>
     */
    private function serializeWorkout(Workout $workout, ?array $filters = null, ?array $competitionContexts = null): array
    {
        return [
            '@id' => '/api/workouts/'.$workout->getId(),
            'id' => (string) $workout->getId(),
            'name' => $workout->getName(),
            'flow' => $workout->getFlow(),
            'timeCap' => $workout->getTimeCap(),
            'workoutType' => $workout->getWorkoutType()?->getName(),
            'workoutOrigin' => $workout->getWorkoutOrigin()->getName()->getName(),
            'implements' => array_map(
                static fn (Implement $implement): string => $implement->getName(),
                $workout->getImplements()->toArray(),
            ),
            'movements' => array_map(
                static fn (Movement $movement): string => $movement->getName() ?? '',
                $workout->getMovements()->toArray(),
            ),
            'createdAt' => $workout->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'sourceName' => $workout->getSourceName(),
            'externalId' => $workout->getExternalId(),
            'sourceUrl' => $workout->getSourceUrl(),
            'competitionContexts' => $competitionContexts ?? $workout->getCompetitionContexts(),
            'matchDetails' => $filters === null ? [] : $this->matchDetails($workout, $filters),
        ];
    }

    /**
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     * @param array<string, list<array<string, mixed>>> $competitionContextsByWorkoutId
     *
     * @return array<string, mixed>
     */
    private function serializeCanonicalWorkout(CanonicalWorkoutCatalogEntry $entry, array $filters, array $competitionContextsByWorkoutId): array
    {
        $payload = $this->serializeWorkout(
            $entry->representative,
            $filters,
            $competitionContextsByWorkoutId[(string) $entry->representative->getId()] ?? [],
        );
        $payload['canonicalFingerprint'] = $entry->fingerprint;
        $payload['occurrenceCount'] = $entry->occurrenceCount();
        $payload['workoutIds'] = $entry->workoutIds();
        $payload['sources'] = $entry->sourceNames();
        $payload['workoutOrigins'] = $entry->workoutOrigins();
        $payload['sourceReferences'] = $entry->sourceReferences();
        $payload['competitionContexts'] = $this->canonicalCompetitionContexts($entry, $competitionContextsByWorkoutId);

        return $payload;
    }

    /**
     * @param array{
     *     query: ?string,
     *     name: ?string,
     *     flow: ?string,
     *     workoutType: ?string,
     *     timeCap: ?int,
     *     timeCapMin: ?int,
     *     timeCapMax: ?int,
     *     sourceName: ?string,
     *     sourceNames: list<string>,
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     *
     * @return array<string, mixed>
     */
    private function matchDetails(Workout $workout, array $filters): array
    {
        $details = [];
        if ($filters['query'] !== null) {
            $details['query'] = [
                'term' => $filters['query'],
                'fields' => $this->matchedTextFields($workout, $filters['query']),
            ];
        }
        if ($filters['name'] !== null) {
            $details['name'] = $filters['name'];
        }
        if ($filters['flow'] !== null) {
            $details['flow'] = $filters['flow'];
        }
        if ($filters['workoutType'] !== null) {
            $details['workoutType'] = $workout->getWorkoutType()?->getName();
        }
        if ($filters['timeCap'] !== null || $filters['timeCapMin'] !== null || $filters['timeCapMax'] !== null) {
            $details['timeCap'] = [
                'value' => $workout->getTimeCap(),
                'requested' => $filters['timeCap'],
                'min' => $filters['timeCapMin'],
                'max' => $filters['timeCapMax'],
            ];
        }
        if ($filters['movementNames'] !== []) {
            $details['movements'] = $this->matchedNamedValues(
                $filters['movementNames'],
                array_map(
                    static fn (Movement $movement): string => $movement->getName() ?? '',
                    $workout->getMovements()->toArray(),
                ),
            );
        }
        if ($filters['implementNames'] !== []) {
            $details['implements'] = $this->matchedNamedValues(
                $filters['implementNames'],
                array_map(
                    static fn (Implement $implement): string => $implement->getName(),
                    $workout->getImplements()->toArray(),
                ),
            );
        }

        return $details;
    }

    /**
     * @return list<string>
     */
    private function matchedTextFields(Workout $workout, string $query): array
    {
        $fields = [];
        if (str_contains(mb_strtolower($workout->getName()), $query)) {
            $fields[] = 'name';
        }
        if (str_contains(mb_strtolower($workout->getFlow()), $query)) {
            $fields[] = 'flow';
        }

        return $fields;
    }

    /**
     * @param list<string> $requested
     * @param list<string> $available
     *
     * @return list<string>
     */
    private function matchedNamedValues(array $requested, array $available): array
    {
        $requestedLookup = array_flip($requested);
        $matched = [];
        foreach ($available as $value) {
            if (isset($requestedLookup[mb_strtolower($value)])) {
                $matched[] = $value;
            }
        }

        return $matched;
    }

    private function normalizedString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_strtolower($value);
    }

    /**
     * @return list<string>
     */
    private function queryStringList(Request $request, string ...$keys): array
    {
        $query = $request->query->all();
        $values = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $query)) {
                continue;
            }

            $value = $query[$key];
            foreach (is_array($value) ? $value : [$value] as $item) {
                $values[] = $item;
            }
        }

        $rawQueryString = (string) $request->server->get('QUERY_STRING', '');
        foreach (explode('&', $rawQueryString) as $rawPair) {
            if ($rawPair === '') {
                continue;
            }

            [$rawKey, $rawValue] = array_pad(explode('=', $rawPair, 2), 2, '');
            $key = urldecode($rawKey);
            $key = str_ends_with($key, '[]') ? substr($key, 0, -2) : $key;
            if (!in_array($key, $keys, true)) {
                continue;
            }

            $values[] = urldecode($rawValue);
        }

        return $this->normalizedStringList($values);
    }

    /**
     * @return list<string>
     */
    private function normalizedStringList(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];
        foreach ($values as $item) {
            $item = $this->normalizedString($item);
            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if (!is_scalar($value) || !ctype_digit((string) $value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    private function positiveInt(mixed $value, int $default): int
    {
        if (!is_scalar($value) || !ctype_digit((string) $value)) {
            return $default;
        }

        return max(1, (int) $value);
    }

    private function booleanQuery(mixed $value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOL);
    }
}
