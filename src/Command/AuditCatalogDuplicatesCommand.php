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
    name: 'app:catalogs:audit-duplicates',
    description: 'Audit duplicate workout catalog rows without mutating data.',
)]
final class AuditCatalogDuplicatesCommand extends Command
{
    /**
     * @var list<array{
     *     label: string,
     *     table: string,
     *     keyExpression: string,
     *     usages: list<array{table: string, column: string}>,
     * }>
     */
    private const CATALOGS = [
        [
            'label' => 'Body parts',
            'table' => 'body_part',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [
                ['table' => 'muscle', 'column' => 'body_part_id'],
                ['table' => 'movement_body_part', 'column' => 'body_part_id'],
                ['table' => 'workout_generation_body_part', 'column' => 'body_part_id'],
            ],
        ],
        [
            'label' => 'Movement difficulties',
            'table' => 'movement_difficulty',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [
                ['table' => 'movement', 'column' => 'difficulty_id'],
                ['table' => 'workout_generation', 'column' => 'movement_difficulty_id'],
            ],
        ],
        [
            'label' => 'Movement types',
            'table' => 'movement_type',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [
                ['table' => 'movement', 'column' => 'movement_type_id'],
                ['table' => 'workout_generation_movement_type', 'column' => 'movement_type_id'],
            ],
        ],
        [
            'label' => 'Workout movement generation types',
            'table' => 'workout_movement_generation_type',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [],
        ],
        [
            'label' => 'Workout types',
            'table' => 'workout_type',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [
                ['table' => 'workout', 'column' => 'workout_type_id'],
                ['table' => 'workout_generation', 'column' => 'workout_type_id'],
            ],
        ],
        [
            'label' => 'Workout origin names',
            'table' => 'workout_origin_name',
            'keyExpression' => 'LOWER(TRIM(name))',
            'usages' => [
                ['table' => 'workout_origin', 'column' => 'name_id'],
            ],
        ],
        [
            'label' => 'Workout origins',
            'table' => 'workout_origin',
            'keyExpression' => 'CONCAT((SELECT LOWER(TRIM(won.name)) FROM workout_origin_name won WHERE won.id = name_id), '
                .'\'|\', COALESCE(year::TEXT, \'\'))',
            'usages' => [
                ['table' => 'workout', 'column' => 'workout_origin_id'],
            ],
        ],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum duplicate groups to display per catalog.', 20)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output the audit report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $report = array_map(fn (array $catalog): array => $this->auditCatalog($catalog, $limit), self::CATALOGS);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $io->title('Workout catalog duplicate audit');
        $io->table(
            ['Catalog', 'Rows', 'Duplicate groups', 'Duplicate rows', 'Referenced duplicate rows', 'References'],
            array_map(
                static fn (array $catalog): array => [
                    $catalog['label'],
                    $catalog['rowCount'],
                    $catalog['duplicateGroupCount'],
                    $catalog['duplicateRowCount'],
                    $catalog['referencedDuplicateRowCount'],
                    $catalog['duplicateReferenceCount'],
                ],
                $report,
            ),
        );

        foreach ($report as $catalog) {
            if ($catalog['groups'] === []) {
                continue;
            }

            $io->section($catalog['label']);
            $io->table(
                ['Key', 'Rows', 'Referenced rows', 'References', 'Sample IDs'],
                array_map(
                    static fn (array $group): array => [
                        $group['key'],
                        $group['rowCount'],
                        $group['referencedRowCount'],
                        $group['referenceCount'],
                        implode(', ', $group['sampleIds']),
                    ],
                    $catalog['groups'],
                ),
            );
        }

        $io->note('Audit only: no row is remapped or deleted by this command.');

        return Command::SUCCESS;
    }

    /**
     * @param array{
     *     label: string,
     *     table: string,
     *     keyExpression: string,
     *     usages: list<array{table: string, column: string}>,
     * } $catalog
     *
     * @return array<string, mixed>
     */
    private function auditCatalog(array $catalog, int $limit): array
    {
        $rowCount = (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $catalog['table']));
        $existingUsages = $this->existingUsages($catalog['usages']);
        $usageExpression = $this->usageExpression($existingUsages);
        $duplicateGroups = $this->connection->fetchAllAssociative(sprintf(
            <<<'SQL'
                SELECT
                    duplicate_key,
                    COUNT(*) AS row_count,
                    COUNT(*) FILTER (WHERE reference_count > 0) AS referenced_row_count,
                    COALESCE(SUM(reference_count), 0) AS reference_count,
                    STRING_AGG(id::TEXT, ',' ORDER BY reference_count DESC, id::TEXT) AS sample_ids
                FROM (
                    SELECT
                        id,
                        %s AS duplicate_key,
                        %s AS reference_count
                    FROM %s
                ) catalog_rows
                GROUP BY duplicate_key
                HAVING COUNT(*) > 1
                ORDER BY COALESCE(SUM(reference_count), 0) DESC, COUNT(*) DESC, duplicate_key ASC
                LIMIT %d
                SQL,
            $catalog['keyExpression'],
            $usageExpression,
            $catalog['table'],
            $limit,
        ));

        $totals = $this->connection->fetchAssociative(sprintf(
            <<<'SQL'
                SELECT
                    COUNT(*) AS duplicate_group_count,
                    COALESCE(SUM(row_count), 0) AS duplicate_row_count,
                    COALESCE(SUM(referenced_row_count), 0) AS referenced_duplicate_row_count,
                    COALESCE(SUM(reference_count), 0) AS duplicate_reference_count
                FROM (
                    SELECT
                        duplicate_key,
                        COUNT(*) AS row_count,
                        COUNT(*) FILTER (WHERE reference_count > 0) AS referenced_row_count,
                        COALESCE(SUM(reference_count), 0) AS reference_count
                    FROM (
                        SELECT
                            id,
                            %s AS duplicate_key,
                            %s AS reference_count
                        FROM %s
                    ) catalog_rows
                    GROUP BY duplicate_key
                    HAVING COUNT(*) > 1
                ) duplicate_groups
                SQL,
            $catalog['keyExpression'],
            $usageExpression,
            $catalog['table'],
        )) ?: [];

        return [
            'label' => $catalog['label'],
            'table' => $catalog['table'],
            'rowCount' => $rowCount,
            'duplicateGroupCount' => (int) ($totals['duplicate_group_count'] ?? 0),
            'duplicateRowCount' => (int) ($totals['duplicate_row_count'] ?? 0),
            'referencedDuplicateRowCount' => (int) ($totals['referenced_duplicate_row_count'] ?? 0),
            'duplicateReferenceCount' => (int) ($totals['duplicate_reference_count'] ?? 0),
            'usageSources' => array_map(
                static fn (array $usage): string => sprintf('%s.%s', $usage['table'], $usage['column']),
                $existingUsages,
            ),
            'skippedUsageSources' => array_map(
                static fn (array $usage): string => sprintf('%s.%s', $usage['table'], $usage['column']),
                array_values(array_filter(
                    $catalog['usages'],
                    fn (array $usage): bool => !$this->tableColumnExists($usage['table'], $usage['column']),
                )),
            ),
            'groups' => array_map(
                static fn (array $group): array => [
                    'key' => (string) $group['duplicate_key'],
                    'rowCount' => (int) $group['row_count'],
                    'referencedRowCount' => (int) $group['referenced_row_count'],
                    'referenceCount' => (int) $group['reference_count'],
                    'sampleIds' => array_slice(explode(',', (string) $group['sample_ids']), 0, 6),
                ],
                $duplicateGroups,
            ),
        ];
    }

    /**
     * @param list<array{table: string, column: string}> $usages
     *
     * @return list<array{table: string, column: string}>
     */
    private function existingUsages(array $usages): array
    {
        return array_values(array_filter(
            $usages,
            fn (array $usage): bool => $this->tableColumnExists($usage['table'], $usage['column']),
        ));
    }

    private function tableColumnExists(string $table, string $column): bool
    {
        return (int) $this->connection->fetchOne(
            <<<'SQL'
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = 'public'
                    AND table_name = :table
                    AND column_name = :column
                SQL,
            [
                'table' => $table,
                'column' => $column,
            ],
        ) > 0;
    }

    /**
     * @param list<array{table: string, column: string}> $usages
     */
    private function usageExpression(array $usages): string
    {
        if ($usages === []) {
            return '0';
        }

        return implode(
            ' + ',
            array_map(
                static fn (array $usage): string => sprintf(
                    '(SELECT COUNT(*) FROM %s usage_table WHERE usage_table.%s = id)',
                    $usage['table'],
                    $usage['column'],
                ),
                $usages,
            ),
        );
    }
}
