<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Repository\Workout\MovementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
#[ApiResource]
class Movement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Muscle::class, inversedBy: 'movements')]
    private Collection $muscles;

    #[ORM\Column(nullable: false)]
    private int $difficulty;

    #[ORM\Column(type: 'string', enumType: MovementTypeEnum::class)]
    private MovementTypeEnum $movementType;

    #[ORM\ManyToMany(targetEntity: Implement::class, inversedBy: 'movements')]
    private Collection $possibleImplements;

    #[ORM\ManyToMany(targetEntity: MovementExecutionTimeForMeasureUnit::class, inversedBy: 'movements')]
    private Collection $movementExecutionTimeForMeasureUnits;

    public function __construct(
        string $name,
        int $difficulty,
        MovementTypeEnum $movementType,
    ) {
        $this->possibleImplements = new ArrayCollection();
        $this->muscles = new ArrayCollection();
        $this->movementExecutionTimeForMeasureUnits = new ArrayCollection();
        $this->name = $name;
        $this->difficulty = $difficulty;
        $this->movementType = $movementType;
    }

    public function getId(): Uuid
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

    /**
     * @return Collection<int, Muscle>
     */
    public function getMuscles(): Collection
    {
        return $this->muscles;
    }

    public function addMuscle(Muscle $muscle): static
    {
        if (!$this->muscles->contains($muscle)) {
            $this->muscles->add($muscle);
        }

        return $this;
    }

    public function removeMuscle(Muscle $muscle): static
    {
        $this->muscles->removeElement($muscle);

        return $this;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getMovementType(): MovementTypeEnum
    {
        return $this->movementType;
    }

    public function setMuscles(array $muscles): Movement
    {
        foreach ($muscles as $bodyPart) {
            $this->addMuscle($bodyPart);
        }

        return $this;
    }

    public function addPossibleImplement(Implement $implement): Movement
    {
        if (!$this->possibleImplements->contains($implement)) {
            $this->possibleImplements->add($implement);
        }

        return $this;
    }

    public function setPossibleImplements(array $possibleImplements): Movement
    {
        foreach ($possibleImplements as $possibleImplement) {
            $this->addPossibleImplement($possibleImplement);
        }

        return $this;
    }

    public function getPossibleImplements(): Collection
    {
        return $this->possibleImplements;
    }

    public function getMovementExecutionTimeForMeasureUnits(): Collection
    {
        return $this->movementExecutionTimeForMeasureUnits;
    }

    public function addMovementExecutionTimeForMeasureUnits(MovementExecutionTimeForMeasureUnit $movementExecutionTimeForMeasureUnit): Movement
    {
        if (!$this->movementExecutionTimeForMeasureUnits->contains($movementExecutionTimeForMeasureUnit)) {
            $this->movementExecutionTimeForMeasureUnits->add($movementExecutionTimeForMeasureUnit);
        }

        return $this;
    }

    public function setMovementExecutionTimeForMeasureUnits(array $movementExecutionTimeForMeasureUnits): Movement
    {
        foreach ($movementExecutionTimeForMeasureUnits as $movementExecutionTimeForMeasureUnit) {
            $this->addMovementExecutionTimeForMeasureUnits($movementExecutionTimeForMeasureUnit);
        }

        return $this;
    }
}
