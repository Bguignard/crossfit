<?php

namespace App\Tests;

use App\Services\Competition\CrossFitGamesProfilePhotoFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CrossFitGamesProfilePhotoFetcherTest extends TestCase
{
    public function testFetchesPhotoFromProfileHtml(): void
    {
        $fetcher = new CrossFitGamesProfilePhotoFetcher(new MockHttpClient([
            new MockResponse('<img src="https://profilepicsbucket.crossfit.com/767cc-P975774_3-184.jpg" />'),
        ]));

        self::assertSame(
            'https://profilepicsbucket.crossfit.com/767cc-P975774_3-184.jpg',
            $fetcher->fetch('https://games.crossfit.com/athlete/975774'),
        );
    }

    public function testFetchesPhotoFromUrlEncodedProfileHtml(): void
    {
        $fetcher = new CrossFitGamesProfilePhotoFetcher(new MockHttpClient([
            new MockResponse('{"avatar":"https%3A%2F%2Fprofilepicsbucket.crossfit.com%2Ff1d92-P163097_5-184.jpg"}'),
        ]));

        self::assertSame(
            'https://profilepicsbucket.crossfit.com/f1d92-P163097_5-184.jpg',
            $fetcher->fetch('https://games.crossfit.com/athlete/163097'),
        );
    }

    public function testReturnsNullWhenNoPhotoIsPresent(): void
    {
        $fetcher = new CrossFitGamesProfilePhotoFetcher(new MockHttpClient([
            new MockResponse('<html>No profile photo here.</html>'),
        ]));

        self::assertNull($fetcher->fetch('https://games.crossfit.com/athlete/unknown'));
    }
}
