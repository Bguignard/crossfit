<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\WorkoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_WORKOUT_SOURCE_EXTERNAL', columns: ['source_name', 'external_id'])]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class Workout
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfRounds;

    #[ORM\Column(type: 'text')]
    private string $flow;

    #[ORM\Column(nullable: true)]
    private ?int $timeCap; // time cap in minutes

    #[ORM\ManyToOne(targetEntity: WorkoutType::class, cascade: ['persist'])]
    private ?WorkoutType $workoutType;

    #[ORM\ManyToOne(targetEntity: WorkoutOrigin::class, cascade: ['persist'])]
    private WorkoutOrigin $workoutOrigin;

    #[ORM\ManyToMany(targetEntity: Implement::class)]
    private Collection $implements;

    #[ORM\ManyToMany(targetEntity: Movement::class)]
    private Collection $movements;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sourceName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\OneToOne(targetEntity: WorkoutGeneration::class, cascade: ['remove'])]
    private ?WorkoutGeneration $workoutGeneration = null;

    public function __construct(
        ?string $name,
        string $flow,
        ?int $numberOfRounds,
        ?int $timeCap,
        ?WorkoutType $workoutType,
        WorkoutOrigin $workoutOrigin,
        array $implements = [],
        array $movements = [],
    ) {
        $this->implements = new ArrayCollection();
        $this->movements = new ArrayCollection();

        $this->name = $name;
        $this->flow = $flow;
        $this->createdAt = new \DateTimeImmutable();
        $this->numberOfRounds = $numberOfRounds;
        $this->timeCap = $timeCap;
        $this->workoutType = $workoutType;
        $this->workoutOrigin = $workoutOrigin;
        foreach ($implements as $implement) {
            $this->addImplement($implement);
        }
        foreach ($movements as $movement) {
            $this->addMovement($movement);
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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFlow(): string
    {
        return $this->flow;
    }

    public function setFlow(string $flow): static
    {
        $this->flow = $flow;

        return $this;
    }

    public function getNumberOfRounds(): ?int
    {
        return $this->numberOfRounds;
    }

    public function setNumberOfRounds(?int $numberOfRounds): static
    {
        $this->numberOfRounds = $numberOfRounds;

        return $this;
    }

    /**
     * @return Collection<int, Implement>
     */
    public function getImplements(): Collection
    {
        return $this->implements;
    }

    public function addImplement(Implement $implement): static
    {
        if (!$this->implements->contains($implement)) {
            $this->implements->add($implement);
        }

        return $this;
    }

    public function removeImplement(Implement $implement): static
    {
        $this->implements->removeElement($implement);

        return $this;
    }

    /**
     * @return Collection<int, Movement>
     */
    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function addMovement(Movement $movement): static
    {
        if (!$this->movements->contains($movement)) {
            $this->movements->add($movement);
        }

        return $this;
    }

    public function removeMovement(Movement $movement): static
    {
        $this->movements->removeElement($movement);

        return $this;
    }

    public function getTimeCap(): ?int
    {
        return $this->timeCap;
    }

    public function setTimeCap(?int $timeCap): static
    {
        $this->timeCap = $timeCap;

        return $this;
    }

    public function getWorkoutType(): ?WorkoutType
    {
        return $this->workoutType;
    }

    public function setWorkoutType(?WorkoutType $workoutType): static
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getWorkoutOrigin(): WorkoutOrigin
    {
        return $this->workoutOrigin;
    }

    public function setWorkoutOrigin(WorkoutOrigin $workoutOrigin): static
    {
        $this->workoutOrigin = $workoutOrigin;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getWorkoutGeneration(): ?WorkoutGeneration
    {
        return $this->workoutGeneration;
    }

    public function setWorkoutGeneration(?WorkoutGeneration $workoutGeneration): static
    {
        $this->workoutGeneration = $workoutGeneration;

        return $this;
    }

    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    public function setSourceName(?string $sourceName): static
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }
}
