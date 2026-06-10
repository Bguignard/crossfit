<?php

declare(strict_types=1);

namespace App\Services\Competition;

interface CompetitionExternalGeocoderInterface
{
    /**
     * @return array{
     *     provider: string,
     *     confidence: float,
     *     geo: array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float}
     * }|null
     */
    public function resolve(string $rawLocation): ?array;
}
