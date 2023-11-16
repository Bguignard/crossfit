<?php

namespace App\Entities\Workout;

use App\Repositories\Workout\MovementClusterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovementClusterRepository::class)]
class MovementCluster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Movement $movement;

    #[ORM\Column]
    private float $numberOfClusterUnits;

    #[ORM\Column(type: 'string', enumType: RepUnit::class)]
    private RepUnit $repUnit;

    #[ORM\ManyToOne]
    private ?MovementImplement $movementImplement = null;

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

    public function getNumberOfClusterUnits(): ?float
    {
        return $this->numberOfClusterUnits;
    }

    public function setNumberOfClusterUnits(float $numberOfClusterUnits): static
    {
        $this->numberOfClusterUnits = $numberOfClusterUnits;

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

    public function getMovementImplement(): ?MovementImplement
    {
        return $this->movementImplement;
    }

    public function setMovementImplement(?MovementImplement $movementImplement): static
    {
        $this->movementImplement = $movementImplement;

        return $this;
    }



}
