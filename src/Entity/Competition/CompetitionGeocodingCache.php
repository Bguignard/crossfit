<?php

declare(strict_types=1);

namespace App\Entity\Competition;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'competition_geocoding_cache')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMPETITION_GEOCODING_CACHE_HASH', columns: ['raw_location_hash'])]
class CompetitionGeocodingCache
{
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_UNRESOLVED = 'unresolved';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    private string $rawLocationHash;

    #[ORM\Column(length: 2048)]
    private string $rawLocation;

    #[ORM\Column(length: 64)]
    private string $provider;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $countryName = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $regionName = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $departmentName = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $cityName = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $confidence = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(string $rawLocationHash, string $rawLocation, string $provider)
    {
        $this->rawLocationHash = $rawLocationHash;
        $this->rawLocation = $rawLocation;
        $this->provider = $provider;
        $this->status = self::STATUS_UNRESOLVED;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getRawLocationHash(): string
    {
        return $this->rawLocationHash;
    }

    public function getRawLocation(): string
    {
        return $this->rawLocation;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * @param array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float} $geo
     */
    public function markResolved(array $geo, float $confidence, ?string $provider = null): self
    {
        if ($provider !== null) {
            $this->provider = $provider;
        }
        $this->status = self::STATUS_RESOLVED;
        $this->countryName = $geo['countryName'];
        $this->countryCode = $geo['countryCode'];
        $this->regionName = $geo['regionName'];
        $this->departmentName = $geo['departmentName'];
        $this->cityName = $geo['cityName'];
        $this->latitude = $geo['latitude'];
        $this->longitude = $geo['longitude'];
        $this->confidence = $confidence;
        $this->errorMessage = null;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markUnresolved(string $errorMessage, ?string $provider = null): self
    {
        if ($provider !== null) {
            $this->provider = $provider;
        }
        $this->status = self::STATUS_UNRESOLVED;
        $this->confidence = null;
        $this->errorMessage = $errorMessage;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function markUsed(): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float}
     */
    public function geo(): array
    {
        return [
            'countryName' => $this->countryName,
            'countryCode' => $this->countryCode,
            'regionName' => $this->regionName,
            'departmentName' => $this->departmentName,
            'cityName' => $this->cityName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
