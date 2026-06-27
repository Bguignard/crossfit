<?php

namespace App\Command;

use App\Services\Workout\Audit\TeamWorkoutStructurePatternClassifier;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:audit-team-structures',
    description: 'Audit team structure patterns in imported competition workout flows.',
)]
final class AuditTeamWorkoutStructuresCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TeamWorkoutStructurePatternClassifier $classifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Filter by competition source_name.')
            ->addOption('participation-type', null, InputOption::VALUE_REQUIRED, 'Filter by competition participation_type. Default "team" includes team and both; use "team-only" for an exact team filter or "all" to disable it.', 'team')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum competition workouts to analyze.')
            ->addOption('examples-per-pattern', null, InputOption::VALUE_REQUIRED, 'Maximum examples to keep per detected pattern.', 3)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output the report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $examplesPerPattern = max(0, (int) $input->getOption('examples-per-pattern'));
        [$filters, $whereSql, $parameters] = $this->filters($input);
        $rows = $this->workouts($whereSql, $parameters, $this->intOrNull($input->getOption('limit')));
        $report = $this->buildReport($filters, $rows, $examplesPerPattern);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $io->title('Team workout structure audit');
        $io->definitionList(
            ['Competitions' => $report['summary']['competitionCount']],
            ['Events' => $report['summary']['eventCount']],
            ['Workouts analyzed' => $report['summary']['workoutCount']],
            ['Workouts with detected patterns' => $report['summary']['workoutsWithDetectedPatterns']],
            ['Filters' => $this->formatFilters($filters)],
        );
        $io->note('Pattern detection is heuristic text matching on imported workout flows. Use it to guide taxonomy weighting, then validate against real examples.');

        if ($report['summary']['workoutCount'] === 0) {
            $io->warning('No imported competition workout matched the selected filters.');

            return Command::SUCCESS;
        }

        $io->section('Pattern frequencies');
        $io->table(
            ['Pattern', 'Workouts', '% workouts'],
            array_map(
                static fn (array $pattern): array => [
                    $pattern['label'],
                    $pattern['workoutCount'],
                    sprintf('%.2f%%', $pattern['percentage']),
                ],
                $report['patternFrequencies'],
            ),
        );

        $io->section('Team sizes detected');
        $io->table(
            ['Team size', 'Workouts', '% workouts'],
            array_map(
                static fn (array $teamSize): array => [
                    $teamSize['teamSize'],
                    $teamSize['workoutCount'],
                    sprintf('%.2f%%', $teamSize['percentage']),
                ],
                $report['teamSizeFrequencies'],
            ),
        );

        if ($report['coOccurringPatternPairs'] !== []) {
            $io->section('Co-occurring pattern pairs');
            $io->table(
                ['Pattern A', 'Pattern B', 'Workouts', '% workouts'],
                array_map(
                    static fn (array $pair): array => [
                        $pair['labelA'],
                        $pair['labelB'],
                        $pair['workoutCount'],
                        sprintf('%.2f%%', $pair['percentage']),
                    ],
                    $report['coOccurringPatternPairs'],
                ),
            );
        }

        if ($examplesPerPattern > 0) {
            $io->section('Examples per pattern');
            foreach ($report['examplesPerPattern'] as $pattern => $examples) {
                if ($examples === []) {
                    continue;
                }

                $io->text(sprintf('%s:', $report['patternLabels'][$pattern] ?? $pattern));
                $io->table(
                    ['Workout', 'Source', 'Flow excerpt'],
                    array_map(
                        static fn (array $example): array => [
                            sprintf('%s (%s)', $example['name'], $example['id']),
                            $example['source'] ?? '-',
                            $example['flowExcerpt'],
                        ],
                        $examples,
                    ),
                );
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}
     */
    private function filters(InputInterface $input): array
    {
        $participationType = $this->normalizedStringOrNull($input->getOption('participation-type'));
        $filters = [
            'source' => $this->stringOrNull($input->getOption('source')),
            'participationType' => $participationType,
            'limit' => $this->intOrNull($input->getOption('limit')),
        ];
        $where = ['ce.workout_id IS NOT NULL'];
        $parameters = [];

        if ($filters['source'] !== null) {
            $where[] = 'c.source_name = :source';
            $parameters['source'] = $filters['source'];
        }

        if ($participationType === 'team') {
            $where[] = 'LOWER(COALESCE(c.participation_type, \'\')) IN (:participationTypeTeam, :participationTypeBoth)';
            $parameters['participationTypeTeam'] = 'team';
            $parameters['participationTypeBoth'] = 'both';
        } elseif ($participationType !== null && $participationType !== 'all') {
            $participationType = $participationType === 'team-only' ? 'team' : $participationType;
            $where[] = 'LOWER(COALESCE(c.participation_type, \'\')) = :participationType';
            $parameters['participationType'] = $participationType;
        }

        return [$filters, implode("\n                    AND ", $where), $parameters];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<array{id: string, name: string, flow: string, source: ?string, competitions: list<array{id: string, name: string}>, events: list<array{id: string, name: string}>}>
     */
    private function workouts(string $whereSql, array $parameters, ?int $limit): array
    {
        $limitSql = $limit !== null ? 'LIMIT '.max(1, $limit) : '';
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    WITH base_workouts AS (
                        SELECT DISTINCT w.id
                        FROM competition_event ce
                        INNER JOIN competition c ON c.id = ce.competition_id
                        INNER JOIN workout w ON w.id = ce.workout_id
                        WHERE %s
                        ORDER BY w.id
                        %s
                    )
                    SELECT
                        w.id::TEXT AS workout_id,
                        COALESCE(w.name, ce.name) AS workout_name,
                        w.flow,
                        COALESCE(w.source_name, c.source_name) AS source_name,
                        c.id::TEXT AS competition_id,
                        c.name AS competition_name,
                        ce.id::TEXT AS event_id,
                        ce.name AS event_name
                    FROM base_workouts bw
                    INNER JOIN workout w ON w.id = bw.id
                    INNER JOIN competition_event ce ON ce.workout_id = w.id
                    INNER JOIN competition c ON c.id = ce.competition_id
                    WHERE %s
                    ORDER BY w.id, c.name ASC, ce.event_order ASC NULLS LAST, ce.name ASC
                    SQL,
                $whereSql,
                $limitSql,
                $whereSql,
            ),
            $parameters,
        );

        return $this->aggregateWorkouts($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{id: string, name: string, flow: string, source: ?string, competitions: list<array{id: string, name: string}>, events: list<array{id: string, name: string}>}>
     */
    private function aggregateWorkouts(array $rows): array
    {
        $workouts = [];

        foreach ($rows as $row) {
            $workoutId = (string) $row['workout_id'];
            $workouts[$workoutId] ??= [
                'id' => $workoutId,
                'name' => (string) $row['workout_name'],
                'flow' => (string) $row['flow'],
                'source' => $row['source_name'] === null ? null : (string) $row['source_name'],
                'competitions' => [],
                'events' => [],
            ];

            $competitionId = (string) $row['competition_id'];
            $eventId = (string) $row['event_id'];
            $workouts[$workoutId]['competitions'][$competitionId] = [
                'id' => $competitionId,
                'name' => (string) $row['competition_name'],
            ];
            $workouts[$workoutId]['events'][$eventId] = [
                'id' => $eventId,
                'name' => (string) $row['event_name'],
            ];
        }

        return array_map(
            static fn (array $workout): array => [
                'id' => $workout['id'],
                'name' => $workout['name'],
                'flow' => $workout['flow'],
                'source' => $workout['source'],
                'competitions' => array_values($workout['competitions']),
                'events' => array_values($workout['events']),
            ],
            array_values($workouts),
        );
    }

    /**
     * @param array<string, mixed>                                                                                                                                                     $filters
     * @param list<array{id: string, name: string, flow: string, source: ?string, competitions: list<array{id: string, name: string}>, events: list<array{id: string, name: string}>}> $workouts
     *
     * @return array<string, mixed>
     */
    private function buildReport(array $filters, array $workouts, int $examplesPerPattern): array
    {
        $patternLabels = $this->classifier->patternLabels();
        $patternCounts = array_fill_keys(array_keys($patternLabels), 0);
        $teamSizeCounts = [];
        $pairCounts = [];
        $examples = array_fill_keys(array_keys($patternLabels), []);
        $competitionIds = [];
        $eventIds = [];
        $workoutsWithDetectedPatterns = 0;

        foreach ($workouts as $workout) {
            foreach ($workout['competitions'] as $competition) {
                $competitionIds[$competition['id']] = true;
            }
            foreach ($workout['events'] as $event) {
                $eventIds[$event['id']] = true;
            }

            $detection = $this->classifier->classify($workout['flow']);
            $patterns = $detection['patterns'];

            if ($patterns !== []) {
                ++$workoutsWithDetectedPatterns;
            }

            foreach ($patterns as $pattern) {
                ++$patternCounts[$pattern];

                if (count($examples[$pattern]) < $examplesPerPattern) {
                    $examples[$pattern][] = $this->example($workout);
                }
            }

            foreach ($detection['teamSizes'] as $teamSize) {
                $teamSizeCounts[$teamSize] ??= 0;
                ++$teamSizeCounts[$teamSize];
            }

            sort($patterns);
            for ($left = 0; $left < count($patterns); ++$left) {
                for ($right = $left + 1; $right < count($patterns); ++$right) {
                    $key = $patterns[$left].'|'.$patterns[$right];
                    $pairCounts[$key] ??= 0;
                    ++$pairCounts[$key];
                }
            }
        }

        $workoutCount = count($workouts);

        return [
            'kind' => 'team_workout_structure_audit_v1',
            'heuristic' => true,
            'filters' => $filters,
            'summary' => [
                'competitionCount' => count($competitionIds),
                'eventCount' => count($eventIds),
                'workoutCount' => $workoutCount,
                'workoutsWithDetectedPatterns' => $workoutsWithDetectedPatterns,
                'workoutsWithoutDetectedPatterns' => max(0, $workoutCount - $workoutsWithDetectedPatterns),
            ],
            'patternLabels' => $patternLabels,
            'patternFrequencies' => $this->frequencies($patternCounts, $workoutCount, 'pattern', $patternLabels),
            'teamSizeFrequencies' => $this->teamSizeFrequencies($teamSizeCounts, $workoutCount),
            'coOccurringPatternPairs' => $this->pairFrequencies($pairCounts, $workoutCount, $patternLabels),
            'examplesPerPattern' => $examples,
        ];
    }

    /**
     * @param array<string, int>    $counts
     * @param array<string, string> $labels
     *
     * @return list<array{pattern: string, label: string, workoutCount: int, percentage: float}>
     */
    private function frequencies(array $counts, int $workoutCount, string $keyName, array $labels): array
    {
        $frequencies = [];

        foreach ($counts as $key => $count) {
            $frequencies[] = [
                $keyName => $key,
                'label' => $labels[$key] ?? $key,
                'workoutCount' => $count,
                'percentage' => $this->percentage($count, $workoutCount),
            ];
        }

        usort($frequencies, static fn (array $left, array $right): int => $right['workoutCount'] <=> $left['workoutCount']);

        return $frequencies;
    }

    /**
     * @param array<string, int> $counts
     *
     * @return list<array{teamSize: string, workoutCount: int, percentage: float}>
     */
    private function teamSizeFrequencies(array $counts, int $workoutCount): array
    {
        $frequencies = [];

        foreach ($counts as $teamSize => $count) {
            $frequencies[] = [
                'teamSize' => $teamSize,
                'workoutCount' => $count,
                'percentage' => $this->percentage($count, $workoutCount),
            ];
        }

        usort($frequencies, static fn (array $left, array $right): int => $right['workoutCount'] <=> $left['workoutCount']);

        return $frequencies;
    }

    /**
     * @param array<string, int>    $pairCounts
     * @param array<string, string> $patternLabels
     *
     * @return list<array{patternA: string, patternB: string, labelA: string, labelB: string, workoutCount: int, percentage: float}>
     */
    private function pairFrequencies(array $pairCounts, int $workoutCount, array $patternLabels): array
    {
        $pairs = [];

        foreach ($pairCounts as $key => $count) {
            [$patternA, $patternB] = explode('|', $key, 2);
            $pairs[] = [
                'patternA' => $patternA,
                'patternB' => $patternB,
                'labelA' => $patternLabels[$patternA] ?? $patternA,
                'labelB' => $patternLabels[$patternB] ?? $patternB,
                'workoutCount' => $count,
                'percentage' => $this->percentage($count, $workoutCount),
            ];
        }

        usort($pairs, static fn (array $left, array $right): int => $right['workoutCount'] <=> $left['workoutCount']);

        return $pairs;
    }

    /**
     * @param array{id: string, name: string, flow: string, source: ?string, competitions: list<array{id: string, name: string}>, events: list<array{id: string, name: string}>} $workout
     *
     * @return array{id: string, name: string, source: ?string, competitions: list<string>, events: list<string>, flowExcerpt: string}
     */
    private function example(array $workout): array
    {
        return [
            'id' => $workout['id'],
            'name' => $workout['name'],
            'source' => $workout['source'],
            'competitions' => array_map(static fn (array $competition): string => $competition['name'], $workout['competitions']),
            'events' => array_map(static fn (array $event): string => $event['name'], $workout['events']),
            'flowExcerpt' => $this->flowExcerpt($workout['flow']),
        ];
    }

    private function flowExcerpt(string $flow): string
    {
        $flow = trim(preg_replace('/\s+/u', ' ', $flow) ?? $flow);

        if (mb_strlen($flow) <= 260) {
            return $flow;
        }

        return mb_substr($flow, 0, 257).'...';
    }

    private function percentage(int $count, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round($count / $total * 100, 2);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function formatFilters(array $filters): string
    {
        $parts = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            $parts[] = sprintf('%s=%s', $key, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        }

        return $parts === [] ? 'none' : implode(', ', $parts);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function normalizedStringOrNull(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return $value === null ? null : mb_strtolower($value);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
