<?php

namespace App\Entities\Workout;

use App\Repositories\Workout\WorkoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
class Workout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $numberOfRounds = null;

    #[ORM\ManyToMany(targetEntity: Block::class)]
    private Collection $blocks;

    #[ORM\Column]
    private ?int $timeCap = null;

    #[ORM\Column(length: 255)]
    private ?string $workoutType = null;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNumberOfRounds(): ?int
    {
        return $this->numberOfRounds;
    }

    public function setNumberOfRounds(int $numberOfRounds): static
    {
        $this->numberOfRounds = $numberOfRounds;

        return $this;
    }

    /**
     * @return Collection<int, MovementCluster>
     */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
        }

        return $this;
    }

    public function removeMovementBlock(Block $block): static
    {
        $this->blocks->removeElement($block);

        return $this;
    }

    public function getTimeCap(): ?int
    {
        return $this->timeCap;
    }

    public function setTimeCap(int $timeCap): static
    {
        $this->timeCap = $timeCap;

        return $this;
    }

    public function getWorkoutType(): ?string
    {
        return $this->workoutType;
    }

    public function setWorkoutType(string $workoutType): static
    {
        $this->workoutType = $workoutType;

        return $this;
    }
}
