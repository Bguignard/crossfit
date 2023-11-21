<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Workout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workout>
 *
 * @method Workout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Workout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Workout[]    findAll()
 * @method Workout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutRepository extends ServiceEntityRepository implements WorkoutRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workout::class);
    }

    public function getByName(string $name): ?Workout
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function getWorkoutsNames(): array
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('w.name');
        $qb->orderBy('w.name', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function getWorkoutsOrigins(): array
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('w.origin');
        $qb->orderBy('w.origin', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function getWorkoutsNamesByOrigin(string $originId): array
    {
        $qb = $this->createQueryBuilder('w');
        $qb->select('w.name');
        $qb->where('w.origin = :origin');
        $qb->setParameter('origin', $originId);
        $qb->orderBy('w.name', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }
}
