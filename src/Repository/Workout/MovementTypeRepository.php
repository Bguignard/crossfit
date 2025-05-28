<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeasureUnitEnum>
 *
 * @method MeasureUnitEnum|null find($id, $lockMode = null, $lockVersion = null)
 * @method MeasureUnitEnum|null findOneBy(array $criteria, array $orderBy = null)
 * @method MeasureUnitEnum[]    findAll()
 * @method MeasureUnitEnum[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementTypeRepository extends ServiceEntityRepository implements MovementTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeasureUnitEnum::class);
    }
}
