<?php

namespace App\Entity\Workout;

use App\Entity\ConvertibleToDTOInterface;
use App\Repository\Workout\BlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BlockRepository::class)]
class Block implements ConvertibleToDTOInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(nullable: false)]
    private int $rounds;

    #[ORM\ManyToMany(targetEntity: MovementCluster::class, cascade: ['persist'])]
    private Collection $movementClusters;

    #[ORM\Column(nullable: true)] // Time in SECONDS
    private ?int $restTime;

    #[ORM\Column(nullable: false)]
    private int $orderInWorkout;

    public function __construct(
        int $rounds,
        int $orderInWorkout,
        array $movementClusters,
        ?int $restTime = null,
    ) {
        $this->rounds = $rounds;
        $this->orderInWorkout = $orderInWorkout;
        $this->movementClusters = new ArrayCollection();
        foreach ($movementClusters as $movementCluster) {
            $this->addMovementCluster($movementCluster);
        }
        $this->restTime = $restTime;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getRounds(): ?int
    {
        return $this->rounds;
    }

    public function setRounds(int $rounds): static
    {
        $this->rounds = $rounds;

        return $this;
    }

    /**
     * @return Collection<int, MovementCluster>
     */
    public function getMovementClusters(): Collection
    {
        return $this->movementClusters;
    }

    public function addMovementCluster(MovementCluster $movementCluster): static
    {
        if (!$this->movementClusters->contains($movementCluster)) {
            $this->movementClusters->add($movementCluster);
        }

        return $this;
    }

    public function removeMovementCluster(MovementCluster $movementCluster): static
    {
        $this->movementClusters->removeElement($movementCluster);

        return $this;
    }

    public function getRestTime(): ?int
    {
        return $this->restTime;
    }

    public function setRestTime(?int $restTime): static
    {
        $this->restTime = $restTime;

        return $this;
    }

    public function getOrderInWorkout(): int
    {
        return $this->orderInWorkout;
    }

    public function setOrderInWorkout(int $orderInWorkout): Block
    {
        $this->orderInWorkout = $orderInWorkout;

        return $this;
    }
}
