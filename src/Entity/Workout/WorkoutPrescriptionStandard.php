<?php

namespace App\Entity\Workout;

use App\Repository\Workout\WorkoutPrescriptionStandardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutPrescriptionStandardRepository::class)]
#[ORM\Index(columns: ['sport', 'level_name', 'division'], name: 'idx_workout_prescription_standard_scope')]
#[ORM\Index(columns: ['movement_name'], name: 'idx_workout_prescription_standard_movement')]
#[ORM\Index(columns: ['implement_name'], name: 'idx_workout_prescription_standard_implement')]
class WorkoutPrescriptionStandard
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    public function __construct(
        #[ORM\Column(length: 64)]
        private string $sourceName,
        #[ORM\Column(length: 32)]
        private string $sport,
        #[ORM\Column(length: 64, nullable: true)]
        private ?string $levelName,
        #[ORM\Column(length: 32)]
        private string $division,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $movementName,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $implementName,
        #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
        private string $quantity,
        #[ORM\Column(length: 32)]
        private string $unit,
        #[ORM\Column(type: 'integer')]
        private int $quantityMultiplier = 1,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $contextLabel = null,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $notes = null,
        #[ORM\Column(type: 'integer')]
        private int $priority = 100,
    ) {
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getSport(): string
    {
        return $this->sport;
    }

    public function getLevelName(): ?string
    {
        return $this->levelName;
    }

    public function getDivision(): string
    {
        return $this->division;
    }

    public function getMovementName(): ?string
    {
        return $this->movementName;
    }

    public function getImplementName(): ?string
    {
        return $this->implementName;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getQuantityMultiplier(): int
    {
        return $this->quantityMultiplier;
    }

    public function getContextLabel(): ?string
    {
        return $this->contextLabel;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function label(): string
    {
        $quantity = rtrim(rtrim(number_format($this->getQuantity(), 2, '.', ''), '0'), '.');
        $load = ($this->quantityMultiplier > 1 ? $this->quantityMultiplier.' x ' : '').$quantity.' '.$this->unit;
        $target = $this->movementName ?? $this->implementName ?? 'loaded movement';
        $context = $this->contextLabel === null ? '' : ' ('.$this->contextLabel.')';
        $notes = $this->notes === null ? '' : ' - '.$this->notes;

        return sprintf('%s %s: %s%s%s', ucfirst($this->division), $target, $load, $context, $notes);
    }
}
