<?php

declare(strict_types=1);

namespace App\Services\Competition;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CompetitionNominatimGeocoder implements CompetitionExternalGeocoderInterface
{
    public const PROVIDER = 'nominatim';

    private ?float $lastRequestAt = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $userAgent,
        private readonly int $timeoutSeconds,
        private readonly int $delayMilliseconds,
    ) {
    }

    public function resolve(string $rawLocation): ?array
    {
        $query = $this->queryFromRawLocation($rawLocation);
        if ($query === null) {
            return null;
        }

        $this->throttle();

        try {
            $response = $this->httpClient->request('GET', rtrim($this->baseUrl, '/').'/search', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en',
                    'User-Agent' => $this->userAgent,
                ],
                'query' => [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'addressdetails' => '1',
                    'limit' => '1',
                ],
                'timeout' => max(1, $this->timeoutSeconds),
            ]);
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                return null;
            }
            $payload = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (TransportExceptionInterface|\JsonException) {
            return null;
        }

        if (!is_array($payload) || !isset($payload[0]) || !is_array($payload[0])) {
            return null;
        }

        return $this->resultFromNominatimPlace($payload[0]);
    }

    private function throttle(): void
    {
        if ($this->delayMilliseconds <= 0 || $this->lastRequestAt === null) {
            $this->lastRequestAt = microtime(true);

            return;
        }

        $elapsedMilliseconds = (microtime(true) - $this->lastRequestAt) * 1000;
        $remainingMilliseconds = $this->delayMilliseconds - $elapsedMilliseconds;
        if ($remainingMilliseconds > 0) {
            usleep((int) round($remainingMilliseconds * 1000));
        }

        $this->lastRequestAt = microtime(true);
    }

    private function queryFromRawLocation(string $rawLocation): ?string
    {
        $query = html_entity_decode($rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $query = preg_replace('/<br\s*\/?>/i', ', ', $query) ?? $query;
        $query = strip_tags($query);
        $query = preg_replace('/\s+/', ' ', $query) ?? $query;
        $query = preg_replace('/\s*,\s*/', ', ', $query) ?? $query;
        $query = trim($query, " \t\n\r\0\x0B,");

        return $query === '' ? null : $query;
    }

    /**
     * @param array<string, mixed> $place
     *
     * @return array{
     *     provider: string,
     *     confidence: float,
     *     geo: array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float}
     * }|null
     */
    private function resultFromNominatimPlace(array $place): ?array
    {
        $address = isset($place['address']) && is_array($place['address']) ? $place['address'] : [];
        $countryName = $this->stringOrNull($address['country'] ?? null);
        $countryCode = $this->countryCodeOrNull($address['country_code'] ?? null);
        $regionName = $this->firstString(
            $address['state'] ?? null,
            $address['region'] ?? null,
            $address['province'] ?? null,
            $address['county'] ?? null,
            $address['state_district'] ?? null,
        );
        $departmentName = $this->firstString($address['county'] ?? null, $address['state_district'] ?? null);
        $cityName = $this->firstString(
            $address['city'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            $address['municipality'] ?? null,
            $address['suburb'] ?? null,
        );
        $latitude = $this->latitudeOrNull($place['lat'] ?? null);
        $longitude = $this->longitudeOrNull($place['lon'] ?? null);

        if ($countryName === null || $countryCode === null || ($cityName === null && $regionName === null)) {
            return null;
        }

        return [
            'provider' => self::PROVIDER,
            'confidence' => $latitude !== null && $longitude !== null ? 0.9 : 0.75,
            'geo' => [
                'countryName' => $countryName,
                'countryCode' => $countryCode,
                'regionName' => $regionName,
                'departmentName' => $departmentName,
                'cityName' => $cityName,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];
    }

    private function firstString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $value = $this->stringOrNull($value);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function countryCodeOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        $value = mb_strtoupper($value);

        return preg_match('/^[A-Z]{2}$/', $value) === 1 ? $value : null;
    }

    private function latitudeOrNull(mixed $value): ?float
    {
        $value = $this->floatOrNull($value);

        return $value !== null && $value >= -90.0 && $value <= 90.0 ? $value : null;
    }

    private function longitudeOrNull(mixed $value): ?float
    {
        $value = $this->floatOrNull($value);

        return $value !== null && $value >= -180.0 && $value <= 180.0 ? $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if (!is_scalar($value) || trim((string) $value) === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
