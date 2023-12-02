<?php

namespace App\Repository\Workout;

use App\Enum\RepUnitEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepUnitEnum>
 *
 * @method RepUnitEnum|null find($id, $lockMode = null, $lockVersion = null)
 * @method RepUnitEnum|null findOneBy(array $criteria, array $orderBy = null)
 * @method RepUnitEnum[]    findAll()
 * @method RepUnitEnum[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementTypeRepository extends ServiceEntityRepository implements MovementTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepUnitEnum::class);
    }
}
