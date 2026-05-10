<?php

namespace App\Entity\Product;

use App\Entity\Competition\Athlete;
use App\Entity\Security\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_athlete_profile')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_ATHLETE_PROFILE_USER_ATHLETE', columns: ['user_id', 'athlete_id'])]
class UserAthleteProfile
{
    public const LINK_SELF = 'self';
    public const LINK_COACHED = 'coached';
    public const LINK_FOLLOWED = 'followed';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'athleteProfiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Athlete::class, inversedBy: 'linkedUserProfiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Athlete $athlete;

    #[ORM\Column(length: 32)]
    private string $linkType;

    #[ORM\Column]
    private bool $primaryProfile = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Athlete $athlete, string $linkType = self::LINK_SELF)
    {
        $this->user = $user;
        $this->athlete = $athlete;
        $this->linkType = $linkType;
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

    public function getAthlete(): Athlete
    {
        return $this->athlete;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function setLinkType(string $linkType): self
    {
        $this->linkType = $linkType;
        $this->touch();

        return $this;
    }

    public function isPrimaryProfile(): bool
    {
        return $this->primaryProfile;
    }

    public function setPrimaryProfile(bool $primaryProfile): self
    {
        $this->primaryProfile = $primaryProfile;
        $this->touch();

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function markVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->verifiedAt = $verifiedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markUnverified(): self
    {
        $this->verifiedAt = null;
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
