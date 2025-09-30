<?php

namespace App\Entity\WorkoutGeneration;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutType;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutGenerationRepository::class)]
#[ApiResource]
class WorkoutGeneration
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    // todo : add the id of user who created it once user management is done

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(nullable: false)]
    private int $timeCap;

    #[ORM\ManyToMany(targetEntity: MovementType::class, cascade: ['persist'])]
    private Collection $movementTypes;

    #[ORM\Column(nullable: false)]
    private int $numberOfDifferentMovements;

    #[ORM\ManyToOne(targetEntity: WorkoutType::class, cascade: ['persist'])]
    private WorkoutType $workoutType;

    #[ORM\ManyToOne(targetEntity: MovementDifficulty::class, cascade: ['persist'])]
    private MovementDifficulty $movementDifficulty;

    #[ORM\Column(type: 'string', nullable: true, enumType: WorkoutMovementGenerationTypeEnum::class)]
    private WorkoutMovementGenerationTypeEnum $movementGenerationType;

    #[ORM\ManyToMany(targetEntity: Movement::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'workout_generation_banned_movements')]
    private Collection $bannedMovements;

    #[ORM\ManyToMany(targetEntity: Implement::class, cascade: ['persist'])]
    private Collection $availableImplements;

    #[ORM\ManyToMany(targetEntity: BodyPart::class, cascade: ['persist'])]
    private Collection $mandatoryBodyParts;

    #[ORM\ManyToMany(targetEntity: Movement::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'workout_generation_mandatory_movements')]
    private Collection $mandatoryMovements;

    #[ORM\Column(nullable: true)]
    private ?int $intervalsTime;

    #[ORM\Column(nullable: true)]
    private ?int $intervalsRestTime;

    #[ORM\Column(nullable: true)]
    private ?int $numberOfRounds;

    public function __construct(
        string $name,
        int $timeCap,
        array $movementTypes,
        int $numberOfDifferentMovements,
        WorkoutType $workoutType,
        MovementDifficulty $movementDifficulty,
        WorkoutMovementGenerationTypeEnum $movementGenerationType,
    ) {
        $this->name = $name;
        $this->timeCap = $timeCap;
        $this->movementTypes = new ArrayCollection();
        foreach ($movementTypes as $movementType) {
            $this->addMovementType($movementType);
        }
        $this->numberOfDifferentMovements = $numberOfDifferentMovements;
        $this->workoutType = $workoutType;
        $this->movementDifficulty = $movementDifficulty;
        $this->movementGenerationType = $movementGenerationType;
        $this->bannedMovements = new ArrayCollection();
        $this->availableImplements = new ArrayCollection();
        $this->mandatoryBodyParts = new ArrayCollection();
        $this->mandatoryMovements = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(?Uuid $id): WorkoutGeneration
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): WorkoutGeneration
    {
        $this->name = $name;

        return $this;
    }

    public function getTimeCap(): int
    {
        return $this->timeCap;
    }

    public function setTimeCap(int $timeCap): WorkoutGeneration
    {
        $this->timeCap = $timeCap;

        return $this;
    }

    public function addMovementType(MovementType $movementType): WorkoutGeneration
    {
        if (!$this->movementTypes->contains($movementType)) {
            $this->movementTypes->add($movementType);
        }

        return $this;
    }

    public function getMovementTypes(): Collection
    {
        return $this->movementTypes;
    }

    public function setMovementTypes(Collection $movementTypes): WorkoutGeneration
    {
        $this->movementTypes = $movementTypes;

        return $this;
    }

    public function getNumberOfDifferentMovements(): int
    {
        return $this->numberOfDifferentMovements;
    }

    public function setNumberOfDifferentMovements(int $numberOfDifferentMovements): WorkoutGeneration
    {
        $this->numberOfDifferentMovements = $numberOfDifferentMovements;

        return $this;
    }

    public function getWorkoutType(): WorkoutType
    {
        return $this->workoutType;
    }

    public function setWorkoutType(WorkoutType $workoutType): WorkoutGeneration
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getMovementDifficulty(): MovementDifficulty
    {
        return $this->movementDifficulty;
    }

    public function setMovementDifficulty(MovementDifficulty $movementDifficulty): WorkoutGeneration
    {
        $this->movementDifficulty = $movementDifficulty;

        return $this;
    }

    public function getMovementGenerationType(): WorkoutMovementGenerationTypeEnum
    {
        return $this->movementGenerationType;
    }

    public function setMovementGenerationType(WorkoutMovementGenerationTypeEnum $movementGenerationType): WorkoutGeneration
    {
        $this->movementGenerationType = $movementGenerationType;

        return $this;
    }

    public function addBannedMovement(Movement $bannedMovement): WorkoutGeneration
    {
        if (!$this->bannedMovements->contains($bannedMovement)) {
            $this->bannedMovements->add($bannedMovement);
        }

        return $this;
    }

    public function getBannedMovements(): Collection
    {
        return $this->bannedMovements;
    }

    public function setBannedMovements(Collection $bannedMovements): WorkoutGeneration
    {
        $this->bannedMovements = $bannedMovements;

        return $this;
    }

    public function addAvailableImplement(Implement $availableImplement): WorkoutGeneration
    {
        if (!$this->availableImplements->contains($availableImplement)) {
            $this->availableImplements->add($availableImplement);
        }

        return $this;
    }

    public function getAvailableImplements(): Collection
    {
        return $this->availableImplements;
    }

    public function setAvailableImplements(Collection $availableImplements): WorkoutGeneration
    {
        $this->availableImplements = $availableImplements;

        return $this;
    }

    public function addMandatoryBodyPart(BodyPart $mandatoryBodyPart): WorkoutGeneration
    {
        if (!$this->mandatoryBodyParts->contains($mandatoryBodyPart)) {
            $this->mandatoryBodyParts->add($mandatoryBodyPart);
        }

        return $this;
    }

    public function getMandatoryBodyParts(): Collection
    {
        return $this->mandatoryBodyParts;
    }

    public function setMandatoryBodyParts(Collection $mandatoryBodyParts): WorkoutGeneration
    {
        $this->mandatoryBodyParts = $mandatoryBodyParts;

        return $this;
    }

    public function getMandatoryMovements(): Collection
    {
        return $this->mandatoryMovements;
    }

    public function addMandatoryMovement(Movement $mandatoryMovement): WorkoutGeneration
    {
        if (!$this->mandatoryMovements->contains($mandatoryMovement)) {
            $this->mandatoryMovements->add($mandatoryMovement);
        }

        return $this;
    }

    public function setMandatoryMovements(Collection $mandatoryMovements): WorkoutGeneration
    {
        $this->mandatoryMovements = $mandatoryMovements;

        return $this;
    }

    public function getIntervalsTime(): ?int
    {
        return $this->intervalsTime;
    }

    public function setIntervalsTime(?int $intervalsTime): WorkoutGeneration
    {
        $this->intervalsTime = $intervalsTime;

        return $this;
    }

    public function getIntervalsRestTime(): ?int
    {
        return $this->intervalsRestTime;
    }

    public function setIntervalsRestTime(?int $intervalsRestTime): WorkoutGeneration
    {
        $this->intervalsRestTime = $intervalsRestTime;

        return $this;
    }

    public function getNumberOfRounds(): ?int
    {
        return $this->numberOfRounds;
    }

    public function setNumberOfRounds(?int $numberOfRounds): WorkoutGeneration
    {
        $this->numberOfRounds = $numberOfRounds;

        return $this;
    }
}
