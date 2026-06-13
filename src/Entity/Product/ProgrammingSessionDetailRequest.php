<?php

namespace App\Entity\Product;

use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'programming_session_detail_request')]
class ProgrammingSessionDetailRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: ProgrammingGenerationRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProgrammingGenerationRequest $programmingRequest;

    #[ORM\Column(type: 'string', length: 32, enumType: ProgrammingGenerationRequestStatusEnum::class)]
    private ProgrammingGenerationRequestStatusEnum $status = ProgrammingGenerationRequestStatusEnum::DRAFT;

    #[ORM\Column(type: 'json')]
    private array $inputSnapshot = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $detailedProgramming = null;

    #[ORM\Column(type: 'integer')]
    private int $currentSessionIndex = 0;

    #[ORM\Column(type: 'json')]
    private array $completedSessionKeys = [];

    #[ORM\Column(type: 'json')]
    private array $sessionEmailSentAtByKey = [];

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
        ProgrammingGenerationRequest $programmingRequest,
        array $inputSnapshot = [],
    ) {
        $this->user = $user;
        $this->programmingRequest = $programmingRequest;
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

    public function getProgrammingRequest(): ProgrammingGenerationRequest
    {
        return $this->programmingRequest;
    }

    public function getStatus(): ProgrammingGenerationRequestStatusEnum
    {
        return $this->status;
    }

    public function getInputSnapshot(): array
    {
        return $this->inputSnapshot;
    }

    public function getDetailedProgramming(): ?array
    {
        return $this->detailedProgramming;
    }

    public function getCurrentSessionIndex(): int
    {
        return $this->currentSessionIndex;
    }

    public function setCurrentSessionIndex(int $currentSessionIndex): self
    {
        $this->currentSessionIndex = max(0, $currentSessionIndex);
        $this->touch();

        return $this;
    }

    public function getCompletedSessionKeys(): array
    {
        return $this->completedSessionKeys;
    }

    public function setCompletedSessionKeys(array $completedSessionKeys): self
    {
        $this->completedSessionKeys = array_values(array_filter($completedSessionKeys, is_string(...)));
        $this->touch();

        return $this;
    }

    public function getCurrentSessionEmailSentAt(string $sessionKey): ?\DateTimeImmutable
    {
        $sentAt = $this->sessionEmailSentAtByKey[$sessionKey] ?? null;
        if (!is_string($sentAt) || trim($sentAt) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($sentAt);
        } catch (\Exception) {
            return null;
        }
    }

    public function markCurrentSessionEmailSent(string $sessionKey, ?\DateTimeImmutable $sentAt = null): self
    {
        $this->sessionEmailSentAtByKey[$sessionKey] = ($sentAt ?? new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $this->touch();

        return $this;
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

    public function markCompleted(array $detailedProgramming, ?\DateTimeImmutable $completedAt = null): self
    {
        $this->status = ProgrammingGenerationRequestStatusEnum::COMPLETED;
        $this->detailedProgramming = $detailedProgramming;
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
