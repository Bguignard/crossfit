<?php

declare(strict_types=1);

namespace App\Controller\Workout;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class WorkoutCatalogController extends AbstractController
{
    private const DEFAULT_PAGE_SIZE = 25;
    private const MAX_PAGE_SIZE = 50;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/workout-catalog', name: 'workout_catalog', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $page = $this->positiveInt($request->query->get('page'), 1);
        $pageSize = min($this->positiveInt($request->query->get('itemsPerPage'), self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);
        $queryBuilder = $this->filteredQueryBuilder($request);
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
            'member' => array_map($this->serializeWorkout(...), $workouts),
            'view' => [
                'next' => $next,
            ],
        ]);
    }

    private function filteredQueryBuilder(Request $request): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout');

        $name = $this->normalizedString($request->query->get('name'));
        if ($name !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.name) LIKE :name')
                ->setParameter('name', '%'.$name.'%');
        }

        $flow = $this->normalizedString($request->query->get('flow'));
        if ($flow !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.flow) LIKE :flow')
                ->setParameter('flow', '%'.$flow.'%');
        }

        $timeCap = $request->query->get('timeCap');
        if (is_scalar($timeCap) && ctype_digit((string) $timeCap)) {
            $queryBuilder
                ->andWhere('workout.timeCap = :timeCap')
                ->setParameter('timeCap', (int) $timeCap);
        }

        $sourceName = $this->normalizedString($request->query->get('sourceName'));
        if ($sourceName !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.sourceName) = :sourceName')
                ->setParameter('sourceName', $sourceName);
        }

        $movementNames = $this->normalizedStringList($request->query->all()['movements.name'] ?? []);
        foreach ($movementNames as $index => $movementName) {
            $alias = sprintf('movement%d', $index);
            $queryBuilder
                ->innerJoin('workout.movements', $alias)
                ->andWhere(sprintf('LOWER(%s.name) = :movementName%d', $alias, $index))
                ->setParameter(sprintf('movementName%d', $index), $movementName);
        }

        $implementNames = $this->normalizedStringList($request->query->all()['implements.name'] ?? []);
        foreach ($implementNames as $index => $implementName) {
            $alias = sprintf('implement%d', $index);
            $queryBuilder
                ->innerJoin('workout.implements', $alias)
                ->andWhere(sprintf('LOWER(%s.name) = :implementName%d', $alias, $index))
                ->setParameter(sprintf('implementName%d', $index), $implementName);
        }

        return $queryBuilder;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWorkout(Workout $workout): array
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
        ];
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

    private function positiveInt(mixed $value, int $default): int
    {
        if (!is_scalar($value) || !ctype_digit((string) $value)) {
            return $default;
        }

        return max(1, (int) $value);
    }
}
