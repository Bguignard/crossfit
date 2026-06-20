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
            ->addOption('detection', null, InputOption::VALUE_REQUIRED, 'Movement detection mode: auto, structured or flow.', 'auto')
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
        $detection = $this->detectionMode($input, (int) $summary['structuredWorkoutCount']);
        $detectedWorkoutMovements = $detection === 'flow'
            ? $this->flowDetectedWorkoutMovements($whereSql, $parameters)
            : $this->structuredWorkoutMovements($whereSql, $parameters);
        $analyzedWorkoutCount = count($detectedWorkoutMovements);
        $summary['flowMatchedWorkoutCount'] = $detection === 'flow' ? $analyzedWorkoutCount : null;
        $summary['analyzedWorkoutCount'] = $analyzedWorkoutCount;
        $movements = $this->movementFrequencies($detectedWorkoutMovements, $analyzedWorkoutCount, $limit);
        $pairs = $this->pairFrequencies($detectedWorkoutMovements, $analyzedWorkoutCount, $pairLimit);

        $report = [
            'kind' => 'competition_movement_frequency_audit_v1',
            'filters' => $filters,
            'detection' => $detection,
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
            ['Flow-matched workouts' => $summary['flowMatchedWorkoutCount'] ?? '-'],
            ['Analyzed workouts' => $summary['analyzedWorkoutCount']],
            ['Detection' => $detection],
            ['Filters' => $this->formatFilters($filters)],
        );

        if ($analyzedWorkoutCount === 0) {
            $io->warning('No filtered competition workout has detectable movements yet.');

            return Command::SUCCESS;
        }

        $io->section('Movement frequencies');
        $io->table(
            ['Movement', 'Type', 'Workouts', '% analyzed workouts'],
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
            ['Movement A', 'Movement B', 'Workouts', '% analyzed workouts'],
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

        $io->note('Percentages use only analyzed competition workouts as denominator. In flow mode, movement detection is approximate and should guide, not replace, later structured enrichment.');

        return Command::SUCCESS;
    }

    private function detectionMode(InputInterface $input, int $structuredWorkoutCount): string
    {
        $detection = $this->normalizedStringOrNull($input->getOption('detection')) ?? 'auto';

        if (!in_array($detection, ['auto', 'structured', 'flow'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported detection mode "%s". Use auto, structured or flow.', $detection));
        }

        if ($detection === 'auto') {
            return $structuredWorkoutCount > 0 ? 'structured' : 'flow';
        }

        return $detection;
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
     * @return array<string, list<array{id: string, name: string, movementType: ?string}>>
     */
    private function structuredWorkoutMovements(string $whereSql, array $parameters): array
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
     * @param array<string, mixed> $parameters
     *
     * @return array<string, list<array{id: string, name: string, movementType: ?string}>>
     */
    private function flowDetectedWorkoutMovements(string $whereSql, array $parameters): array
    {
        $catalog = $this->movementCatalog();
        $workouts = $this->connection->fetchAllAssociative(
            sprintf(
                <<<'SQL'
                    SELECT
                        w.id::TEXT AS workout_id,
                        w.flow
                    FROM competition_event ce
                    INNER JOIN competition c ON c.id = ce.competition_id
                    INNER JOIN workout w ON w.id = ce.workout_id
                    LEFT JOIN workout_type wt ON wt.id = w.workout_type_id
                    WHERE %s
                    ORDER BY w.id::TEXT ASC
                    SQL,
                $whereSql,
            ),
            $parameters,
        );
        $detected = [];

        foreach ($workouts as $workout) {
            $movements = $this->detectMovementsInFlow($this->prescriptionTextForDetection((string) $workout['flow']), $catalog);

            if ($movements === []) {
                continue;
            }

            $detected[(string) $workout['workout_id']] = $movements;
        }

        return $detected;
    }

    /**
     * @return list<array{id: string, name: string, movementType: ?string, aliases: list<string>}>
     */
    private function movementCatalog(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    m.id::TEXT AS movement_id,
                    m.name AS movement,
                    mt.name AS movement_type
                FROM movement m
                LEFT JOIN movement_type mt ON mt.id = m.movement_type_id
                ORDER BY LENGTH(m.name) DESC, m.name ASC
                SQL,
        );

        $catalog = array_map(
            fn (array $row): array => [
                'id' => (string) $row['movement_id'],
                'name' => (string) $row['movement'],
                'movementType' => $row['movement_type'] === null ? null : (string) $row['movement_type'],
                'aliases' => $this->movementAliases((string) $row['movement']),
            ],
            $rows,
        );

        usort(
            $catalog,
            static fn (array $left, array $right): int => max(array_map('strlen', $right['aliases']))
                <=> max(array_map('strlen', $left['aliases'])),
        );

        return $catalog;
    }

    /**
     * @return list<string>
     */
    private function movementAliases(string $movement): array
    {
        $normalized = $this->normalizeSearchText($movement);
        $aliases = [$normalized, ...$this->commonMovementAliases($normalized)];

        foreach ($aliases as $alias) {
            $plural = $this->pluralAlias($alias);

            if ($plural !== null) {
                $aliases[] = $plural;
            }
        }

        return array_values(array_unique(array_filter($aliases)));
    }

    private function prescriptionTextForDetection(string $flow): string
    {
        $lines = preg_split('/\R/', $flow) ?: [$flow];
        $keptLines = [];

        foreach ($lines as $line) {
            $normalizedLine = $this->normalizeSearchText($line);

            if ($normalizedLine !== '' && preg_match(
                '/^(scaling|scaling options|scale|scaled|adaptation|adaptations|strategy|strategies|notes?|standards?|scorecards?|tiebreak|tie break|rx|intermediate|beginner|debutant|intermediaire|option|options)\b/',
                $normalizedLine,
            )) {
                break;
            }

            $keptLines[] = $line;
        }

        return trim(implode("\n", $keptLines));
    }

    /**
     * @return list<string>
     */
    private function commonMovementAliases(string $normalizedMovement): array
    {
        return match ($normalizedMovement) {
            'wall ball shot' => ['wall ball', 'wall balls', 'wallball', 'wallballs'],
            'chest to bar pull up' => ['chest to bar', 'chest to bars', 'c2b', 'ctb', 'chest2bar'],
            'toes to bar' => ['toe to bar', 't2b', 'ttb', 'toes2bar'],
            'handstand push up' => ['hspu', 'handstand pushup', 'handstand push ups', 'handstand pushups'],
            'double under' => ['double unders', 'du', 'dus', 'dubs'],
            'single under' => ['single unders', 'su', 'sus'],
            'muscle up' => ['muscle ups', 'mu', 'muscleup', 'muscleups'],
            'bar muscle up' => ['bar muscle ups', 'bmu', 'bar mu', 'bar mus'],
            'ring muscle up' => ['ring muscle ups', 'rmu', 'ring mu', 'ring mus'],
            'clean and jerk' => ['clean jerk', 'clean jerks', 'clean and jerks', 'c j'],
            'shoulder to overhead' => ['shoulder overhead', 's2oh', 'sto', 'stoh'],
            'burpee box jump over' => ['bbjo', 'burpee box jump overs'],
            'box jump over' => ['box jump overs', 'bjo'],
            'ski erg' => ['skierg', 'ski'],
            'bike erg' => ['bikeerg'],
            'assault bike' => ['air bike'],
            'echo bike' => ['rogue echo bike'],
            'ghd sit up' => ['ghd situp', 'ghd sit ups', 'ghd situps'],
            default => [],
        };
    }

    private function pluralAlias(string $alias): ?string
    {
        if (strlen($alias) < 4 || str_ends_with($alias, 's')) {
            return null;
        }

        if (str_ends_with($alias, 'y')) {
            return substr($alias, 0, -1).'ies';
        }

        return $alias.'s';
    }

    /**
     * @param list<array{id: string, name: string, movementType: ?string, aliases: list<string>}> $catalog
     *
     * @return list<array{id: string, name: string, movementType: ?string}>
     */
    private function detectMovementsInFlow(string $flow, array $catalog): array
    {
        $normalizedFlow = ' '.$this->normalizeSearchText($flow).' ';
        $compactFlow = str_replace(' ', '', $normalizedFlow);
        $occupiedSpans = [];
        $detected = [];

        foreach ($catalog as $movement) {
            foreach ($movement['aliases'] as $alias) {
                $haystack = str_contains($alias, ' ') ? $normalizedFlow : $compactFlow;
                $pattern = '/(?<![a-z0-9])'.preg_quote($alias, '/').'(?![a-z0-9])/';

                if (!preg_match_all($pattern, $haystack, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                foreach ($matches[0] as $match) {
                    $start = (int) $match[1];
                    $end = $start + strlen((string) $match[0]);

                    if ($this->overlapsAnySpan($start, $end, $occupiedSpans)) {
                        continue;
                    }

                    $occupiedSpans[] = [$start, $end];
                    $detected[$movement['id']] = [
                        'id' => $movement['id'],
                        'name' => $movement['name'],
                        'movementType' => $movement['movementType'],
                    ];

                    break 2;
                }
            }
        }

        $detected = $this->suppressGenericDetectedMovements($detected);
        uasort($detected, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return array_values($detected);
    }

    /**
     * @param array<string, array{id: string, name: string, movementType: ?string}> $detected
     *
     * @return array<string, array{id: string, name: string, movementType: ?string}>
     */
    private function suppressGenericDetectedMovements(array $detected): array
    {
        $detectedByName = [];

        foreach ($detected as $movementId => $movement) {
            $detectedByName[$movement['name']] = $movementId;
        }

        foreach ($this->genericMovementSuppressions() as $specificMovement => $genericMovements) {
            if (!isset($detectedByName[$specificMovement])) {
                continue;
            }

            foreach ($genericMovements as $genericMovement) {
                if (!isset($detectedByName[$genericMovement])) {
                    continue;
                }

                unset($detected[$detectedByName[$genericMovement]]);
            }
        }

        return $detected;
    }

    /**
     * @return array<string, list<string>>
     */
    private function genericMovementSuppressions(): array
    {
        return [
            'Bar Muscle Up' => ['Muscle Up', 'Pull Up'],
            'Burpee Box Jump Over' => ['Burpee Box Jump', 'Box Jump Over', 'Box Jump', 'Burpee Over'],
            'Burpee Box Jump' => ['Box Jump'],
            'Chest to Bar Pull Up' => ['Pull Up'],
            'Double Under' => ['Single Under'],
            'Handstand Push Up' => ['Push Up'],
            'Hang Power Clean' => ['Hang Clean', 'Power Clean'],
            'Hang Power Snatch' => ['Hang Snatch', 'Power Snatch'],
            'Ring Muscle Up' => ['Muscle Up', 'Pull Up'],
            'Wall Walk' => ['Handstand Walk'],
        ];
    }

    /**
     * @param list<array{0: int, 1: int}> $spans
     */
    private function overlapsAnySpan(int $start, int $end, array $spans): bool
    {
        foreach ($spans as [$spanStart, $spanEnd]) {
            if ($start < $spanEnd && $end > $spanStart) {
                return true;
            }
        }

        return false;
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
    private function movementFrequencies(array $detectedWorkoutMovements, int $analyzedWorkoutCount, int $limit): array
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

        return array_slice($frequencies, 0, $limit);
    }

    /**
     * @param array<string, list<array{id: string, name: string, movementType: ?string}>> $detectedWorkoutMovements
     *
     * @return list<array{movementA: string, movementB: string, workoutCount: int, percentage: float}>
     */
    private function pairFrequencies(array $detectedWorkoutMovements, int $analyzedWorkoutCount, int $limit): array
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

        return array_slice($frequencies, 0, $limit);
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

    private function normalizeSearchText(string $value): string
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value) ?? $value;
        $value = str_replace(['’', '\''], '', $value);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
