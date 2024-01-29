<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MeasureUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeasureUnit>
 *
 * @method MeasureUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method MeasureUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method MeasureUnit[]    findAll()
 * @method MeasureUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MeasureUnitRepository extends ServiceEntityRepository implements MeasureUnitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeasureUnit::class);
    }
}
