<?php

namespace App\Entity\WorkoutGeneration;

use App\Entity\Security\User;
use App\Repository\WorkoutGeneration\WorkoutAiGenerationUsageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutAiGenerationUsageRepository::class)]
#[ORM\Table(name: 'workout_ai_generation_usage')]
#[ORM\Index(name: 'IDX_WORKOUT_AI_USAGE_USER_CREATED', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'IDX_WORKOUT_AI_USAGE_VISITOR_CREATED', columns: ['visitor_hash', 'created_at'])]
class WorkoutAiGenerationUsage
{
    public const ACTOR_ANONYMOUS = 'anonymous';
    public const ACTOR_USER = 'user';
    public const ACTOR_ADMIN = 'admin';

    public const ENDPOINT_WORKOUT = 'workout_generation_flow_workout';
    public const ENDPOINT_VARIANTS = 'workout_generation_flow_variants';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private string $actorType;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $visitorHash = null;

    #[ORM\Column(length: 64)]
    private string $endpoint;

    #[ORM\Column(length: 64)]
    private string $generationType;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(nullable: true)]
    private ?int $promptTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $completionTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalTokens = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 6, nullable: true)]
    private ?string $estimatedCostUsd = null;

    #[ORM\Column]
    private bool $quotaCounted;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $aiUsage
     */
    public function __construct(
        string $actorType,
        string $endpoint,
        string $generationType,
        string $status,
        bool $quotaCounted,
        ?User $user = null,
        ?string $visitorHash = null,
        ?array $aiUsage = null,
        ?string $failureReason = null,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->actorType = $actorType;
        $this->endpoint = $endpoint;
        $this->generationType = $generationType;
        $this->status = $status;
        $this->quotaCounted = $quotaCounted;
        $this->user = $user;
        $this->visitorHash = $visitorHash;
        $this->failureReason = $failureReason === null ? null : substr($failureReason, 0, 2000);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->applyAiUsage($aiUsage);
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getActorType(): string
    {
        return $this->actorType;
    }

    public function getVisitorHash(): ?string
    {
        return $this->visitorHash;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getGenerationType(): string
    {
        return $this->generationType;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function getEstimatedCostUsd(): ?string
    {
        return $this->estimatedCostUsd;
    }

    public function setEstimatedCostUsd(?string $estimatedCostUsd): self
    {
        $this->estimatedCostUsd = $this->nullableDecimalString($estimatedCostUsd);

        return $this;
    }

    public function isQuotaCounted(): bool
    {
        return $this->quotaCounted;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param array<string, mixed>|null $aiUsage
     */
    private function applyAiUsage(?array $aiUsage): void
    {
        if ($aiUsage === null) {
            return;
        }

        $this->model = is_string($aiUsage['model'] ?? null) ? $aiUsage['model'] : null;
        $this->promptTokens = $this->nullableInt($aiUsage['prompt_tokens'] ?? null);
        $this->completionTokens = $this->nullableInt($aiUsage['completion_tokens'] ?? null);
        $this->totalTokens = $this->nullableInt($aiUsage['total_tokens'] ?? null);
        $this->durationMs = $this->nullableInt($aiUsage['duration_ms'] ?? null);
        $this->estimatedCostUsd = $this->nullableDecimalString($aiUsage['estimated_cost_usd'] ?? null);
    }

    private function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    private function nullableDecimalString(mixed $value): ?string
    {
        if (is_int($value) || is_float($value)) {
            return sprintf('%.6F', $value);
        }
        if (is_string($value) && preg_match('/^\d+(?:\.\d+)?$/', $value) === 1) {
            return $value;
        }

        return null;
    }
}
