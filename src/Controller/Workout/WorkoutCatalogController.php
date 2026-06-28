<?php

declare(strict_types=1);

namespace App\Controller\Workout;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
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

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/workout-catalog/random-generated', name: 'workout_catalog_random_generated', methods: ['GET'])]
    public function randomGenerated(): JsonResponse
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout')
            ->andWhere('workout.workoutGeneration IS NOT NULL');
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
        $queryBuilder = $this->filteredQueryBuilder($filters);
        $totalItems = (int) (clone $queryBuilder)
            ->select('COUNT(DISTINCT workout.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<Workout> $workouts */
        $workouts = $queryBuilder
            ->select('DISTINCT workout')
            ->orderBy('workout.name', 'ASC')
            ->addOrderBy('workout.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        $next = null;
        if ($page * $pageSize < $totalItems) {
            $query = $request->query->all();
            $query['page'] = $page + 1;
            $next = '/api/workout-catalog?'.http_build_query($query);
        }

        return $this->json([
            'totalItems' => $totalItems,
            'member' => array_map(
                fn (Workout $workout): array => $this->serializeWorkout($workout, $filters),
                $workouts,
            ),
            'view' => [
                'next' => $next,
            ],
        ]);
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
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * } $filters
     */
    private function filteredQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout');

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

        if ($filters['sourceName'] !== null) {
            if ($filters['sourceName'] === 'monwod_catalog') {
                $queryBuilder
                    ->innerJoin('workout.workoutOrigin', 'sourceWorkoutOrigin')
                    ->innerJoin('sourceWorkoutOrigin.name', 'sourceWorkoutOriginName')
                    ->andWhere('sourceWorkoutOriginName.name IN (:monwodCatalogOrigins)')
                    ->setParameter('monwodCatalogOrigins', [
                        WorkoutOriginNameEnum::GIRLS_WORKOUT->value,
                        WorkoutOriginNameEnum::HERO_WORKOUT->value,
                    ]);
            } else {
                $queryBuilder
                    ->andWhere('LOWER(workout.sourceName) = :sourceName')
                    ->setParameter('sourceName', $filters['sourceName']);
            }
        }

        foreach ($filters['movementNames'] as $index => $movementName) {
            $alias = sprintf('movement%d', $index);
            $queryBuilder
                ->innerJoin('workout.movements', $alias)
                ->andWhere(sprintf('LOWER(%s.name) = :movementName%d', $alias, $index))
                ->setParameter(sprintf('movementName%d', $index), $movementName);
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
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * }
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'query' => $this->normalizedString($request->query->get('q')),
            'name' => $this->normalizedString($request->query->get('name')),
            'flow' => $this->normalizedString($request->query->get('flow')),
            'workoutType' => $this->normalizedString($request->query->get('workoutType')),
            'timeCap' => $this->nullablePositiveInt($request->query->get('timeCap')),
            'timeCapMin' => $this->nullablePositiveInt($request->query->get('timeCapMin')),
            'timeCapMax' => $this->nullablePositiveInt($request->query->get('timeCapMax')),
            'sourceName' => $this->normalizedString($request->query->get('sourceName')),
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
     *     movementNames: list<string>,
     *     implementNames: list<string>,
     * }|null $filters
     *
     * @return array<string, mixed>
     */
    private function serializeWorkout(Workout $workout, ?array $filters = null): array
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
            'competitionContexts' => $workout->getCompetitionContexts(),
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
}
