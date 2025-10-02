<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Workout\SimpleWorkoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SimpleWorkoutRepository::class)]
#[ApiResource]
class SimpleWorkout
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Assert\Length(min: 20, max: 5000)]
    private string $flow;

    #[ORM\Column(nullable: true)]
    private ?int $timeCap; // time cap in minutes

    #[ORM\ManyToOne(targetEntity: WorkoutOrigin::class, cascade: ['persist'])]
    private WorkoutOrigin $workoutOrigin;

    #[ORM\ManyToMany(targetEntity: Implement::class)]
    private Collection $implements;

    #[ORM\ManyToMany(targetEntity: Movement::class)]
    private Collection $movements;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        ?string $name,
        ?string $flow,
        ?int $timeCap,
        WorkoutOrigin $workoutOrigin,
        array $implements,
        array $movements,
    ) {
        $this->implements = new ArrayCollection();
        $this->movements = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();

        $this->name = $name;
        $this->flow = $flow;
        $this->timeCap = $timeCap;
        $this->workoutOrigin = $workoutOrigin;
        foreach ($implements as $implement) {
            $this->implements->add($implement);
        }
        foreach ($movements as $movement) {
            $this->movements->add($movement);
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): SimpleWorkout
    {
        $this->name = $name;

        return $this;
    }

    public function getFlow(): ?string
    {
        return $this->flow;
    }

    public function setFlow(?string $flow): SimpleWorkout
    {
        $this->flow = $flow;

        return $this;
    }

    public function getTimeCap(): ?int
    {
        return $this->timeCap;
    }

    public function setTimeCap(?int $timeCap): SimpleWorkout
    {
        $this->timeCap = $timeCap;

        return $this;
    }

    public function getWorkoutOrigin(): WorkoutOrigin
    {
        return $this->workoutOrigin;
    }

    public function setWorkoutOrigin(WorkoutOrigin $workoutOrigin): SimpleWorkout
    {
        $this->workoutOrigin = $workoutOrigin;

        return $this;
    }

    public function getImplements(): Collection
    {
        return $this->implements;
    }

    public function setImplements(Collection $implements): SimpleWorkout
    {
        $this->implements = $implements;

        return $this;
    }

    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function setMovements(Collection $movements): SimpleWorkout
    {
        $this->movements = $movements;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
