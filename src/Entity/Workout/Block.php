<?php

namespace App\Entity\Workout;

use App\Repository\Workout\BlockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockRepository::class)]
class Block
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private int $rounds;

    #[ORM\ManyToMany(targetEntity: MovementCluster::class)]
    private Collection $movementClusters;

    #[ORM\Column(nullable: true)]
    private ?int $restTime = null;

    public function __construct()
    {
        $this->movementClusters = new ArrayCollection();
    }

    public function getId(): ?int
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
}
