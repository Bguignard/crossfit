<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Services\Competition\HyroxResultPerformanceDetailsNormalizer;
use PHPUnit\Framework\TestCase;

class HyroxResultPerformanceDetailsNormalizerTest extends TestCase
{
    public function testNormalizeExposesDetailedHyroxPerformanceContract(): void
    {
        $competition = (new Competition('HYROX Sydney 2026', 'hyrox', 'hyrox-sydney-2026'))
            ->setCompetitionType('hyrox')
            ->setStartsAt(new \DateTimeImmutable('2026-07-01T00:00:00+10:00'));
        $event = (new CompetitionEvent($competition, 'HYROX Pro Men', 'hyrox', 'hyrox-sydney-2026-pro-men'))
            ->setEventOrder(1);
        $result = (new WorkoutResult(
            new Athlete('James Hansen', 'hyrox', 'hyrox-james'),
            $event,
            (new Score(ScoreTypeEnum::TIME, '57:57'))->setDisplayValue('57:57')->setTimeInSeconds(3477),
            'hyrox',
            'hyrox-result-1',
        ))
            ->setDivision('30-34')
            ->setRank(1)
            ->setFieldSize(120)
            ->setPerformanceBreakdown([
                'sport' => 'hyrox',
                'category' => 'HYROX MEN',
                'totalTime' => '57:57',
                'totalTimeSeconds' => 3477,
                'resultSummary' => [
                    'category' => 'total',
                    'displayLabel' => 'Total',
                    'duration' => '57:57',
                    'durationSeconds' => 3477,
                    'rank' => 1,
                ],
                'segments' => [
                    [
                        'key' => 'run_1',
                        'order' => 1,
                        'kind' => 'run',
                        'category' => 'run',
                        'name' => 'Running 1',
                        'sourceLabel' => 'Running 1',
                        'displayLabel' => 'Run 1',
                        'duration' => '02:55',
                        'durationSeconds' => 175,
                        'analysisArea' => 'running',
                    ],
                    [
                        'key' => 'skierg',
                        'order' => 2,
                        'kind' => 'station',
                        'category' => 'station',
                        'name' => '1000m SkiErg',
                        'sourceLabel' => '1000m SkiErg',
                        'displayLabel' => 'SkiErg',
                        'canonicalName' => 'SkiErg',
                        'duration' => '03:30',
                        'durationSeconds' => 210,
                        'rank' => 4,
                        'analysisArea' => 'ergs_engine',
                    ],
                    [
                        'key' => 'roxzone',
                        'order' => 17,
                        'kind' => 'transition',
                        'category' => 'roxzone',
                        'name' => 'Roxzone',
                        'displayLabel' => 'Roxzone',
                        'duration' => '04:52',
                        'durationSeconds' => 292,
                        'analysisArea' => 'roxzone_transitions',
                    ],
                ],
                'segmentGroups' => [
                    'runs' => [['key' => 'run_1', 'displayLabel' => 'Run 1']],
                    'stations' => [['key' => 'skierg', 'displayLabel' => 'SkiErg']],
                    'roxzone' => [['key' => 'roxzone', 'displayLabel' => 'Roxzone']],
                    'unknown' => [],
                ],
                'analysisSummary' => [
                    'areas' => [
                        'running' => ['segmentCount' => 1, 'totalDurationSeconds' => 175],
                        'ergs_engine' => ['segmentCount' => 1, 'totalDurationSeconds' => 210],
                        'roxzone_transitions' => ['segmentCount' => 1, 'totalDurationSeconds' => 292],
                    ],
                ],
                'exportQuality' => [
                    'expectedSegmentCount' => 17,
                    'knownSegmentCount' => 3,
                    'missingSegmentCount' => 14,
                    'isComplete' => false,
                    'missingSegmentKeys' => ['run_2'],
                ],
                'missingSegments' => [
                    ['key' => 'run_2', 'name' => 'Run 2', 'kind' => 'run', 'order' => 3],
                ],
            ]);

        $payload = (new HyroxResultPerformanceDetailsNormalizer())->normalize($result);

        self::assertIsArray($payload);
        self::assertSame('hyrox', $payload['sport']);
        self::assertSame('competition_result', $payload['resultKind']);
        self::assertSame('HYROX MEN', $payload['category']);
        self::assertSame('HYROX Sydney 2026', $payload['competition']['name']);
        self::assertSame('HYROX Pro Men', $payload['event']['name']);
        self::assertSame('30-34', $payload['division']);
        self::assertSame(['display' => '57:57', 'seconds' => 3477], $payload['totalTime']);
        self::assertSame('Total', $payload['resultSummary']['displayLabel']);
        self::assertSame(['run_1', 'skierg', 'roxzone'], array_column($payload['segments'], 'key'));
        self::assertSame('Run 1', $payload['segments'][0]['displayLabel']);
        self::assertSame('ergs_engine', $payload['segments'][1]['analysisArea']);
        self::assertSame(['display' => '03:30', 'seconds' => 210], $payload['segments'][1]['time']);
        self::assertSame('roxzone', $payload['segments'][2]['type']);
        self::assertSame(['run_1'], array_column($payload['segmentGroups']['runs'], 'key'));
        self::assertSame(175, $payload['analysisSummary']['areas']['running']['totalDurationSeconds']);
        self::assertFalse($payload['exportQuality']['isComplete']);
        self::assertSame(['run_2'], $payload['exportQuality']['missingSegmentKeys']);
        self::assertSame('Run 2', $payload['missingSegments'][0]['name']);
    }
}
