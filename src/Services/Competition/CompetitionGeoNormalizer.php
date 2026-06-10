<?php

declare(strict_types=1);

namespace App\Services\Competition;

final class CompetitionGeoNormalizer
{
    /**
     * @var array<string, array{departmentName: string, regionName: string}>
     */
    private const FRENCH_POSTAL_AREAS = [
        '01' => ['departmentName' => 'Ain', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '02' => ['departmentName' => 'Aisne', 'regionName' => 'Hauts-de-France'],
        '03' => ['departmentName' => 'Allier', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '04' => ['departmentName' => 'Alpes-de-Haute-Provence', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '05' => ['departmentName' => 'Hautes-Alpes', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '06' => ['departmentName' => 'Alpes-Maritimes', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '07' => ['departmentName' => 'Ardèche', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '08' => ['departmentName' => 'Ardennes', 'regionName' => 'Grand Est'],
        '09' => ['departmentName' => 'Ariège', 'regionName' => 'Occitanie'],
        '10' => ['departmentName' => 'Aube', 'regionName' => 'Grand Est'],
        '11' => ['departmentName' => 'Aude', 'regionName' => 'Occitanie'],
        '12' => ['departmentName' => 'Aveyron', 'regionName' => 'Occitanie'],
        '13' => ['departmentName' => 'Bouches-du-Rhône', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '14' => ['departmentName' => 'Calvados', 'regionName' => 'Normandie'],
        '15' => ['departmentName' => 'Cantal', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '16' => ['departmentName' => 'Charente', 'regionName' => 'Nouvelle-Aquitaine'],
        '17' => ['departmentName' => 'Charente-Maritime', 'regionName' => 'Nouvelle-Aquitaine'],
        '18' => ['departmentName' => 'Cher', 'regionName' => 'Centre-Val de Loire'],
        '19' => ['departmentName' => 'Corrèze', 'regionName' => 'Nouvelle-Aquitaine'],
        '20' => ['departmentName' => 'Corse', 'regionName' => 'Corse'],
        '21' => ['departmentName' => 'Côte-d’Or', 'regionName' => 'Bourgogne-Franche-Comté'],
        '22' => ['departmentName' => 'Côtes-d’Armor', 'regionName' => 'Bretagne'],
        '23' => ['departmentName' => 'Creuse', 'regionName' => 'Nouvelle-Aquitaine'],
        '24' => ['departmentName' => 'Dordogne', 'regionName' => 'Nouvelle-Aquitaine'],
        '25' => ['departmentName' => 'Doubs', 'regionName' => 'Bourgogne-Franche-Comté'],
        '26' => ['departmentName' => 'Drôme', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '27' => ['departmentName' => 'Eure', 'regionName' => 'Normandie'],
        '28' => ['departmentName' => 'Eure-et-Loir', 'regionName' => 'Centre-Val de Loire'],
        '29' => ['departmentName' => 'Finistère', 'regionName' => 'Bretagne'],
        '30' => ['departmentName' => 'Gard', 'regionName' => 'Occitanie'],
        '31' => ['departmentName' => 'Haute-Garonne', 'regionName' => 'Occitanie'],
        '32' => ['departmentName' => 'Gers', 'regionName' => 'Occitanie'],
        '33' => ['departmentName' => 'Gironde', 'regionName' => 'Nouvelle-Aquitaine'],
        '34' => ['departmentName' => 'Hérault', 'regionName' => 'Occitanie'],
        '35' => ['departmentName' => 'Ille-et-Vilaine', 'regionName' => 'Bretagne'],
        '36' => ['departmentName' => 'Indre', 'regionName' => 'Centre-Val de Loire'],
        '37' => ['departmentName' => 'Indre-et-Loire', 'regionName' => 'Centre-Val de Loire'],
        '38' => ['departmentName' => 'Isère', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '39' => ['departmentName' => 'Jura', 'regionName' => 'Bourgogne-Franche-Comté'],
        '40' => ['departmentName' => 'Landes', 'regionName' => 'Nouvelle-Aquitaine'],
        '41' => ['departmentName' => 'Loir-et-Cher', 'regionName' => 'Centre-Val de Loire'],
        '42' => ['departmentName' => 'Loire', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '43' => ['departmentName' => 'Haute-Loire', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '44' => ['departmentName' => 'Loire-Atlantique', 'regionName' => 'Pays de la Loire'],
        '45' => ['departmentName' => 'Loiret', 'regionName' => 'Centre-Val de Loire'],
        '46' => ['departmentName' => 'Lot', 'regionName' => 'Occitanie'],
        '47' => ['departmentName' => 'Lot-et-Garonne', 'regionName' => 'Nouvelle-Aquitaine'],
        '48' => ['departmentName' => 'Lozère', 'regionName' => 'Occitanie'],
        '49' => ['departmentName' => 'Maine-et-Loire', 'regionName' => 'Pays de la Loire'],
        '50' => ['departmentName' => 'Manche', 'regionName' => 'Normandie'],
        '51' => ['departmentName' => 'Marne', 'regionName' => 'Grand Est'],
        '52' => ['departmentName' => 'Haute-Marne', 'regionName' => 'Grand Est'],
        '53' => ['departmentName' => 'Mayenne', 'regionName' => 'Pays de la Loire'],
        '54' => ['departmentName' => 'Meurthe-et-Moselle', 'regionName' => 'Grand Est'],
        '55' => ['departmentName' => 'Meuse', 'regionName' => 'Grand Est'],
        '56' => ['departmentName' => 'Morbihan', 'regionName' => 'Bretagne'],
        '57' => ['departmentName' => 'Moselle', 'regionName' => 'Grand Est'],
        '58' => ['departmentName' => 'Nièvre', 'regionName' => 'Bourgogne-Franche-Comté'],
        '59' => ['departmentName' => 'Nord', 'regionName' => 'Hauts-de-France'],
        '60' => ['departmentName' => 'Oise', 'regionName' => 'Hauts-de-France'],
        '61' => ['departmentName' => 'Orne', 'regionName' => 'Normandie'],
        '62' => ['departmentName' => 'Pas-de-Calais', 'regionName' => 'Hauts-de-France'],
        '63' => ['departmentName' => 'Puy-de-Dôme', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '64' => ['departmentName' => 'Pyrénées-Atlantiques', 'regionName' => 'Nouvelle-Aquitaine'],
        '65' => ['departmentName' => 'Hautes-Pyrénées', 'regionName' => 'Occitanie'],
        '66' => ['departmentName' => 'Pyrénées-Orientales', 'regionName' => 'Occitanie'],
        '67' => ['departmentName' => 'Bas-Rhin', 'regionName' => 'Grand Est'],
        '68' => ['departmentName' => 'Haut-Rhin', 'regionName' => 'Grand Est'],
        '69' => ['departmentName' => 'Rhône', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '70' => ['departmentName' => 'Haute-Saône', 'regionName' => 'Bourgogne-Franche-Comté'],
        '71' => ['departmentName' => 'Saône-et-Loire', 'regionName' => 'Bourgogne-Franche-Comté'],
        '72' => ['departmentName' => 'Sarthe', 'regionName' => 'Pays de la Loire'],
        '73' => ['departmentName' => 'Savoie', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '74' => ['departmentName' => 'Haute-Savoie', 'regionName' => 'Auvergne-Rhône-Alpes'],
        '75' => ['departmentName' => 'Paris', 'regionName' => 'Île-de-France'],
        '76' => ['departmentName' => 'Seine-Maritime', 'regionName' => 'Normandie'],
        '77' => ['departmentName' => 'Seine-et-Marne', 'regionName' => 'Île-de-France'],
        '78' => ['departmentName' => 'Yvelines', 'regionName' => 'Île-de-France'],
        '79' => ['departmentName' => 'Deux-Sèvres', 'regionName' => 'Nouvelle-Aquitaine'],
        '80' => ['departmentName' => 'Somme', 'regionName' => 'Hauts-de-France'],
        '81' => ['departmentName' => 'Tarn', 'regionName' => 'Occitanie'],
        '82' => ['departmentName' => 'Tarn-et-Garonne', 'regionName' => 'Occitanie'],
        '83' => ['departmentName' => 'Var', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '84' => ['departmentName' => 'Vaucluse', 'regionName' => 'Provence-Alpes-Côte d’Azur'],
        '85' => ['departmentName' => 'Vendée', 'regionName' => 'Pays de la Loire'],
        '86' => ['departmentName' => 'Vienne', 'regionName' => 'Nouvelle-Aquitaine'],
        '87' => ['departmentName' => 'Haute-Vienne', 'regionName' => 'Nouvelle-Aquitaine'],
        '88' => ['departmentName' => 'Vosges', 'regionName' => 'Grand Est'],
        '89' => ['departmentName' => 'Yonne', 'regionName' => 'Bourgogne-Franche-Comté'],
        '90' => ['departmentName' => 'Territoire de Belfort', 'regionName' => 'Bourgogne-Franche-Comté'],
        '91' => ['departmentName' => 'Essonne', 'regionName' => 'Île-de-France'],
        '92' => ['departmentName' => 'Hauts-de-Seine', 'regionName' => 'Île-de-France'],
        '93' => ['departmentName' => 'Seine-Saint-Denis', 'regionName' => 'Île-de-France'],
        '94' => ['departmentName' => 'Val-de-Marne', 'regionName' => 'Île-de-France'],
        '95' => ['departmentName' => 'Val-d’Oise', 'regionName' => 'Île-de-France'],
        '971' => ['departmentName' => 'Guadeloupe', 'regionName' => 'Guadeloupe'],
        '972' => ['departmentName' => 'Martinique', 'regionName' => 'Martinique'],
        '973' => ['departmentName' => 'Guyane', 'regionName' => 'Guyane'],
        '974' => ['departmentName' => 'La Réunion', 'regionName' => 'La Réunion'],
        '976' => ['departmentName' => 'Mayotte', 'regionName' => 'Mayotte'],
    ];

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
        'denmark' => ['name' => 'Denmark', 'code' => 'DK'],
        'danemark' => ['name' => 'Denmark', 'code' => 'DK'],
        'dk' => ['name' => 'Denmark', 'code' => 'DK'],
        'finland' => ['name' => 'Finland', 'code' => 'FI'],
        'finlande' => ['name' => 'Finland', 'code' => 'FI'],
        'fi' => ['name' => 'Finland', 'code' => 'FI'],
        'france' => ['name' => 'France', 'code' => 'FR'],
        'fr' => ['name' => 'France', 'code' => 'FR'],
        'germany' => ['name' => 'Germany', 'code' => 'DE'],
        'allemagne' => ['name' => 'Germany', 'code' => 'DE'],
        'de' => ['name' => 'Germany', 'code' => 'DE'],
        'greece' => ['name' => 'Greece', 'code' => 'GR'],
        'grèce' => ['name' => 'Greece', 'code' => 'GR'],
        'gr' => ['name' => 'Greece', 'code' => 'GR'],
        'hungary' => ['name' => 'Hungary', 'code' => 'HU'],
        'hongrie' => ['name' => 'Hungary', 'code' => 'HU'],
        'hu' => ['name' => 'Hungary', 'code' => 'HU'],
        'iceland' => ['name' => 'Iceland', 'code' => 'IS'],
        'islande' => ['name' => 'Iceland', 'code' => 'IS'],
        'is' => ['name' => 'Iceland', 'code' => 'IS'],
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
        'norway' => ['name' => 'Norway', 'code' => 'NO'],
        'norvège' => ['name' => 'Norway', 'code' => 'NO'],
        'no' => ['name' => 'Norway', 'code' => 'NO'],
        'poland' => ['name' => 'Poland', 'code' => 'PL'],
        'pologne' => ['name' => 'Poland', 'code' => 'PL'],
        'pl' => ['name' => 'Poland', 'code' => 'PL'],
        'portugal' => ['name' => 'Portugal', 'code' => 'PT'],
        'pt' => ['name' => 'Portugal', 'code' => 'PT'],
        'sweden' => ['name' => 'Sweden', 'code' => 'SE'],
        'suède' => ['name' => 'Sweden', 'code' => 'SE'],
        'se' => ['name' => 'Sweden', 'code' => 'SE'],
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
     *
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

        $locationGeo = $this->fromLocationLabel($this->stringOrNull($row['locationLabel'] ?? null));
        if (($geo['countryCode'] === 'FR' || $locationGeo['countryCode'] === 'FR') && $locationGeo['countryName'] !== null) {
            $geo['countryName'] = $locationGeo['countryName'];
            $geo['countryCode'] = $locationGeo['countryCode'];
            $geo['regionName'] = $locationGeo['regionName'] ?? $this->trustedFrenchRegionOrNull($geo['regionName']);
            $geo['departmentName'] = $locationGeo['departmentName'] ?? $geo['departmentName'];
            $geo['cityName'] = $locationGeo['cityName'] ?? $geo['cityName'];

            return $geo;
        }

        if ($geo['countryName'] !== null || $geo['cityName'] !== null) {
            return $geo;
        }

        return array_merge($geo, $locationGeo);
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

        $parts = $this->locationParts($locationLabel);
        if (count($parts) < 2) {
            return $empty;
        }

        $country = $this->country($parts[count($parts) - 1]);
        if ($country === null) {
            return [
                'countryName' => null,
                'countryCode' => null,
                'regionName' => $parts[1] ?? null,
                'departmentName' => null,
                'cityName' => $parts[0] ?? null,
            ];
        }

        if ($country['code'] === 'FR') {
            return array_merge(
                $empty,
                ['countryName' => $country['name'], 'countryCode' => $country['code']],
                $this->fromFrenchLocationParts($parts),
            );
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
     * @return list<string>
     */
    private function locationParts(string $locationLabel): array
    {
        $locationLabel = html_entity_decode($locationLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $locationLabel = preg_replace('/<br\s*\/?>/i', ',', $locationLabel) ?? $locationLabel;
        $locationLabel = strip_tags($locationLabel);

        return array_values(array_filter(array_map('trim', explode(',', $locationLabel)), static fn (string $part): bool => $part !== ''));
    }

    /**
     * @param list<string> $parts
     *
     * @return array{regionName: ?string, departmentName: ?string, cityName: ?string}
     */
    private function fromFrenchLocationParts(array $parts): array
    {
        $geo = [
            'regionName' => null,
            'departmentName' => null,
            'cityName' => null,
        ];
        $postalIndex = null;
        $postalCode = null;

        foreach ($parts as $index => $part) {
            if (preg_match('/\b([0-9]{5})\b/', $part, $matches) === 1) {
                $postalIndex = $index;
                $postalCode = $matches[1];
                break;
            }
        }

        if ($postalCode === null || $postalIndex === null) {
            $city = $this->firstLocationNameCandidate($parts);

            return [
                'regionName' => null,
                'departmentName' => null,
                'cityName' => $city,
            ];
        }

        $area = $this->frenchPostalArea($postalCode);
        if ($area !== null) {
            $geo['regionName'] = $area['regionName'];
            $geo['departmentName'] = $area['departmentName'];
        }

        $city = $parts[max(0, $postalIndex - 2)] ?? null;
        if ($city === null || $this->looksLikeStreetOrVenue($city)) {
            $city = $parts[max(0, $postalIndex - 1)] ?? null;
        }
        if ($city !== null && !$this->looksLikeStreetOrVenue($city)) {
            $geo['cityName'] = $city;
        }

        return $geo;
    }

    /**
     * @param list<string> $parts
     */
    private function firstLocationNameCandidate(array $parts): ?string
    {
        foreach ($parts as $part) {
            if (!$this->looksLikeStreetOrVenue($part) && $this->country($part) === null) {
                return $part;
            }
        }

        return null;
    }

    /**
     * @return array{departmentName: string, regionName: string}|null
     */
    private function frenchPostalArea(string $postalCode): ?array
    {
        $overseas = substr($postalCode, 0, 3);
        if (isset(self::FRENCH_POSTAL_AREAS[$overseas])) {
            return self::FRENCH_POSTAL_AREAS[$overseas];
        }

        return self::FRENCH_POSTAL_AREAS[substr($postalCode, 0, 2)] ?? null;
    }

    private function trustedFrenchRegionOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null || preg_match('/[0-9,<>]/', $value) === 1) {
            return null;
        }

        $key = $this->asciiKey($value);
        foreach (self::FRENCH_POSTAL_AREAS as $area) {
            if ($this->asciiKey($area['regionName']) === $key) {
                return $area['regionName'];
            }
        }

        return null;
    }

    private function looksLikeStreetOrVenue(string $value): bool
    {
        $key = $this->asciiKey($value);

        return preg_match('/\b(rue|avenue|boulevard|chemin|impasse|route|allee|place|quai|cours|crossfit|club|training|academy|athletic|athletique|district|factory|box)\b/', $key) === 1;
    }

    private function asciiKey(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii === false) {
            $ascii = $value;
        }

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($ascii, 'UTF-8')));
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
