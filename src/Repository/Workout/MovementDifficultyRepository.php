<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementDifficulty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementDifficulty>
 *
 * @method MovementDifficulty|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementDifficulty|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementDifficulty[]    findAll()
 * @method MovementDifficulty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementDifficultyRepository extends ServiceEntityRepository implements MovementDifficultyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementDifficulty::class);
    }

    public function persist(MovementDifficulty $movementDifficulty): MovementDifficulty
    {
        $this->getEntityManager()->persist($movementDifficulty);
        $this->getEntityManager()->flush();

        return $movementDifficulty;
    }
}
