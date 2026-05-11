<?php

namespace App\Entity\Competition;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'score')]
#[ApiResource(operations: [new Get()])]
class Score
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 32, enumType: ScoreTypeEnum::class)]
    private ScoreTypeEnum $type;

    #[ORM\Column(length: 255)]
    private string $rawValue;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayValue = null;

    #[ORM\Column(nullable: true)]
    private ?float $numericValue = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeInSeconds = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $unit = null;

    public function __construct(ScoreTypeEnum $type, string $rawValue)
    {
        $this->type = $type;
        $this->rawValue = $rawValue;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getType(): ScoreTypeEnum
    {
        return $this->type;
    }

    public function setType(ScoreTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    public function setRawValue(string $rawValue): self
    {
        $this->rawValue = $rawValue;

        return $this;
    }

    public function getDisplayValue(): ?string
    {
        return $this->displayValue;
    }

    public function setDisplayValue(?string $displayValue): self
    {
        $this->displayValue = $displayValue;

        return $this;
    }

    public function getNumericValue(): ?float
    {
        return $this->numericValue;
    }

    public function setNumericValue(?float $numericValue): self
    {
        $this->numericValue = $numericValue;

        return $this;
    }

    public function getTimeInSeconds(): ?int
    {
        return $this->timeInSeconds;
    }

    public function setTimeInSeconds(?int $timeInSeconds): self
    {
        $this->timeInSeconds = $timeInSeconds;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }
}
