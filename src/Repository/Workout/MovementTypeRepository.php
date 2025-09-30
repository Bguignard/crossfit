<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementType>
 *
 * @method MovementType|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementType|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementType[]    findAll()
 * @method MovementType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementTypeRepository extends ServiceEntityRepository implements MovementTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementType::class);
    }
}
