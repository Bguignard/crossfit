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
            $typeOrX = $query->expr()->orX();
            foreach ($movementTypes as $idx => $movementType) {
                $typeOrX->add('m.movementType = :movementType'.$idx);
                $query->setParameter('movementType'.$idx, $movementType);
            }
            $query->andWhere($typeOrX);
        }

        if (count($difficulties) > 0) {
            $diffOrX = $query->expr()->orX();
            foreach ($difficulties as $idx => $difficulty) {
                $diffOrX->add('m.difficulty = :difficulty'.$idx);
                $query->setParameter('difficulty'.$idx, $difficulty);
            }
            $query->andWhere($diffOrX);
        }

        if (count($implements) > 0) {
            $diffOrX = $query->expr()->orX();
            foreach ($implements as $idx => $implement) {
                $diffOrX->add(':implement'.$idx.' MEMBER OF m.possibleImplements');
                $query->setParameter('implement'.$idx, $implement);
            }
            $query->andWhere($diffOrX);
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
     * @param Muscle[] $muscles
     * @return Movement[]
     */
    public function getMovementsByMovementTypesAndDifficultyAndImplementsAndMuscles(
        array $movementTypes,
        array $difficulties,
        array $movementsToExclude,
        array $implements,
        array $muscles,
    ) {
        $query = $this->createQueryBuilder('m');

        if (count($movementTypes) > 0) {
            $typeOrX = $query->expr()->orX();
            foreach ($movementTypes as $idx => $movementType) {
                $typeOrX->add('m.movementType = :movementType'.$idx);
                $query->setParameter('movementType'.$idx, $movementType);
            }
            $query->andWhere($typeOrX);
        }

        if (count($difficulties) > 0) {
            $diffOrX = $query->expr()->orX();
            foreach ($difficulties as $idx => $difficulty) {
                $diffOrX->add('m.difficulty = :difficulty'.$idx);
                $query->setParameter('difficulty'.$idx, $difficulty);
            }
            $query->andWhere($diffOrX);
        }

        if (count($implements) > 0) {
            $diffOrX = $query->expr()->orX();
            foreach ($implements as $idx => $implement) {
                $diffOrX->add(':implement'.$idx.' MEMBER OF m.possibleImplements');
                $query->setParameter('implement'.$idx, $implement);
            }
            $query->andWhere($diffOrX);
        }

        if (count($muscles) > 0) {
            $muscleOrX = $query->expr()->orX();
            foreach ($muscles as $idx => $muscle) {
                $muscleOrX->add(':muscle'.$idx.' MEMBER OF m.muscles');
                $query->setParameter('muscle'.$idx, $muscle);
            }
            $query->andWhere($muscleOrX);
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
}
