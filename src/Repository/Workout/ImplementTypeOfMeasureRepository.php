<?php

namespace App\Repository\Workout;

use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImplementTypeOfAdjustableMeasureUnit>
 *
 * @method ImplementTypeOfAdjustableMeasureUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImplementTypeOfAdjustableMeasureUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImplementTypeOfAdjustableMeasureUnit[]    findAll()
 * @method ImplementTypeOfAdjustableMeasureUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImplementTypeOfMeasureRepository extends ServiceEntityRepository implements ImplementTypeOfMeasureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImplementTypeOfAdjustableMeasureUnit::class);
    }
}
