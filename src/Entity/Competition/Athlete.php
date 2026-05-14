<?php

namespace App\Entity\Competition;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Product\UserAthleteProfile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'athlete')]
#[ORM\UniqueConstraint(name: 'UNIQ_ATHLETE_SOURCE_EXTERNAL', columns: ['source_name', 'external_id'])]
#[ApiResource(operations: [new Get(), new GetCollection()])]
#[ApiFilter(SearchFilter::class, properties: [
    'displayName' => 'ipartial',
    'firstName' => 'ipartial',
    'lastName' => 'ipartial',
    'sourceName' => 'exact',
    'externalId' => 'exact',
])]
class Athlete
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $displayName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 64)]
    private string $sourceName;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'athlete', targetEntity: UserAthleteProfile::class, orphanRemoval: true)]
    private Collection $linkedUserProfiles;

    #[ORM\OneToMany(mappedBy: 'athlete', targetEntity: AthletePublicAnalysis::class, orphanRemoval: true)]
    private Collection $publicAnalyses;

    public function __construct(string $displayName, string $sourceName, string $externalId)
    {
        $this->displayName = $displayName;
        $this->sourceName = $sourceName;
        $this->externalId = $externalId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->linkedUserProfiles = new ArrayCollection();
        $this->publicAnalyses = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        $this->touch();

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        $this->touch();

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        $this->touch();

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        $this->touch();

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        $this->touch();

        return $this;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;
        $this->touch();

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getPublicAnalysis(): ?array
    {
        $analysis = $this->publicAnalyses
            ->filter(static fn (AthletePublicAnalysis $analysis): bool => $analysis->getKind() === AthletePublicAnalysis::KIND_GAMES_PUBLIC)
            ->first();

        return $analysis instanceof AthletePublicAnalysis ? $analysis->toPublicPayload() : null;
    }

    #[Ignore]
    public function getLinkedUserProfiles(): Collection
    {
        return $this->linkedUserProfiles;
    }

    #[Ignore]
    public function getPublicAnalyses(): Collection
    {
        return $this->publicAnalyses;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
