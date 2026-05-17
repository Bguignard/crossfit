<?php

namespace App\Tests;

use App\Entity\Competition\Competition;
use App\Services\Competition\CompetitionLogoFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CompetitionLogoFetcherTest extends TestCase
{
    public function testFetchesCompetitionCornerLogo(): void
    {
        $fetcher = new CompetitionLogoFetcher(new MockHttpClient([
            new MockResponse('<img alt="" class="custom-logo mt-0" src="https://competitioncorner.net/file.aspx/mainFilesystem?General%2f6g7sow2b3.jpg&amp;thumbnail=1080,1080">'),
        ]));
        $competition = new Competition('French Throwdown', 'competition_corner', '20465');

        self::assertSame(
            'https://competitioncorner.net/file.aspx/mainFilesystem?General%2f6g7sow2b3.jpg&thumbnail=1080,1080',
            $fetcher->fetch($competition),
        );
    }

    public function testFetchesCompetitionCornerLogoFromEncodedEventPayload(): void
    {
        $fetcher = new CompetitionLogoFetcher(new MockHttpClient([
            new MockResponse('<app-root visual="{&quot;logo&quot;:&quot;https://competitioncorner.net/file.aspx/mainFilesystem?Events%2fck56xibbz.jpg&amp;thumbnail=1080,1080&quot;}" eventdata="{&quot;event&quot;:{&quot;eventPageLogoImage&quot;:&quot;General/6g7sow2b3.jpg&quot;}}"></app-root>'),
        ]));
        $competition = new Competition('French Throwdown', 'competition_corner', '20465');

        self::assertSame(
            'https://competitioncorner.net/file.aspx/mainFilesystem?General%2f6g7sow2b3.jpg&thumbnail=1080,1080',
            $fetcher->fetch($competition),
        );
    }

    public function testFetchesCompetitionCornerLogoFromVisualPayloadWhenNoEventPageLogoExists(): void
    {
        $fetcher = new CompetitionLogoFetcher(new MockHttpClient([
            new MockResponse('<app-root visual="{&quot;logo&quot;:&quot;https://competitioncorner.net/file.aspx/mainFilesystem?Events%2f455a18m3n.png&amp;thumbnail=1080,1080&quot;}"></app-root>'),
        ]));
        $competition = new Competition('Wodapalooza', 'competition_corner', '19193');

        self::assertSame(
            'https://competitioncorner.net/file.aspx/mainFilesystem?Events%2f455a18m3n.png&thumbnail=1080,1080',
            $fetcher->fetch($competition),
        );
    }

    public function testFetchesScoringFitLogo(): void
    {
        $fetcher = new CompetitionLogoFetcher(new MockHttpClient([
            new MockResponse('<div class="hero-image-logo"><img src="https://scoring-images.s3.eu-west-3.amazonaws.com/events/logo.png?1779003354922" alt="logo"></div>'),
        ]));
        $competition = new Competition('Scoring Event', 'scoring_fit', '3051');

        self::assertSame(
            'https://scoring-images.s3.eu-west-3.amazonaws.com/events/logo.png?1779003354922',
            $fetcher->fetch($competition),
        );
    }

    public function testReturnsNullWhenNoLogoIsPresent(): void
    {
        $fetcher = new CompetitionLogoFetcher(new MockHttpClient([
            new MockResponse('<html>No logo here.</html>'),
        ]));
        $competition = new Competition('Scoring Event', 'scoring_fit', '3051');

        self::assertNull($fetcher->fetch($competition));
    }
}
