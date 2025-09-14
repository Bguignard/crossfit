<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
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
            ->setParameter('movementType', $movementType);

        if (!empty($forbiddenMovements)) {
            $queryBuilder->andWhere('m.id NOT IN (:forbiddenMovements)')
                ->setParameter('forbiddenMovements', $forbiddenMovements);
        }

        if (!empty($availableImplements)) {
            $queryBuilder->leftJoin('m.possibleImplements', 'i')
            ->andwhere('i.id IN (:availableImplementsIds)')
            ->setParameter('availableImplementsIds', array_map(fn (Implement $implement) => $implement->getId()->toBinary(), $availableImplements))
            ->orWhere('i.id IS NULL');
        }

        $result = $queryBuilder->getQuery()->getResult();

        return $result[rand(0, count($result) - 1)];
    }

    /*
     * @param MovementType[] $movementTypes
     * @param MovementDifficulty[] $difficulties
     * @return Movement[]
     */
    public function getMovementsByMovementTypesAndDifficulty(array $movementTypes, array $difficulties): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.movementType IN (:movementType)')
            ->andWhere('m.difficulty IN (:difficulty)')
            ->setParameter('difficulty', $difficulties)
            ->setParameter('movementType', $movementTypes)
            ->getQuery()
            ->getResult();
    }
}
