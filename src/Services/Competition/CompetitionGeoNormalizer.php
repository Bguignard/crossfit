<?php

declare(strict_types=1);

namespace App\Services\Competition;

final class CompetitionGeoNormalizer
{
    /**
     * @var array<string, array{name: string, code: string}>
     */
    private const COUNTRIES = [
        'australia' => ['name' => 'Australia', 'code' => 'AU'],
        'au' => ['name' => 'Australia', 'code' => 'AU'],
        'belgium' => ['name' => 'Belgium', 'code' => 'BE'],
        'belgique' => ['name' => 'Belgium', 'code' => 'BE'],
        'be' => ['name' => 'Belgium', 'code' => 'BE'],
        'brazil' => ['name' => 'Brazil', 'code' => 'BR'],
        'brésil' => ['name' => 'Brazil', 'code' => 'BR'],
        'br' => ['name' => 'Brazil', 'code' => 'BR'],
        'canada' => ['name' => 'Canada', 'code' => 'CA'],
        'ca' => ['name' => 'Canada', 'code' => 'CA'],
        'france' => ['name' => 'France', 'code' => 'FR'],
        'fr' => ['name' => 'France', 'code' => 'FR'],
        'germany' => ['name' => 'Germany', 'code' => 'DE'],
        'allemagne' => ['name' => 'Germany', 'code' => 'DE'],
        'de' => ['name' => 'Germany', 'code' => 'DE'],
        'ireland' => ['name' => 'Ireland', 'code' => 'IE'],
        'irlande' => ['name' => 'Ireland', 'code' => 'IE'],
        'ie' => ['name' => 'Ireland', 'code' => 'IE'],
        'italy' => ['name' => 'Italy', 'code' => 'IT'],
        'italie' => ['name' => 'Italy', 'code' => 'IT'],
        'it' => ['name' => 'Italy', 'code' => 'IT'],
        'mexico' => ['name' => 'Mexico', 'code' => 'MX'],
        'mexique' => ['name' => 'Mexico', 'code' => 'MX'],
        'mx' => ['name' => 'Mexico', 'code' => 'MX'],
        'netherlands' => ['name' => 'Netherlands', 'code' => 'NL'],
        'pays-bas' => ['name' => 'Netherlands', 'code' => 'NL'],
        'nl' => ['name' => 'Netherlands', 'code' => 'NL'],
        'portugal' => ['name' => 'Portugal', 'code' => 'PT'],
        'pt' => ['name' => 'Portugal', 'code' => 'PT'],
        'spain' => ['name' => 'Spain', 'code' => 'ES'],
        'espagne' => ['name' => 'Spain', 'code' => 'ES'],
        'es' => ['name' => 'Spain', 'code' => 'ES'],
        'switzerland' => ['name' => 'Switzerland', 'code' => 'CH'],
        'suisse' => ['name' => 'Switzerland', 'code' => 'CH'],
        'ch' => ['name' => 'Switzerland', 'code' => 'CH'],
        'united kingdom' => ['name' => 'United Kingdom', 'code' => 'GB'],
        'uk' => ['name' => 'United Kingdom', 'code' => 'GB'],
        'gb' => ['name' => 'United Kingdom', 'code' => 'GB'],
        'united states' => ['name' => 'United States', 'code' => 'US'],
        'usa' => ['name' => 'United States', 'code' => 'US'],
        'us' => ['name' => 'United States', 'code' => 'US'],
        'états-unis' => ['name' => 'United States', 'code' => 'US'],
    ];

    /**
     * @param array<string, mixed> $row
     * @return array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float}
     */
    public function fromImportRow(array $row): array
    {
        $geo = [
            'countryName' => $this->stringOrNull($row['countryName'] ?? null),
            'countryCode' => $this->countryCodeOrNull($row['countryCode'] ?? null),
            'regionName' => $this->stringOrNull($row['regionName'] ?? null),
            'departmentName' => $this->stringOrNull($row['departmentName'] ?? null),
            'cityName' => $this->stringOrNull($row['cityName'] ?? null),
            'latitude' => $this->latitudeOrNull($row['latitude'] ?? null),
            'longitude' => $this->longitudeOrNull($row['longitude'] ?? null),
        ];

        if ($this->boolOrNull($row['isOnline'] ?? null) === true) {
            return array_merge($geo, [
                'countryName' => null,
                'countryCode' => null,
                'regionName' => null,
                'departmentName' => null,
                'cityName' => null,
                'latitude' => null,
                'longitude' => null,
            ]);
        }

        $country = $geo['countryName'] === null ? null : $this->country($geo['countryName']);
        if ($country !== null) {
            $geo['countryName'] = $country['name'];
            $geo['countryCode'] ??= $country['code'];
        } elseif ($geo['countryCode'] !== null) {
            $country = $this->country($geo['countryCode']);
            if ($country !== null) {
                $geo['countryName'] ??= $country['name'];
                $geo['countryCode'] = $country['code'];
            }
        }

        if ($geo['countryName'] !== null || $geo['cityName'] !== null) {
            return $geo;
        }

        return array_merge($geo, $this->fromLocationLabel($this->stringOrNull($row['locationLabel'] ?? null)));
    }

    /**
     * @return array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string}
     */
    private function fromLocationLabel(?string $locationLabel): array
    {
        $empty = [
            'countryName' => null,
            'countryCode' => null,
            'regionName' => null,
            'departmentName' => null,
            'cityName' => null,
        ];

        if ($locationLabel === null || mb_strtolower($locationLabel) === 'en ligne') {
            return $empty;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $locationLabel)), static fn (string $part): bool => $part !== ''));
        if (count($parts) < 2) {
            return $empty;
        }

        $country = $this->country($parts[count($parts) - 1]);
        if ($country === null) {
            return [
                ...$empty,
                'regionName' => $parts[1] ?? null,
                'cityName' => $parts[0] ?? null,
            ];
        }

        $middle = array_slice($parts, 1, -1);

        return [
            'countryName' => $country['name'],
            'countryCode' => $country['code'],
            'regionName' => count($middle) > 0 ? implode(', ', $middle) : null,
            'departmentName' => null,
            'cityName' => $parts[0] ?? null,
        ];
    }

    /**
     * @return array{name: string, code: string}|null
     */
    private function country(string $value): ?array
    {
        $key = mb_strtolower(trim($value));

        return self::COUNTRIES[$key] ?? null;
    }

    private function countryCodeOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null || !preg_match('/^[A-Z]{2}$/', mb_strtoupper($value))) {
            return null;
        }

        return mb_strtoupper($value);
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

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function boolOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
