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
    name: 'app:workouts:audit-generated-movement-frequencies',
    description: 'Audit movement and movement pair frequencies in AI-generated workouts.',
)]
final class AuditGeneratedWorkoutMovementFrequenciesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('stimulus', null, InputOption::VALUE_REQUIRED, 'Filter by workout generation stimulus, for example Competition.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Filter by workout type name, for example "For time" or AMRAP.')
            ->addOption('team', null, InputOption::VALUE_NONE, 'Keep only team workout generations.')
            ->addOption('individual', null, InputOption::VALUE_NONE, 'Keep only individual workout generations.')
            ->addOption('created-after', null, InputOption::VALUE_REQUIRED, 'Keep workouts generated at or after this date/time.')
            ->addOption('created-before', null, InputOption::VALUE_REQUIRED, 'Keep workouts generated before this date/time. A YYYY-MM-DD value includes the whole day.')
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
        $detectedWorkoutMovements = $this->structuredWorkoutMovements($whereSql, $parameters);
        $analyzedWorkoutCount = count($detectedWorkoutMovements);
        $summary['analyzedWorkoutCount'] = $analyzedWorkoutCount;
        $summary['unstructuredGeneratedWorkoutCount'] = max(
            0,
            $summary['generatedWorkoutCount'] - $summary['structuredWorkoutCount'],
        );

        $allMovements = $this->movementFrequencies($detectedWorkoutMovements, $analyzedWorkoutCount);
        $allPairs = $this->pairFrequencies($detectedWorkoutMovements, $analyzedWorkoutCount);
        $movements = array_slice($allMovements, 0, $limit);
        $pairs = array_slice($allPairs, 0, $pairLimit);

        $report = [
            'kind' => 'generated_workout_movement_frequency_audit_v1',
            'filters' => $filters,
            'summary' => $summary,
            'movements' => $movements,
            'pairs' => $pairs,
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $io->title('Generated workout movement frequency audit');
        $io->definitionList(
            ['Generated workouts' => $summary['generatedWorkoutCount']],
            ['Structured workouts' => $summary['structuredWorkoutCount']],
            ['Unstructured generated workouts' => $summary['unstructuredGeneratedWorkoutCount']],
            ['Analyzed workouts' => $summary['analyzedWorkoutCount']],
            ['Filters' => $this->formatFilters($filters)],
        );

        if ($analyzedWorkoutCount === 0) {
            $io->warning('No filtered generated workout has structured movements yet.');

            return Command::SUCCESS;
        }

        $io->section('Movement frequencies');
        $io->table(
            ['Movement', 'Type', 'Generated workouts', '% analyzed workouts'],
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
            ['Movement A', 'Movement B', 'Generated workouts', '% analyzed workouts'],
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

        $io->note('Percentages use only generated workouts with structured movement links as denominator.');

        return Command::SUCCESS;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: array<string, mixed>}
     */
    private function filters(InputInterface $input): array
    {
        if ((bool) $input->getOption('team') && (bool) $input->getOption('individual')) {
            throw new \InvalidArgumentException('Use either --team or --individual, not both.');
        }

        $createdAfter = $this->dateTimeOrNull($input->getOption('created-after'), false);
        $createdBefore = $this->dateTimeOrNull($input->getOption('created-before'), true);
        $filters = [
            'stimulus' => $this->normalizedStringOrNull($input->getOption('stimulus')),
            'format' => $this->normalizedStringOrNull($input->getOption('format')),
            'team' => (bool) $input->getOption('team') ? true : null,
            'individual' => (bool) $input->getOption('individual') ? true : null,
            'createdAfter' => $createdAfter?->format(\DateTimeInterface::ATOM),
            'createdBefore' => $this->stringOrNull($input->getOption('created-before')),
        ];
        $where = ['w.workout_generation_id IS NOT NULL'];
        $parameters = [];

        if ($filters['stimulus'] !== null) {
            $where[] = 'LOWER(COALESCE(wg.stimulus, \'\')) = :stimulus';
            $parameters['stimulus'] = $filters['stimulus'];
        }

        if ($filters['format'] !== null) {
            $where[] = 'LOWER(COALESCE(wt.name, \'\')) = :format';
            $parameters['format'] = $filters['format'];
        }

        if ($filters['team'] === true) {
            $where[] = 'wg.is_team_workout = TRUE';
        }

        if ($filters['individual'] === true) {
            $where[] = 'wg.is_team_workout = FALSE';
        }

        if ($createdAfter !== null) {
            $where[] = 'w.created_at >= :createdAfter';
            $parameters['createdAfter'] = $createdAfter->format('Y-m-d H:i:s');
        }

        if ($createdBefore !== null) {
            $where[] = 'w.created_at < :createdBefore';
            $parameters['createdBefore'] = $createdBefore->format('Y-m-d H:i:s');
        }

        return [$filters, implode("\n                    AND ", $where), $parameters];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{generatedWorkoutCount: int, structuredWorkoutCount: int}
     */
    private function summary(string $whereSql, array $parameters): array
    {
        $row = $this->connection->fetchAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        COUNT(DISTINCT w.id) AS generated_workout_count,
                        COUNT(DISTINCT w.id) FILTER (
                            WHERE EXISTS (
                                SELECT 1
                                FROM workout_movement wm
                                WHERE wm.workout_id = w.id
                            )
                        ) AS structured_workout_count
                    FROM workout w
                    INNER JOIN workout_generation wg ON wg.id = w.workout_generation_id
                    LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                    WHERE %s
                    SQL,
                $whereSql,
            ),
            $parameters,
        );

        return [
            'generatedWorkoutCount' => (int) ($row['generated_workout_count'] ?? 0),
            'structuredWorkoutCount' => (int) ($row['structured_workout_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, list<array{id: string, name: string, movementType: ?string}>>
     */
    private function structuredWorkoutMovements(string $whereSql, array $parameters): array
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    WITH base_workouts AS (
                        SELECT DISTINCT w.id
                        FROM workout w
                        INNER JOIN workout_generation wg ON wg.id = w.workout_generation_id
                        LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                        WHERE %s
                    )
                    SELECT
                        bw.id::TEXT AS workout_id,
                        m.id::TEXT AS movement_id,
                        m.name AS movement,
                        mt.name AS movement_type
                    FROM base_workouts bw
                    INNER JOIN workout_movement wm ON wm.workout_id = bw.id
                    INNER JOIN movement m ON m.id = wm.movement_id
                    LEFT JOIN movement_type mt ON mt.id = m.movement_type_id
                    ORDER BY bw.id::TEXT ASC, m.name ASC
                    SQL,
                $whereSql,
            ),
            $parameters,
        );

        return $this->groupDetectedRows($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, list<array{id: string, name: string, movementType: ?string}>>
     */
    private function groupDetectedRows(array $rows): array
    {
        $detected = [];

        foreach ($rows as $row) {
            $workoutId = (string) $row['workout_id'];
            $detected[$workoutId] ??= [];
            $detected[$workoutId][] = [
                'id' => (string) $row['movement_id'],
                'name' => (string) $row['movement'],
                'movementType' => $row['movement_type'] === null ? null : (string) $row['movement_type'],
            ];
        }

        return $detected;
    }

    /**
     * @param array<string, list<array{id: string, name: string, movementType: ?string}>> $detectedWorkoutMovements
     *
     * @return list<array{movement: string, movementType: ?string, workoutCount: int, percentage: float}>
     */
    private function movementFrequencies(array $detectedWorkoutMovements, int $analyzedWorkoutCount): array
    {
        $frequencies = [];

        foreach ($detectedWorkoutMovements as $movements) {
            foreach ($movements as $movement) {
                $frequencies[$movement['id']] ??= [
                    'movement' => $movement['name'],
                    'movementType' => $movement['movementType'],
                    'workoutCount' => 0,
                    'percentage' => 0.0,
                ];
                ++$frequencies[$movement['id']]['workoutCount'];
            }
        }

        foreach ($frequencies as &$frequency) {
            $frequency['percentage'] = $this->percentage($frequency['workoutCount'], $analyzedWorkoutCount);
        }
        unset($frequency);

        usort(
            $frequencies,
            static fn (array $left, array $right): int => [$right['workoutCount'], $left['movement']]
                <=> [$left['workoutCount'], $right['movement']],
        );

        return $frequencies;
    }

    /**
     * @param array<string, list<array{id: string, name: string, movementType: ?string}>> $detectedWorkoutMovements
     *
     * @return list<array{movementA: string, movementB: string, workoutCount: int, percentage: float}>
     */
    private function pairFrequencies(array $detectedWorkoutMovements, int $analyzedWorkoutCount): array
    {
        $frequencies = [];

        foreach ($detectedWorkoutMovements as $movements) {
            $movementCount = count($movements);

            for ($leftIndex = 0; $leftIndex < $movementCount; ++$leftIndex) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < $movementCount; ++$rightIndex) {
                    $left = $movements[$leftIndex];
                    $right = $movements[$rightIndex];
                    $key = $left['name'] < $right['name']
                        ? $left['id'].'|'.$right['id']
                        : $right['id'].'|'.$left['id'];
                    $movementA = $left['name'] < $right['name'] ? $left['name'] : $right['name'];
                    $movementB = $left['name'] < $right['name'] ? $right['name'] : $left['name'];
                    $frequencies[$key] ??= [
                        'movementA' => $movementA,
                        'movementB' => $movementB,
                        'workoutCount' => 0,
                        'percentage' => 0.0,
                    ];
                    ++$frequencies[$key]['workoutCount'];
                }
            }
        }

        foreach ($frequencies as &$frequency) {
            $frequency['percentage'] = $this->percentage($frequency['workoutCount'], $analyzedWorkoutCount);
        }
        unset($frequency);

        usort(
            $frequencies,
            static fn (array $left, array $right): int => [$right['workoutCount'], $left['movementA'], $left['movementB']]
                <=> [$left['workoutCount'], $right['movementA'], $right['movementB']],
        );

        return $frequencies;
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
            static fn (string $key, mixed $value): string => sprintf('%s=%s', $key, is_bool($value) ? ($value ? 'true' : 'false') : $value),
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

    private function dateTimeOrNull(mixed $value, bool $exclusiveEndOfDay): ?\DateTimeImmutable
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $dateTime = new \DateTimeImmutable($value);

        if ($exclusiveEndOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $dateTime->modify('+1 day');
        }

        return $dateTime;
    }
}
