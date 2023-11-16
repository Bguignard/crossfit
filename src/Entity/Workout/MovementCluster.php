<?php

namespace App\Entity\Workout;

use App\Repository\Workout\MovementClusterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovementClusterRepository::class)]
class MovementCluster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $repetitions; // For example 5 reps, 10 reps, etc. if its 500m run its only 1 rep

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Movement $movement;

    #[ORM\Column]
    private float $movementIntensity; // For example the weight, the distance, the height, etc.

    #[ORM\Column(type: 'string', enumType: RepUnit::class)]
    private RepUnit $repUnit; // For example kg, lbs, meters, feet, etc.

    #[ORM\ManyToMany(targetEntity: Implement::class)]
    private Collection $implements;

    public function __construct(
        int $repetitions,
        ArrayCollection $implements,
        Movement $movement,
        float $movementIntensity,
        RepUnit $repUnit
    ) {
        $this->repetitions = $repetitions;
        $this->implements = $implements;
        $this->movement = $movement;
        $this->movementIntensity = $movementIntensity;
        $this->repUnit = $repUnit;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovement(): ?Movement
    {
        return $this->movement;
    }

    public function setMovement(?Movement $movement): static
    {
        $this->movement = $movement;

        return $this;
    }

    public function getMovementIntensity(): ?float
    {
        return $this->movementIntensity;
    }

    public function setMovementIntensity(float $movementIntensity): static
    {
        $this->movementIntensity = $movementIntensity;

        return $this;
    }

    public function getRepUnit(): RepUnit
    {
        return $this->repUnit;
    }

    public function setRepUnit(RepUnit $repUnit): void
    {
        $this->repUnit = $repUnit;
    }

    public function setImplements(Collection $implements): MovementCluster
    {
        $this->implements = $implements;

        return $this;
    }

    public function getImplements(): Collection
    {
        return $this->implements;
    }

    /**
     * @return int
     */
    public function getRepetitions(): int
    {
        return $this->repetitions;
    }

    /**
     * @param int $repetitions
     */
    public function setRepetitions(int $repetitions): void
    {
        $this->repetitions = $repetitions;
    }

}
