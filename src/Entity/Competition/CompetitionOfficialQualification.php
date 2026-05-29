<?php

namespace App\Entity\Competition;

use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'competition_official_qualification')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMPETITION_OFFICIAL_QUALIFICATION_SCOPE', columns: ['competition_id', 'circuit', 'stage', 'division_pattern'])]
class CompetitionOfficialQualification
{
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DISMISSED = 'dismissed';

    public const SOURCE_AUTO = 'auto';
    public const SOURCE_ADMIN = 'admin';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Competition::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Competition $competition;

    #[ORM\Column(nullable: true)]
    private ?int $season = null;

    #[ORM\Column(length: 64)]
    private string $circuit;

    #[ORM\Column(length: 64)]
    private string $stage;

    #[ORM\Column(length: 255)]
    private string $divisionPattern;

    #[ORM\Column(length: 32)]
    private string $status = self::STATUS_SUGGESTED;

    #[ORM\Column(length: 32)]
    private string $source = self::SOURCE_AUTO;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $confirmedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dismissedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Competition $competition, string $circuit, string $stage, string $divisionPattern)
    {
        $this->competition = $competition;
        $this->circuit = $circuit;
        $this->stage = $stage;
        $this->divisionPattern = $divisionPattern;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(?int $season): self
    {
        $this->season = $season;
        $this->touch();

        return $this;
    }

    public function getCircuit(): string
    {
        return $this->circuit;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getDivisionPattern(): string
    {
        return $this->divisionPattern;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function suggest(string $source = self::SOURCE_AUTO): self
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            $this->status = self::STATUS_SUGGESTED;
            $this->source = $source;
            $this->dismissedAt = null;
        }

        $this->touch();

        return $this;
    }

    public function confirm(?User $user = null): self
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->source = self::SOURCE_ADMIN;
        $this->confirmedBy = $user;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->dismissedAt = null;
        $this->touch();

        return $this;
    }

    public function dismiss(): self
    {
        $this->status = self::STATUS_DISMISSED;
        $this->source = self::SOURCE_ADMIN;
        $this->dismissedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
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

    public function getConfirmedBy(): ?User
    {
        return $this->confirmedBy;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getDismissedAt(): ?\DateTimeImmutable
    {
        return $this->dismissedAt;
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
