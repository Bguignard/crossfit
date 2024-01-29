<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Block;
use App\Entity\Workout\MovementExecutionTimeForMeasureUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementExecutionTimeForMeasureUnit>
 *
 * @method MovementExecutionTimeForMeasureUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementExecutionTimeForMeasureUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementExecutionTimeForMeasureUnit[]    findAll()
 * @method MovementExecutionTimeForMeasureUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementExecutionTimeForMeasureUnitRepository extends ServiceEntityRepository implements MovementExecutionTimeForMeasureUnitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Block::class);
    }
}
