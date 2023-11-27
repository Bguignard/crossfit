<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementDetail>
 *
 * @method MovementDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementDetail[]    findAll()
 * @method MovementDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementDetailRepository extends ServiceEntityRepository implements MovementDetailRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementDetail::class);
    }
}
