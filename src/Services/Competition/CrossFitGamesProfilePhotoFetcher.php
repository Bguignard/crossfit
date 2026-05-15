<?php

namespace App\Services\Competition;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CrossFitGamesProfilePhotoFetcher
{
    private const PROFILE_PICTURE_PATTERN = '~https://profilepicsbucket\.crossfit\.com/[^"\'<>\s)]+~i';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function fetch(string $profileUrl): ?string
    {
        try {
            $html = $this->httpClient->request('GET', $profileUrl)->getContent();
        } catch (TransportExceptionInterface $exception) {
            throw new \RuntimeException('Could not fetch CrossFit Games profile.', previous: $exception);
        }

        return $this->extract($html);
    }

    private function extract(string $html): ?string
    {
        $contentVariants = [
            $html,
            html_entity_decode($html, ENT_QUOTES | ENT_HTML5),
            rawurldecode($html),
        ];

        foreach ($contentVariants as $content) {
            if (preg_match(self::PROFILE_PICTURE_PATTERN, $content, $matches) === 1) {
                return rtrim($matches[0], '\\');
            }
        }

        return null;
    }
}
