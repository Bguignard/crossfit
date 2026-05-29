<?php

namespace App\Entity\Competition;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'competition_participation')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMPETITION_PARTICIPATION_SOURCE_EXTERNAL', columns: ['source_name', 'external_id'])]
#[ApiResource(operations: [new Get(), new GetCollection()])]
#[ApiFilter(SearchFilter::class, properties: [
    'athlete' => 'exact',
    'competition' => 'exact',
    'sourceName' => 'exact',
    'externalId' => 'exact',
])]
class CompetitionParticipation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Athlete::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Athlete $athlete;

    #[ORM\ManyToOne(targetEntity: Competition::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Competition $competition;

    #[ORM\Column(length: 64)]
    private string $sourceName;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $rank = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $division = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $divisionSourceId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $formatSlug = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Athlete $athlete, Competition $competition, string $sourceName, string $externalId)
    {
        $this->athlete = $athlete;
        $this->competition = $competition;
        $this->sourceName = $sourceName;
        $this->externalId = $externalId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getAthlete(): Athlete
    {
        return $this->athlete;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
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

    public function getRank(): ?string
    {
        return $this->rank;
    }

    public function setRank(?string $rank): self
    {
        $this->rank = $rank;
        $this->touch();

        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(?string $division): self
    {
        $this->division = $division;
        $this->touch();

        return $this;
    }

    public function getDivisionSourceId(): ?string
    {
        return $this->divisionSourceId;
    }

    public function setDivisionSourceId(?string $divisionSourceId): self
    {
        $this->divisionSourceId = $divisionSourceId;
        $this->touch();

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;
        $this->touch();

        return $this;
    }

    public function getFormatSlug(): ?string
    {
        return $this->formatSlug;
    }

    public function setFormatSlug(?string $formatSlug): self
    {
        $this->formatSlug = $formatSlug;
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
