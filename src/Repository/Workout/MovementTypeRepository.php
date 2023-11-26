<?php

namespace App\Repository\Workout;

use App\Entity\Workout\RepUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepUnit>
 *
 * @method RepUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method RepUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method RepUnit[]    findAll()
 * @method RepUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementTypeRepository extends ServiceEntityRepository implements MovementTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepUnit::class);
    }
}
