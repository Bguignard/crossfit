<?php

namespace App\Entity\Security;

use App\Entity\Product\BoxMembership;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceProfile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'UNIQ_APP_USER_EMAIL', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column]
    private string $password;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BoxMembership::class, orphanRemoval: true)]
    private Collection $boxMemberships;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserAthleteProfile::class, orphanRemoval: true)]
    private Collection $athleteProfiles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPerformanceProfile::class, orphanRemoval: true)]
    private Collection $performanceProfiles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PerformanceAnalysisRequest::class, orphanRemoval: true)]
    private Collection $performanceAnalysisRequests;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ProgrammingGenerationRequest::class, orphanRemoval: true)]
    private Collection $programmingGenerationRequests;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->boxMemberships = new ArrayCollection();
        $this->athleteProfiles = new ArrayCollection();
        $this->performanceProfiles = new ArrayCollection();
        $this->performanceAnalysisRequests = new ArrayCollection();
        $this->programmingGenerationRequests = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        $this->touch();

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        $this->touch();

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        $this->touch();

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        $this->touch();

        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function markEmailVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->emailVerifiedAt = $verifiedAt ?? new \DateTimeImmutable();
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

    public function getBoxMemberships(): Collection
    {
        return $this->boxMemberships;
    }

    public function getAthleteProfiles(): Collection
    {
        return $this->athleteProfiles;
    }

    public function getPerformanceProfiles(): Collection
    {
        return $this->performanceProfiles;
    }

    public function getPerformanceAnalysisRequests(): Collection
    {
        return $this->performanceAnalysisRequests;
    }

    public function getProgrammingGenerationRequests(): Collection
    {
        return $this->programmingGenerationRequests;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
