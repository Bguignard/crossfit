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
        if ($competition->getSourceName() === 'scoring_fit') {
            $logoUrl = $this->fetchScoringFitApiLogo($competition->getExternalId());
            if ($logoUrl !== null) {
                return $logoUrl;
            }

            $logoUrl = $this->fetchScoringFitStoredLogo($competition->getExternalId());
            if ($logoUrl !== null) {
                return $logoUrl;
            }
        }

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
            foreach ($this->competitionCornerLogoCandidates($html) as $candidate) {
                return $this->competitionCornerImageUrl($candidate, $sourceUrl);
            }

            return null;
        }

        return $this->competitionCornerImageUrl(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), $sourceUrl);
    }

    /**
     * @return list<string>
     */
    private function competitionCornerLogoCandidates(string $html): array
    {
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
        $patterns = [
            '~"eventPageLogoImage"\s*:\s*"([^"]+)"~',
            '~"logo"\s*:\s*"([^"]+)"~',
            '~<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']~i',
            '~<meta\s+name=["\']twitter:image["\']\s+content=["\']([^"\']+)["\']~i',
        ];

        $candidates = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $decoded, $matches) !== false) {
                foreach ($matches[1] as $match) {
                    $candidate = trim($match);
                    if ($candidate !== '') {
                        $candidates[] = $candidate;
                    }
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    private function competitionCornerImageUrl(string $url, string $sourceUrl): string
    {
        if (preg_match('~^(?:https?:)?//|^/~i', $url) === 1 || str_starts_with($url, 'file.aspx')) {
            return $this->absoluteUrl($url, $sourceUrl);
        }

        if (preg_match('~^[A-Za-z]+/.+\.(?:jpe?g|png|gif|webp)$~i', $url) === 1) {
            $path = str_replace('%2F', '%2f', rawurlencode($url));

            return sprintf('https://competitioncorner.net/file.aspx/mainFilesystem?%s&thumbnail=1080,1080', $path);
        }

        return $this->absoluteUrl($url, $sourceUrl);
    }

    private function extractScoringFitLogo(string $html, string $sourceUrl): ?string
    {
        if (preg_match('~<(?:div|section)\b(?=[^>]*\b(?:hero-image-logo|logo-image-background)\b)[^>]*>.*?<img\b[^>]*\bsrc=["\']([^"\']+)["\']~is', $html, $matches) !== 1) {
            return null;
        }

        return $this->absoluteUrl(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), $sourceUrl);
    }

    private function fetchScoringFitStoredLogo(string $externalId): ?string
    {
        if (preg_match('~^[a-f0-9]{24}$~i', $externalId) !== 1) {
            return null;
        }

        foreach (['png', 'jpg'] as $extension) {
            $logoUrl = sprintf('https://scoring-images.s3.eu-west-3.amazonaws.com/events/%s/logo.%s', $externalId, $extension);

            try {
                $statusCode = $this->httpClient->request('HEAD', $logoUrl)->getStatusCode();
            } catch (TransportExceptionInterface $exception) {
                throw new \RuntimeException(sprintf('Could not fetch Scoring.fit logo %s.', $logoUrl), previous: $exception);
            } catch (\Throwable) {
                continue;
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                return $logoUrl;
            }
        }

        return null;
    }

    private function fetchScoringFitApiLogo(string $externalId): ?string
    {
        $apiUrl = sprintf('https://scoring-fit-prod-7a29180d25c8.herokuapp.com/api/leaderboard/competition/%s', rawurlencode($externalId));

        try {
            $response = $this->httpClient->request('GET', $apiUrl);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return null;
            }

            $payload = json_decode($response->getContent(false), true, flags: JSON_THROW_ON_ERROR);
        } catch (TransportExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not fetch Scoring.fit competition API %s.', $apiUrl), previous: $exception);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload)) {
            return null;
        }

        $logoUrl = $payload['data']['competition']['iconLink'] ?? null;
        if (!is_string($logoUrl)) {
            return null;
        }

        $logoUrl = trim($logoUrl);
        if ($logoUrl === '' || preg_match('~^https?://~i', $logoUrl) !== 1) {
            return null;
        }

        if (!$this->isPublicImageUrl($logoUrl)) {
            return null;
        }

        return $logoUrl;
    }

    private function isPublicImageUrl(string $url): bool
    {
        try {
            $statusCode = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Range' => 'bytes=0-0',
                ],
            ])->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            throw new \RuntimeException(sprintf('Could not validate image URL %s.', $url), previous: $exception);
        } catch (\Throwable) {
            return false;
        }

        return $statusCode >= 200 && $statusCode < 300;
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
