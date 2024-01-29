<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
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

    /**
     * @param Implement[]|null $availableImplements
     * @param Movement[]|null  $forbiddenMovements
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMovementByDifficultyAndImplementsAndForbiddenMovementsAndType(?array $availableImplements, ?int $maxDifficulty, ?array $forbiddenMovements, MovementTypeEnum $movementType): ?Movement
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.difficulty <= :maxDifficulty')
            ->setParameter('maxDifficulty', $maxDifficulty)
            ->andWhere('m.movementType = :movementType')
            ->setParameter('movementType', $movementType)
            ->orderBy('RAND()')
            ->setMaxResults(1);

        if (!empty($forbiddenMovements)) {
            $queryBuilder->andWhere('m.id NOT IN (:forbiddenMovements)')
                ->setParameter('forbiddenMovements', array_map(fn (Movement $movement) => $movement->getId(), $forbiddenMovements));
        }

        if (!empty($availableImplements)) {
            $queryBuilder->andWhere('m.possibleImplements IN (:availableImplements) OR m.possibleImplements IS EMPTY')
                ->setParameter('availableImplements', array_map(fn (Implement $implement) => $implement->getId(), $availableImplements));
        }

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
