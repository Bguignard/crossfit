<?php

namespace App\Entity\Product;

use App\Entity\Product\Enum\PerformanceMetricCategoryEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Security\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_performance_profile')]
class UserPerformanceProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'performanceProfiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: UserPerformanceMetric::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $metrics;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->metrics = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function markCompleted(?\DateTimeImmutable $completedAt = null): self
    {
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    /**
     * @return Collection<int, UserPerformanceMetric>
     */
    public function getMetrics(): Collection
    {
        return $this->metrics;
    }

    public function addMetric(UserPerformanceMetric $metric): self
    {
        if (!$this->metrics->contains($metric)) {
            $this->metrics->add($metric);
        }
        $this->touch();

        return $this;
    }

    public function getMetric(PerformanceMetricKeyEnum $metricKey): ?UserPerformanceMetric
    {
        foreach ($this->metrics as $metric) {
            if ($metric->getMetricKey() === $metricKey) {
                return $metric;
            }
        }

        return null;
    }

    public function hasProvidedMetric(PerformanceMetricKeyEnum $metricKey): bool
    {
        return $this->getMetric($metricKey)?->hasValue() ?? false;
    }

    public function hasPositiveSkill(PerformanceMetricKeyEnum $metricKey): bool
    {
        return $this->getMetric($metricKey)?->getBooleanValue() === true;
    }

    /**
     * @return list<PerformanceMetricKeyEnum>
     */
    public function availableGymnasticsCapacityMetrics(): array
    {
        $availableMetrics = [];

        foreach (PerformanceMetricKeyEnum::cases() as $metricKey) {
            if ($metricKey->category() !== PerformanceMetricCategoryEnum::GYMNASTICS_CAPACITY) {
                continue;
            }

            $requiredSkill = $metricKey->requiredSkill();
            if ($requiredSkill === null || $this->hasPositiveSkill($requiredSkill)) {
                $availableMetrics[] = $metricKey;
            }
        }

        return $availableMetrics;
    }

    public function isEligibleForPerformanceAnalysis(): bool
    {
        return $this->hasAllMetrics(PerformanceMetricKeyEnum::requiredStrengthMetrics())
            && $this->hasAllMetrics(PerformanceMetricKeyEnum::requiredWeightliftingMetrics())
            && $this->hasAllMetrics(PerformanceMetricKeyEnum::gymnasticsSkillMetrics())
            && $this->countProvidedMetrics(PerformanceMetricKeyEnum::cardioMetrics()) >= 3;
    }

    /**
     * @param list<PerformanceMetricKeyEnum> $metricKeys
     */
    private function hasAllMetrics(array $metricKeys): bool
    {
        foreach ($metricKeys as $metricKey) {
            if (!$this->hasProvidedMetric($metricKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<PerformanceMetricKeyEnum> $metricKeys
     */
    private function countProvidedMetrics(array $metricKeys): int
    {
        $count = 0;
        foreach ($metricKeys as $metricKey) {
            if ($this->hasProvidedMetric($metricKey)) {
                ++$count;
            }
        }

        return $count;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
