<?php

namespace App\Entity\Workout;

use App\Enum\WorkoutType;
use App\Repository\Workout\WorkoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
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

    #[ORM\ManyToMany(targetEntity: Block::class, cascade: ['persist'])]
    private Collection $blocks;

    #[ORM\Column(nullable: true)]
    private ?int $timeCap;

    #[ORM\Column(type: 'string', nullable: true, enumType: WorkoutType::class)]
    private ?WorkoutType $workoutType;

    #[ORM\ManyToOne(targetEntity: WorkoutOrigin::class, cascade: ['persist'])]
    private WorkoutOrigin $workoutOrigin;

    public function __construct(
        ?string $name,
        ?int $numberOfRounds,
        ?int $timeCap,
        ?WorkoutType $workoutType,
        WorkoutOrigin $workoutOrigin,
        array $blocks,
    ) {
        $this->name = $name;
        $this->numberOfRounds = $numberOfRounds;
        $this->timeCap = $timeCap;
        $this->workoutType = $workoutType;
        $this->workoutOrigin = $workoutOrigin;
        $this->blocks = new ArrayCollection();
        foreach ($blocks as $block) {
            $this->addBlock($block);
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
}
