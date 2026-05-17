<?php

namespace App\Services\Competition;

use App\Entity\Competition\Competition;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CompetitionLogoFetcher
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function fetch(Competition $competition): ?string
    {
        foreach ($this->candidateUrls($competition) as $url) {
            try {
                $html = $this->httpClient->request('GET', $url)->getContent();
            } catch (TransportExceptionInterface $exception) {
                throw new \RuntimeException(sprintf('Could not fetch competition logo page %s.', $url), previous: $exception);
            } catch (\Throwable) {
                continue;
            }

            $logoUrl = match ($competition->getSourceName()) {
                'competition_corner' => $this->extractCompetitionCornerLogo($html, $url),
                'scoring_fit' => $this->extractScoringFitLogo($html, $url),
                default => null,
            };

            if ($logoUrl !== null) {
                return $logoUrl;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidateUrls(Competition $competition): array
    {
        $externalId = $competition->getExternalId();
        $sourceUrl = $competition->getSourceUrl();

        $urls = match ($competition->getSourceName()) {
            'competition_corner' => [
                sprintf('https://competitioncorner.net/events/%s/details', $externalId),
                $sourceUrl,
            ],
            'scoring_fit' => [
                sprintf('https://scoring.fit/%s', $externalId),
                $sourceUrl ? str_replace('https://scoring.fit/leaderboard/', 'https://scoring.fit/', $sourceUrl) : null,
                $sourceUrl,
            ],
            default => [],
        };

        return array_values(array_unique(array_filter($urls, static fn (?string $url): bool => $url !== null && $url !== '')));
    }

    private function extractCompetitionCornerLogo(string $html, string $sourceUrl): ?string
    {
        if (preg_match('~<img\b(?=[^>]*\bcustom-logo\b)[^>]*\bsrc=["\']([^"\']+)["\']~i', $html, $matches) !== 1) {
            return null;
        }

        return $this->absoluteUrl(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), $sourceUrl);
    }

    private function extractScoringFitLogo(string $html, string $sourceUrl): ?string
    {
        if (preg_match('~<(?:div|section)\b(?=[^>]*\b(?:hero-image-logo|logo-image-background)\b)[^>]*>.*?<img\b[^>]*\bsrc=["\']([^"\']+)["\']~is', $html, $matches) !== 1) {
            return null;
        }

        return $this->absoluteUrl(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), $sourceUrl);
    }

    private function absoluteUrl(string $url, string $sourceUrl): string
    {
        if (preg_match('~^https?://~i', $url) === 1) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        $sourceParts = parse_url($sourceUrl);
        $scheme = isset($sourceParts['scheme']) ? $sourceParts['scheme'].'://' : 'https://';
        $host = $sourceParts['host'] ?? '';
        if (str_starts_with($url, '/')) {
            return $scheme.$host.$url;
        }

        $path = $sourceParts['path'] ?? '/';
        $basePath = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $scheme.$host.$basePath.'/'.$url;
    }
}
