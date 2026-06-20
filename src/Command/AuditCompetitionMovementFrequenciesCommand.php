<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:audit-competition-movement-frequencies',
    description: 'Audit movement and movement pair frequencies in imported competition workouts.',
)]
final class AuditCompetitionMovementFrequenciesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Filter by competition source_name.')
            ->addOption('participation-type', null, InputOption::VALUE_REQUIRED, 'Filter by competition participation_type, for example individual or team.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Filter by workout type name, for example "For time" or AMRAP.')
            ->addOption('time-cap-min', null, InputOption::VALUE_REQUIRED, 'Filter workouts with a time cap greater than or equal to this value.')
            ->addOption('time-cap-max', null, InputOption::VALUE_REQUIRED, 'Filter workouts with a time cap lower than or equal to this value.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum movements to display.', 30)
            ->addOption('pair-limit', null, InputOption::VALUE_REQUIRED, 'Maximum movement pairs to display.', 30)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output the report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $pairLimit = max(1, (int) $input->getOption('pair-limit'));
        [$filters, $whereSql, $parameters] = $this->filters($input);

        $summary = $this->summary($whereSql, $parameters);
        $structuredWorkoutCount = max(0, (int) $summary['structuredWorkoutCount']);
        $movements = $this->movementFrequencies($whereSql, $parameters, $structuredWorkoutCount, $limit);
        $pairs = $this->pairFrequencies($whereSql, $parameters, $structuredWorkoutCount, $pairLimit);

        $report = [
            'kind' => 'competition_movement_frequency_audit_v1',
            'filters' => $filters,
            'summary' => $summary,
            'movements' => $movements,
            'pairs' => $pairs,
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $io->title('Competition movement frequency audit');
        $io->definitionList(
            ['Competitions' => $summary['competitionCount']],
            ['Events' => $summary['eventCount']],
            ['Workouts' => $summary['workoutCount']],
            ['Structured workouts' => $summary['structuredWorkoutCount']],
            ['Filters' => $this->formatFilters($filters)],
        );

        if ($structuredWorkoutCount === 0) {
            $io->warning('No filtered competition workout has structured movements yet.');

            return Command::SUCCESS;
        }

        $io->section('Movement frequencies');
        $io->table(
            ['Movement', 'Type', 'Workouts', '% structured workouts'],
            array_map(
                static fn (array $movement): array => [
                    $movement['movement'],
                    $movement['movementType'] ?? '-',
                    $movement['workoutCount'],
                    sprintf('%.2f%%', $movement['percentage']),
                ],
                $movements,
            ),
        );

        $io->section('Movement pair frequencies');
        $io->table(
            ['Movement A', 'Movement B', 'Workouts', '% structured workouts'],
            array_map(
                static fn (array $pair): array => [
                    $pair['movementA'],
                    $pair['movementB'],
                    $pair['workoutCount'],
                    sprintf('%.2f%%', $pair['percentage']),
                ],
                $pairs,
            ),
        );

        $io->note('Percentages use only competition workouts with structured movement links as denominator.');

        return Command::SUCCESS;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}
     */
    private function filters(InputInterface $input): array
    {
        $filters = [
            'source' => $this->stringOrNull($input->getOption('source')),
            'participationType' => $this->normalizedStringOrNull($input->getOption('participation-type')),
            'format' => $this->normalizedStringOrNull($input->getOption('format')),
            'timeCapMin' => $this->intOrNull($input->getOption('time-cap-min')),
            'timeCapMax' => $this->intOrNull($input->getOption('time-cap-max')),
        ];
        $where = ['ce.workout_id IS NOT NULL'];
        $parameters = [];

        if ($filters['source'] !== null) {
            $where[] = 'c.source_name = :source';
            $parameters['source'] = $filters['source'];
        }

        if ($filters['participationType'] !== null) {
            $where[] = 'LOWER(COALESCE(c.participation_type, \'\')) = :participationType';
            $parameters['participationType'] = $filters['participationType'];
        }

        if ($filters['format'] !== null) {
            $where[] = 'LOWER(COALESCE(wt.name, \'\')) = :format';
            $parameters['format'] = $filters['format'];
        }

        if ($filters['timeCapMin'] !== null) {
            $where[] = 'w.time_cap >= :timeCapMin';
            $parameters['timeCapMin'] = $filters['timeCapMin'];
        }

        if ($filters['timeCapMax'] !== null) {
            $where[] = 'w.time_cap <= :timeCapMax';
            $parameters['timeCapMax'] = $filters['timeCapMax'];
        }

        return [$filters, implode("\n                    AND ", $where), $parameters];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{competitionCount: int, eventCount: int, workoutCount: int, structuredWorkoutCount: int}
     */
    private function summary(string $whereSql, array $parameters): array
    {
        $row = $this->connection->fetchAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        COUNT(DISTINCT c.id) AS competition_count,
                        COUNT(DISTINCT ce.id) AS event_count,
                        COUNT(DISTINCT w.id) AS workout_count,
                        COUNT(DISTINCT w.id) FILTER (
                            WHERE EXISTS (
                                SELECT 1
                                FROM workout_movement wm
                                WHERE wm.workout_id = w.id
                            )
                        ) AS structured_workout_count
                    FROM competition_event ce
                    INNER JOIN competition c ON c.id = ce.competition_id
                    INNER JOIN workout w ON w.id = ce.workout_id
                    LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                    WHERE %s
                    SQL,
                $whereSql,
            ),
            $parameters,
        );

        return [
            'competitionCount' => (int) ($row['competition_count'] ?? 0),
            'eventCount' => (int) ($row['event_count'] ?? 0),
            'workoutCount' => (int) ($row['workout_count'] ?? 0),
            'structuredWorkoutCount' => (int) ($row['structured_workout_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<array{movement: string, movementType: ?string, workoutCount: int, percentage: float}>
     */
    private function movementFrequencies(string $whereSql, array $parameters, int $structuredWorkoutCount, int $limit): array
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    WITH base_workouts AS (
                        SELECT DISTINCT w.id
                        FROM competition_event ce
                        INNER JOIN competition c ON c.id = ce.competition_id
                        INNER JOIN workout w ON w.id = ce.workout_id
                        LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                        WHERE %s
                    )
                    SELECT
                        m.name AS movement,
                        mt.name AS movement_type,
                        COUNT(DISTINCT bw.id) AS workout_count
                    FROM base_workouts bw
                    INNER JOIN workout_movement wm ON wm.workout_id = bw.id
                    INNER JOIN movement m ON m.id = wm.movement_id
                    LEFT JOIN movement_type mt ON mt.id = m.movement_type_id
                    GROUP BY m.id, m.name, mt.name
                    ORDER BY COUNT(DISTINCT bw.id) DESC, m.name ASC
                    LIMIT %d
                    SQL,
                $whereSql,
                $limit,
            ),
            $parameters,
        );

        return array_map(
            fn (array $row): array => [
                'movement' => (string) $row['movement'],
                'movementType' => $row['movement_type'] === null ? null : (string) $row['movement_type'],
                'workoutCount' => (int) $row['workout_count'],
                'percentage' => $this->percentage((int) $row['workout_count'], $structuredWorkoutCount),
            ],
            $rows,
        );
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<array{movementA: string, movementB: string, workoutCount: int, percentage: float}>
     */
    private function pairFrequencies(string $whereSql, array $parameters, int $structuredWorkoutCount, int $limit): array
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    WITH base_workouts AS (
                        SELECT DISTINCT w.id
                        FROM competition_event ce
                        INNER JOIN competition c ON c.id = ce.competition_id
                        INNER JOIN workout w ON w.id = ce.workout_id
                        LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                        WHERE %s
                    )
                    SELECT
                        m1.name AS movement_a,
                        m2.name AS movement_b,
                        COUNT(DISTINCT bw.id) AS workout_count
                    FROM base_workouts bw
                    INNER JOIN workout_movement wm1 ON wm1.workout_id = bw.id
                    INNER JOIN workout_movement wm2 ON wm2.workout_id = bw.id
                        AND wm1.movement_id::TEXT < wm2.movement_id::TEXT
                    INNER JOIN movement m1 ON m1.id = wm1.movement_id
                    INNER JOIN movement m2 ON m2.id = wm2.movement_id
                    GROUP BY m1.id, m1.name, m2.id, m2.name
                    ORDER BY COUNT(DISTINCT bw.id) DESC, m1.name ASC, m2.name ASC
                    LIMIT %d
                    SQL,
                $whereSql,
                $limit,
            ),
            $parameters,
        );

        return array_map(
            fn (array $row): array => [
                'movementA' => (string) $row['movement_a'],
                'movementB' => (string) $row['movement_b'],
                'workoutCount' => (int) $row['workout_count'],
                'percentage' => $this->percentage((int) $row['workout_count'], $structuredWorkoutCount),
            ],
            $rows,
        );
    }

    private function percentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 2);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function formatFilters(array $filters): string
    {
        $activeFilters = array_filter($filters, static fn (mixed $value): bool => $value !== null);

        if ($activeFilters === []) {
            return 'none';
        }

        return implode(', ', array_map(
            static fn (string $key, mixed $value): string => sprintf('%s=%s', $key, $value),
            array_keys($activeFilters),
            $activeFilters,
        ));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
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
