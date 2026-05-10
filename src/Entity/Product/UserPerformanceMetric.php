<?php

namespace App\Entity\Product;

use App\Entity\Product\Enum\PerformanceMetricCategoryEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\PerformanceMetricValueTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_performance_metric')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_PERFORMANCE_METRIC_PROFILE_KEY', columns: ['profile_id', 'metric_key'])]
class UserPerformanceMetric
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: UserPerformanceProfile::class, inversedBy: 'metrics')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserPerformanceProfile $profile;

    #[ORM\Column(type: 'string', length: 96, enumType: PerformanceMetricKeyEnum::class)]
    private PerformanceMetricKeyEnum $metricKey;

    #[ORM\Column(type: 'string', length: 32, enumType: PerformanceMetricCategoryEnum::class)]
    private PerformanceMetricCategoryEnum $category;

    #[ORM\Column(type: 'string', length: 32, enumType: PerformanceMetricValueTypeEnum::class)]
    private PerformanceMetricValueTypeEnum $valueType;

    #[ORM\Column(nullable: true)]
    private ?float $numericValue = null;

    #[ORM\Column(nullable: true)]
    private ?bool $booleanValue = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(UserPerformanceProfile $profile, PerformanceMetricKeyEnum $metricKey)
    {
        $this->profile = $profile;
        $this->metricKey = $metricKey;
        $this->category = $metricKey->category();
        $this->valueType = $metricKey->valueType();
        $this->unit = $metricKey->defaultUnit();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $profile->addMetric($this);
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getProfile(): UserPerformanceProfile
    {
        return $this->profile;
    }

    public function getMetricKey(): PerformanceMetricKeyEnum
    {
        return $this->metricKey;
    }

    public function getCategory(): PerformanceMetricCategoryEnum
    {
        return $this->category;
    }

    public function getValueType(): PerformanceMetricValueTypeEnum
    {
        return $this->valueType;
    }

    public function getNumericValue(): ?float
    {
        return $this->numericValue;
    }

    public function setNumericValue(?float $numericValue, ?string $unit = null): self
    {
        $this->numericValue = $numericValue;
        $this->unit = $unit ?? $this->unit;
        $this->touch();

        return $this;
    }

    public function getBooleanValue(): ?bool
    {
        return $this->booleanValue;
    }

    public function setBooleanValue(?bool $booleanValue): self
    {
        $this->booleanValue = $booleanValue;
        $this->touch();

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;
        $this->touch();

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->touch();

        return $this;
    }

    public function hasValue(): bool
    {
        return match ($this->valueType) {
            PerformanceMetricValueTypeEnum::BOOLEAN => $this->booleanValue !== null,
            default => $this->numericValue !== null,
        };
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
