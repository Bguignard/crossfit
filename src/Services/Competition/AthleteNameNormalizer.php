<?php

namespace App\Services\Competition;

final class AthleteNameNormalizer
{
    public function normalize(string $name): string
    {
        $normalized = trim($name);
        if ($normalized === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        } else {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            if (is_string($transliterated)) {
                $normalized = $transliterated;
            }
        }

        $normalized = strtolower($normalized);
        $normalized = str_replace(['-', '_'], ' ', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
    }
}
