<?php

namespace App\Tests;

use App\Services\Competition\CompetitionGeoNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
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

    public function testItDerivesNordicCountryFromLocationLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Ringkøbing, Jylland, Denmark',
            'isOnline' => false,
        ]);

        self::assertSame('Denmark', $geo['countryName']);
        self::assertSame('DK', $geo['countryCode']);
        self::assertSame('Jylland', $geo['regionName']);
        self::assertSame('Ringkøbing', $geo['cityName']);
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

    public function testItDerivesFrenchRegionFromHtmlAddressAndPostalCode(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'CrossFit Louviers<br />10 Rue des Entrepots<br />Louviers, Eure, 27400, France',
            'isOnline' => false,
        ]);

        self::assertSame('France', $geo['countryName']);
        self::assertSame('FR', $geo['countryCode']);
        self::assertSame('Normandie', $geo['regionName']);
        self::assertSame('Eure', $geo['departmentName']);
        self::assertSame('Louviers', $geo['cityName']);
    }

    public function testItReplacesPollutedFrenchRegionFromLocationLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'DISTRICT CROSSFIT BRUNSTATT DIDENHEIM<br />3, Avenue de Bruxelles<br />Brunstatt-Didenheim, Haut-Rhin, 68350, France',
            'countryName' => 'France',
            'regionName' => 'Avenue de Bruxelles<br />Brunstatt-Didenheim, Haut-Rhin, 68350',
            'cityName' => 'DISTRICT CROSSFIT BRUNSTATT DIDENHEIM',
            'isOnline' => false,
        ]);

        self::assertSame('France', $geo['countryName']);
        self::assertSame('FR', $geo['countryCode']);
        self::assertSame('Grand Est', $geo['regionName']);
        self::assertSame('Haut-Rhin', $geo['departmentName']);
        self::assertSame('Brunstatt-Didenheim', $geo['cityName']);
    }

    public function testItDerivesFrenchRegionWhenRawRegionIsHistorical(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Plateau d’évolution de la Couronne<br />chemin du phare<br />La Couronne, Bouche du Rhone, 13500, France',
            'isOnline' => false,
        ]);

        self::assertSame('Provence-Alpes-Côte d’Azur', $geo['regionName']);
        self::assertSame('Bouches-du-Rhône', $geo['departmentName']);
        self::assertSame('La Couronne', $geo['cityName']);
    }

    public function testItDerivesFrenchRegionFromAddressWithCountryBeforePostalCode(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'H. TRAINING CLUB<br />20 RUE HELIOT<br />Toulouse, FRANCE, 31000, France',
            'isOnline' => false,
        ]);

        self::assertSame('Occitanie', $geo['regionName']);
        self::assertSame('Haute-Garonne', $geo['departmentName']);
        self::assertSame('Toulouse', $geo['cityName']);
    }

    public function testItDerivesFrenchRegionFromDepartmentLabelWithoutPostalCode(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Wittelsheim, haut-rhin, France',
            'isOnline' => false,
        ]);

        self::assertSame('Grand Est', $geo['regionName']);
        self::assertSame('Haut-Rhin', $geo['departmentName']);
        self::assertSame('Wittelsheim', $geo['cityName']);
    }

    public function testItDerivesFrenchRegionFromLegacyRegionLabelWithoutPostalCode(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Langon, Gironde, France',
            'isOnline' => false,
        ]);

        self::assertSame('Nouvelle-Aquitaine', $geo['regionName']);
        self::assertSame('Gironde', $geo['departmentName']);
        self::assertSame('Langon', $geo['cityName']);
    }

    public function testItDerivesFrenchOverseasAreaFromFwiLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Saint barthelemy, FWI, France',
            'isOnline' => false,
        ]);

        self::assertSame('Saint-Barthélemy', $geo['regionName']);
        self::assertSame('Saint-Barthélemy', $geo['departmentName']);
        self::assertSame('Saint barthelemy', $geo['cityName']);
    }

    public function testItDerivesFrenchOverseasAreaWhenUsedAsCountryLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Baie mahault, guadeloupe, Guadeloupe',
            'isOnline' => false,
        ]);

        self::assertSame('France', $geo['countryName']);
        self::assertSame('FR', $geo['countryCode']);
        self::assertSame('Guadeloupe', $geo['regionName']);
        self::assertSame('Guadeloupe', $geo['departmentName']);
        self::assertSame('Baie mahault', $geo['cityName']);
    }

    public function testItDerivesReunionWhenUsedAsCountryLabel(): void
    {
        $geo = (new CompetitionGeoNormalizer())->fromImportRow([
            'locationLabel' => 'Sainte-clotilde, Sainte-Clotilde, Reunion',
            'isOnline' => false,
        ]);

        self::assertSame('France', $geo['countryName']);
        self::assertSame('FR', $geo['countryCode']);
        self::assertSame('La Réunion', $geo['regionName']);
        self::assertSame('La Réunion', $geo['departmentName']);
        self::assertSame('Sainte-clotilde', $geo['cityName']);
    }
}
