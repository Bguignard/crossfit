<?php

namespace App\Entity\Competition;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'competition')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMPETITION_SOURCE_EXTERNAL', columns: ['source_name', 'external_id'])]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class Competition
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?int $season = null;

    #[ORM\Column(length: 64)]
    private string $sourceName;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $registrationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationLabel = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isOnline = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $competitionType = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $participationType = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $priceLabel = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastDiscoveredAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'competition', targetEntity: CompetitionEvent::class, orphanRemoval: true)]
    private Collection $events;

    public function __construct(string $name, string $sourceName, string $externalId)
    {
        $this->name = $name;
        $this->sourceName = $sourceName;
        $this->externalId = $externalId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();

        return $this;
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

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;
        $this->touch();

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;
        $this->touch();

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;
        $this->touch();

        return $this;
    }

    public function getRegistrationUrl(): ?string
    {
        return $this->registrationUrl;
    }

    public function setRegistrationUrl(?string $registrationUrl): self
    {
        $this->registrationUrl = $registrationUrl;
        $this->touch();

        return $this;
    }

    public function getLocationLabel(): ?string
    {
        return $this->locationLabel;
    }

    public function setLocationLabel(?string $locationLabel): self
    {
        $this->locationLabel = $locationLabel;
        $this->touch();

        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(?bool $isOnline): self
    {
        $this->isOnline = $isOnline;
        $this->touch();

        return $this;
    }

    public function getCompetitionType(): ?string
    {
        return $this->competitionType;
    }

    public function setCompetitionType(?string $competitionType): self
    {
        $this->competitionType = $competitionType;
        $this->touch();

        return $this;
    }

    public function getParticipationType(): ?string
    {
        return $this->participationType;
    }

    public function setParticipationType(?string $participationType): self
    {
        $this->participationType = $participationType;
        $this->touch();

        return $this;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): self
    {
        $this->coverImageUrl = $coverImageUrl;
        $this->touch();

        return $this;
    }

    public function getPriceLabel(): ?string
    {
        return $this->priceLabel;
    }

    public function setPriceLabel(?string $priceLabel): self
    {
        $this->priceLabel = $priceLabel;
        $this->touch();

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        $this->touch();

        return $this;
    }

    public function getLastDiscoveredAt(): ?\DateTimeImmutable
    {
        return $this->lastDiscoveredAt;
    }

    public function setLastDiscoveredAt(?\DateTimeImmutable $lastDiscoveredAt): self
    {
        $this->lastDiscoveredAt = $lastDiscoveredAt;
        $this->touch();

        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
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
