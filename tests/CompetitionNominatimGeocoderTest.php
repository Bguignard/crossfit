<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Competition\CompetitionNominatimGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CompetitionNominatimGeocoderTest extends TestCase
{
    public function testItResolvesStructuredGeoFromNominatimSearch(): void
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('GET', $method);
            self::assertStringStartsWith('https://nominatim.openstreetmap.org/search?', $url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $headers = $options['normalized_headers'] ?? $options['headers'] ?? [];
            self::assertSame('User-Agent: MonWOD test geocoder', $headers['user-agent'][0] ?? null);
            self::assertSame('Woolston, christchurch, New Zealand, New zealand', $query['q'] ?? null);
            self::assertSame('jsonv2', $query['format'] ?? null);
            self::assertSame('1', $query['addressdetails'] ?? null);
            self::assertSame('1', $query['limit'] ?? null);

            return new MockResponse(json_encode([
                [
                    'lat' => '-43.5505',
                    'lon' => '172.6811',
                    'address' => [
                        'suburb' => 'Woolston',
                        'city' => 'Christchurch',
                        'state' => 'Canterbury',
                        'country' => 'New Zealand',
                        'country_code' => 'nz',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $result = (new CompetitionNominatimGeocoder(
            $client,
            'https://nominatim.openstreetmap.org',
            'MonWOD test geocoder',
            10,
            0,
        ))->resolve('Woolston, christchurch, New Zealand, New zealand');

        self::assertNotNull($result);
        self::assertSame('nominatim', $result['provider']);
        self::assertSame('New Zealand', $result['geo']['countryName']);
        self::assertSame('NZ', $result['geo']['countryCode']);
        self::assertSame('Canterbury', $result['geo']['regionName']);
        self::assertSame('Christchurch', $result['geo']['cityName']);
        self::assertSame(-43.5505, $result['geo']['latitude']);
        self::assertSame(172.6811, $result['geo']['longitude']);
    }

    public function testItCleansHtmlAddressesBeforeQuerying(): void
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            self::assertSame('Sofia, 1000, 1766, Bulgaria', $query['q'] ?? null);

            return new MockResponse(json_encode([
                [
                    'lat' => '42.6977',
                    'lon' => '23.3219',
                    'address' => [
                        'city' => 'Sofia',
                        'state' => 'Sofia City',
                        'country' => 'Bulgaria',
                        'country_code' => 'bg',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $result = (new CompetitionNominatimGeocoder(
            $client,
            'https://nominatim.openstreetmap.org',
            'MonWOD test geocoder',
            10,
            0,
        ))->resolve('Crossfit Vitosha<br />Donka Ushlinova 2<br />Sofia, 1000, 1766, Bulgaria');

        self::assertNotNull($result);
        self::assertSame('Bulgaria', $result['geo']['countryName']);
        self::assertSame('BG', $result['geo']['countryCode']);
        self::assertSame('Sofia City', $result['geo']['regionName']);
        self::assertSame('Sofia', $result['geo']['cityName']);
    }

    public function testItFallsBackAcrossCandidateQueriesUntilOneResolves(): void
    {
        $queries = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$queries): MockResponse {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $queries[] = $query['q'] ?? null;

            if (count($queries) === 1) {
                return new MockResponse(json_encode([], JSON_THROW_ON_ERROR));
            }

            return new MockResponse(json_encode([
                [
                    'lat' => '42.5751',
                    'lon' => '-71.9981',
                    'address' => [
                        'city' => 'Gardner',
                        'state' => 'Massachusetts',
                        'county' => 'Worcester County',
                        'country' => 'United States',
                        'country_code' => 'us',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
        });

        $result = (new CompetitionNominatimGeocoder(
            $client,
            'https://nominatim.openstreetmap.org',
            'MonWOD test geocoder',
            10,
            0,
        ))->resolve('CrossFit 696<br />696 W Broadway<br />Gardner, MA, 01440, United States');

        self::assertNotNull($result);
        self::assertSame([
            'Gardner, MA, 01440, United States',
            'MA, 01440, United States',
        ], $queries);
        self::assertSame('United States', $result['geo']['countryName']);
        self::assertSame('US', $result['geo']['countryCode']);
        self::assertSame('Massachusetts', $result['geo']['regionName']);
        self::assertSame('Worcester County', $result['geo']['departmentName']);
        self::assertSame('Gardner', $result['geo']['cityName']);
    }

    public function testItReturnsNullWhenResponseHasNoUsableCountry(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode([['address' => ['city' => 'Unknown']]], JSON_THROW_ON_ERROR)),
        ]);

        $result = (new CompetitionNominatimGeocoder(
            $client,
            'https://nominatim.openstreetmap.org',
            'MonWOD test geocoder',
            10,
            0,
        ))->resolve('Unknown');

        self::assertNull($result);
    }
}
