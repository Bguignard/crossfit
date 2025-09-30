<?php

namespace App\Repository\WorkoutGeneration;

use App\Entity\WorkoutGeneration\WorkoutGeneration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutGeneration>
 *
 * @method WorkoutGeneration|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutGeneration|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutGeneration[]    findAll()
 * @method WorkoutGeneration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutGenerationRepository extends ServiceEntityRepository implements WorkoutGenerationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutGeneration::class);
    }

    public function save(WorkoutGeneration $workoutGeneration): WorkoutGeneration
    {
        $this->_em->persist($workoutGeneration);
        $this->_em->flush();

        return $workoutGeneration;
    }
}
