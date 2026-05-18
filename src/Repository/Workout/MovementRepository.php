<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Movement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movement>
 *
 * @method Movement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movement[]    findAll()
 * @method Movement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementRepository extends ServiceEntityRepository implements MovementRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movement::class);
    }

    public function getRandomMovement(?int $maximumDifficulty = 100, ?array $forbiddenMovements = []): Movement
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.difficulty <= :maximumDifficulty')
            ->setParameter('maximumDifficulty', $maximumDifficulty)
            ->orderBy('RAND()')
            ->setMaxResults(1);

        if (!empty($forbiddenMovements)) {
            $queryBuilder->andWhere('m.id NOT IN (:forbiddenMovements)')
                ->setParameter('forbiddenMovements', array_map(fn (Movement $movement) => $movement->getId(), $forbiddenMovements));
        }

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /*
     * @param MovementType[] $movementTypes
     * @param MovementDifficulty[] $difficulties
     * @param Movement[] $movementsToExclude
     * @param Implement[]|null $implements
     * @return Movement[]
     */
    public function getMovementsByMovementTypesAndDifficultyAndImplements(
        array $movementTypes,
        array $difficulties,
        array $movementsToExclude,
        array $implements,
    ): array {
        $query = $this->createQueryBuilder('m');

        if (count($movementTypes) > 0) {
            $query
                ->join('m.movementType', 'mt')
                ->andWhere('mt.name IN (:movementTypeNames)')
                ->setParameter('movementTypeNames', $this->entityNames($movementTypes));
        }

        if (count($difficulties) > 0) {
            $query
                ->join('m.difficulty', 'md')
                ->andWhere('md.name IN (:difficultyNames)')
                ->setParameter('difficultyNames', $this->entityNames($difficulties));
        }

        if (count($implements) > 0) {
            $query
                ->join('m.possibleImplements', 'pi')
                ->andWhere('pi.name IN (:implementNames)')
                ->setParameter('implementNames', $this->entityNames($implements));
        }

        if (count($movementsToExclude) > 0) {
            $movementsToExclude = array_map(fn (Movement $movement) => $movement->getId()->toString(), $movementsToExclude);
            $query->andWhere('m.id NOT IN (:movementsToExclude)')
                ->setParameter('movementsToExclude', $movementsToExclude);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    /*
     * @param MovementType[] $movementTypes
     * @param MovementDifficulty[] $difficulties
     * @param Movement[] $movementsToExclude
     * @param Implement[] $implements
     * @param BodyParts[] $bodyParts
     * @return Movement[]
     */
    public function getMovementsByMovementTypesAndDifficultyAndImplementsAndMuscles(
        array $movementTypes,
        array $difficulties,
        array $movementsToExclude,
        array $implements,
        array $bodyParts,
    ) {
        $query = $this->createQueryBuilder('m');

        if (count($movementTypes) > 0) {
            $query
                ->join('m.movementType', 'mt')
                ->andWhere('mt.name IN (:movementTypeNames)')
                ->setParameter('movementTypeNames', $this->entityNames($movementTypes));
        }

        if (count($difficulties) > 0) {
            $query
                ->join('m.difficulty', 'md')
                ->andWhere('md.name IN (:difficultyNames)')
                ->setParameter('difficultyNames', $this->entityNames($difficulties));
        }

        if (count($implements) > 0) {
            $query
                ->join('m.possibleImplements', 'pi')
                ->andWhere('pi.name IN (:implementNames)')
                ->setParameter('implementNames', $this->entityNames($implements));
        }

        if (count($bodyParts) > 0) {
            $query->join('m.muscles', 'mm')
                ->join('mm.bodyPart', 'bp');

            $query
                ->andWhere('bp.name IN (:bodyPartNames)')
                ->setParameter('bodyPartNames', $this->entityNames($bodyParts));
        }

        if (count($movementsToExclude) > 0) {
            $movementsToExclude = array_map(fn (Movement $movement) => $movement->getId()->toString(), $movementsToExclude);
            $query->andWhere('m.id NOT IN (:movementsToExclude)')
                ->setParameter('movementsToExclude', $movementsToExclude);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    private function entityNames(array $entities): array
    {
        return array_values(array_unique(array_map(
            static fn (object $entity): string => $entity->getName(),
            $entities,
        )));
    }
}
