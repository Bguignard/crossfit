<?php

namespace App\Tests;

use App\Services\Competition\CompetitionGeoNormalizer;
use PHPUnit\Framework\TestCase;

final class CompetitionGeoNormalizerTest extends TestCase
{
    public function testItKeepsExplicitStructuredGeo(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Fort Worth, TX',
            'countryName' => 'United States',
            'countryCode' => 'us',
            'regionName' => 'Texas',
            'departmentName' => 'Tarrant County',
            'cityName' => 'Fort Worth',
            'latitude' => '32.7555',
            'longitude' => '-97.3308',
            'isOnline' => false,
        ]);

        self::assertSame('United States', $geo['countryName']);
        self::assertSame('US', $geo['countryCode']);
        self::assertSame('Texas', $geo['regionName']);
        self::assertSame('Tarrant County', $geo['departmentName']);
        self::assertSame('Fort Worth', $geo['cityName']);
        self::assertSame(32.7555, $geo['latitude']);
        self::assertSame(-97.3308, $geo['longitude']);
    }

    public function testItDerivesKnownCountryFromLocationLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Brisbane, Queensland, Australia',
            'isOnline' => false,
        ]);

        self::assertSame('Australia', $geo['countryName']);
        self::assertSame('AU', $geo['countryCode']);
        self::assertSame('Queensland', $geo['regionName']);
        self::assertSame('Brisbane', $geo['cityName']);
    }

    public function testItLeavesOnlineCompetitionsUnclassified(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'En ligne',
            'countryName' => 'France',
            'isOnline' => true,
        ]);

        self::assertNull($geo['countryName']);
        self::assertNull($geo['countryCode']);
        self::assertNull($geo['cityName']);
    }
}
