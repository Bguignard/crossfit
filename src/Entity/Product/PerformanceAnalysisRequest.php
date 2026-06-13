<?php

namespace App\Entity\Product;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'performance_analysis_request')]
class PerformanceAnalysisRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'performanceAnalysisRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: UserPerformanceProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private UserPerformanceProfile $performanceProfile;

    #[ORM\ManyToOne(targetEntity: UserAthleteProfile::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UserAthleteProfile $athleteProfile = null;

    #[ORM\Column(type: 'string', length: 32, enumType: AnalysisRequestStatusEnum::class)]
    private AnalysisRequestStatusEnum $status = AnalysisRequestStatusEnum::DRAFT;

    #[ORM\Column]
    private bool $eligibleAtCreation;

    #[ORM\Column(type: 'json')]
    private array $parameters = [];

    #[ORM\Column(type: 'json')]
    private array $inputSnapshot = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $result = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $queuedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $messengerEnqueuedAt = null;

    public function __construct(
        User $user,
        UserPerformanceProfile $performanceProfile,
        ?UserAthleteProfile $athleteProfile = null,
        array $parameters = [],
        array $inputSnapshot = [],
    ) {
        $this->user = $user;
        $this->performanceProfile = $performanceProfile;
        $this->athleteProfile = $athleteProfile;
        $this->parameters = $parameters;
        $this->inputSnapshot = $inputSnapshot;
        $this->eligibleAtCreation = $performanceProfile->isEligibleForPerformanceAnalysis() || $athleteProfile !== null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPerformanceProfile(): UserPerformanceProfile
    {
        return $this->performanceProfile;
    }

    public function getAthleteProfile(): ?UserAthleteProfile
    {
        return $this->athleteProfile;
    }

    public function getStatus(): AnalysisRequestStatusEnum
    {
        return $this->status;
    }

    public function wasEligibleAtCreation(): bool
    {
        return $this->eligibleAtCreation;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        $this->touch();

        return $this;
    }

    public function getInputSnapshot(): array
    {
        return $this->inputSnapshot;
    }

    public function setInputSnapshot(array $inputSnapshot): self
    {
        $this->inputSnapshot = $inputSnapshot;
        $this->touch();

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function markQueued(?\DateTimeImmutable $queuedAt = null): self
    {
        $this->status = AnalysisRequestStatusEnum::QUEUED;
        $this->queuedAt = $queuedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markMessengerEnqueued(?\DateTimeImmutable $messengerEnqueuedAt = null): self
    {
        $this->messengerEnqueuedAt = $messengerEnqueuedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markRunning(?\DateTimeImmutable $startedAt = null): self
    {
        $this->status = AnalysisRequestStatusEnum::RUNNING;
        $this->startedAt = $startedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markCompleted(array $result, ?\DateTimeImmutable $completedAt = null): self
    {
        $this->status = AnalysisRequestStatusEnum::COMPLETED;
        $this->result = $result;
        $this->errorMessage = null;
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markFailed(string $errorMessage, ?\DateTimeImmutable $completedAt = null): self
    {
        $this->status = AnalysisRequestStatusEnum::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getQueuedAt(): ?\DateTimeImmutable
    {
        return $this->queuedAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getMessengerEnqueuedAt(): ?\DateTimeImmutable
    {
        return $this->messengerEnqueuedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
