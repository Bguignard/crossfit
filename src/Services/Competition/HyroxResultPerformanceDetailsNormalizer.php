<?php

namespace App\Services\Competition;

use App\Entity\Competition\WorkoutResult;

final class HyroxResultPerformanceDetailsNormalizer
{
    /**
     * @return array<string, mixed>|null
     */
    public function normalize(WorkoutResult $result): ?array
    {
        $breakdown = $result->getPerformanceBreakdown();
        if (!$this->isHyroxResult($result, $breakdown)) {
            return null;
        }

        $score = $result->getScore();
        $competition = $result->getEvent()->getCompetition();
        $division = $result->getCompetitionDivision()?->getName() ?? $result->getDivision();
        $totalTime = $breakdown !== null && is_array($breakdown['totalTime'] ?? null) ? $breakdown['totalTime'] : [];

        $payload = [
            'sport' => 'hyrox',
            'resultKind' => 'competition_result',
            'competition' => [
                'name' => $competition->getName(),
                'sourceName' => $competition->getSourceName(),
                'externalId' => $competition->getExternalId(),
                'startsAt' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
                'endsAt' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
            ],
            'event' => [
                'name' => $result->getEvent()->getName(),
                'order' => $result->getEvent()->getEventOrder(),
            ],
            'division' => $division,
            'rank' => $result->getRank(),
            'fieldSize' => $result->getFieldSize(),
            'totalTime' => [
                'display' => $this->stringValue($breakdown['total_time_display'] ?? $breakdown['totalTimeDisplay'] ?? $totalTime['display'] ?? null)
                    ?? $score->getDisplayValue()
                    ?? $score->getRawValue(),
                'seconds' => $this->intValue($breakdown['total_time_seconds'] ?? $breakdown['totalTimeSeconds'] ?? $totalTime['seconds'] ?? null)
                    ?? $score->getTimeInSeconds(),
            ],
            'source' => [
                'name' => $result->getSourceName(),
                'url' => $result->getSourceUrl() ?? $result->getEvent()->getSourceUrl() ?? $competition->getSourceUrl(),
                'externalId' => $result->getExternalId(),
            ],
            'segments' => $this->segments($breakdown),
        ];

        foreach ([
            'category' => $this->stringValue($breakdown['category'] ?? null),
            'resultSummary' => $this->arrayValue($breakdown['resultSummary'] ?? $breakdown['result_summary'] ?? null),
            'segmentGroups' => $this->arrayValue($breakdown['segmentGroups'] ?? $breakdown['segment_groups'] ?? null),
            'analysisSummary' => $this->arrayValue($breakdown['analysisSummary'] ?? $breakdown['analysis_summary'] ?? null),
            'exportQuality' => $this->arrayValue($breakdown['exportQuality'] ?? $breakdown['export_quality'] ?? $breakdown['qualityFlags'] ?? $breakdown['quality_flags'] ?? null),
            'missingSegments' => $this->arrayListValue($breakdown['missingSegments'] ?? $breakdown['missing_segments'] ?? null),
        ] as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|null $breakdown
     */
    private function isHyroxResult(WorkoutResult $result, ?array $breakdown): bool
    {
        $sport = $this->stringValue($breakdown['sport'] ?? null);
        if ($sport !== null && strtolower($sport) === 'hyrox') {
            return true;
        }

        foreach ([
            $result->getSourceName(),
            $result->getEvent()->getSourceName(),
            $result->getEvent()->getCompetition()->getSourceName(),
            $result->getEvent()->getCompetition()->getCompetitionType(),
            $result->getEvent()->getCompetition()->getName(),
        ] as $candidate) {
            if (is_string($candidate) && str_contains(strtolower($candidate), 'hyrox')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $breakdown
     *
     * @return list<array<string, mixed>>
     */
    private function segments(?array $breakdown): array
    {
        if ($breakdown === null) {
            return [];
        }

        $rawSegments = $breakdown['segments'] ?? $breakdown['splits'] ?? [];
        if (!is_array($rawSegments)) {
            return [];
        }

        $segments = [];
        foreach ($rawSegments as $index => $segment) {
            if (!is_array($segment)) {
                continue;
            }

            $order = $this->intValue($segment['order'] ?? $segment['position'] ?? null) ?? ($index + 1);
            $time = is_array($segment['time'] ?? null) ? $segment['time'] : [];
            $displayTime = $this->stringValue(
                $segment['time_display'] ?? $segment['display_time'] ?? $segment['displayTime'] ?? $time['display'] ?? null
            );
            $duration = $this->stringValue($segment['duration'] ?? $segment['time'] ?? null);
            $durationSeconds = $this->intValue($segment['durationSeconds'] ?? $segment['duration_seconds'] ?? null);
            $timeSeconds = $this->intValue($segment['time_seconds'] ?? $segment['timeSeconds'] ?? $time['seconds'] ?? null)
                ?? $durationSeconds;

            $segments[] = [
                'order' => $order,
                'type' => $this->segmentType($segment['type'] ?? $segment['category'] ?? $segment['kind'] ?? null),
                'key' => $this->stringValue($segment['key'] ?? null),
                'name' => $this->stringValue($segment['name'] ?? null),
                'label' => $this->stringValue($segment['label'] ?? null),
                'sourceLabel' => $this->stringValue($segment['sourceLabel'] ?? $segment['source_label'] ?? null),
                'displayLabel' => $this->stringValue($segment['displayLabel'] ?? $segment['display_label'] ?? null),
                'canonicalName' => $this->stringValue($segment['canonicalName'] ?? $segment['canonical_name'] ?? null),
                'category' => $this->stringValue($segment['category'] ?? null),
                'kind' => $this->stringValue($segment['kind'] ?? null),
                'stationNumber' => $this->intValue($segment['station_number'] ?? $segment['stationNumber'] ?? null),
                'distanceMeters' => $this->intValue($segment['distance_meters'] ?? $segment['distanceMeters'] ?? null),
                'reps' => $this->intValue($segment['reps'] ?? null),
                'rank' => $this->intValue($segment['rank'] ?? null),
                'analysisArea' => $this->stringValue($segment['analysisArea'] ?? $segment['analysis_area'] ?? null),
                'duration' => $duration ?? $displayTime,
                'durationSeconds' => $durationSeconds ?? $timeSeconds,
                'time' => [
                    'display' => $displayTime ?? $duration,
                    'seconds' => $timeSeconds,
                ],
                'rawValue' => $this->stringValue($segment['raw_value'] ?? $segment['rawValue'] ?? null),
            ];
        }

        usort(
            $segments,
            static fn (array $left, array $right): int => ($left['order'] <=> $right['order'])
        );

        return array_values($segments);
    }

    private function segmentType(mixed $value): string
    {
        $type = strtolower((string) ($this->stringValue($value) ?? 'station'));
        $type = str_replace([' ', '-'], '_', $type);

        return in_array($type, ['run', 'station', 'roxzone', 'transition'], true) ? $type : 'station';
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function arrayValue(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function arrayListValue(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function intValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value);
        }
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }
}
