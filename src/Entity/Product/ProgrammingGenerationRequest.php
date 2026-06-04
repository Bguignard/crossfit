<?php

namespace App\Entity\Product;

use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'programming_generation_request')]
class ProgrammingGenerationRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'programmingGenerationRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 32, enumType: ProgrammingGenerationTypeEnum::class)]
    private ProgrammingGenerationTypeEnum $type;

    #[ORM\ManyToOne(targetEntity: UserPerformanceProfile::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UserPerformanceProfile $performanceProfile = null;

    #[ORM\ManyToOne(targetEntity: Box::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Box $box = null;

    #[ORM\Column(type: 'string', length: 32, enumType: ProgrammingGenerationRequestStatusEnum::class)]
    private ProgrammingGenerationRequestStatusEnum $status = ProgrammingGenerationRequestStatusEnum::DRAFT;

    #[ORM\Column(type: 'json')]
    private array $constraints = [];

    #[ORM\Column(type: 'json')]
    private array $inputSnapshot = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $generatedProgramming = null;

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
        ProgrammingGenerationTypeEnum $type,
        array $constraints = [],
        array $inputSnapshot = [],
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->constraints = $constraints;
        $this->inputSnapshot = $inputSnapshot;
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

    public function getType(): ProgrammingGenerationTypeEnum
    {
        return $this->type;
    }

    public function getPerformanceProfile(): ?UserPerformanceProfile
    {
        return $this->performanceProfile;
    }

    public function setPerformanceProfile(?UserPerformanceProfile $performanceProfile): self
    {
        $this->performanceProfile = $performanceProfile;
        $this->touch();

        return $this;
    }

    public function getBox(): ?Box
    {
        return $this->box;
    }

    public function setBox(?Box $box): self
    {
        $this->box = $box;
        $this->touch();

        return $this;
    }

    public function getStatus(): ProgrammingGenerationRequestStatusEnum
    {
        return $this->status;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function setConstraints(array $constraints): self
    {
        $this->constraints = $constraints;
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

    public function getGeneratedProgramming(): ?array
    {
        return $this->generatedProgramming;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function markQueued(?\DateTimeImmutable $queuedAt = null): self
    {
        $this->status = ProgrammingGenerationRequestStatusEnum::QUEUED;
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
        $this->status = ProgrammingGenerationRequestStatusEnum::RUNNING;
        $this->startedAt = $startedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markCompleted(array $generatedProgramming, ?\DateTimeImmutable $completedAt = null): self
    {
        $this->status = ProgrammingGenerationRequestStatusEnum::COMPLETED;
        $this->generatedProgramming = $generatedProgramming;
        $this->errorMessage = null;
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markFailed(string $errorMessage, ?\DateTimeImmutable $completedAt = null): self
    {
        $this->status = ProgrammingGenerationRequestStatusEnum::FAILED;
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
