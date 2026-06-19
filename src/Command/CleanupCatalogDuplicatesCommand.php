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
    name: 'app:catalogs:cleanup-duplicates',
    description: 'Remap and delete duplicate workout catalog rows. Dry-run by default.',
)]
final class CleanupCatalogDuplicatesCommand extends Command
{
    /**
     * @var list<array{
     *     label: string,
     *     table: string,
     *     keyExpression: string,
     * }>
     */
    private const CATALOGS = [
        [
            'label' => 'Body parts',
            'table' => 'body_part',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Movement difficulties',
            'table' => 'movement_difficulty',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Movement types',
            'table' => 'movement_type',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Workout movement generation types',
            'table' => 'workout_movement_generation_type',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Workout types',
            'table' => 'workout_type',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Workout origin names',
            'table' => 'workout_origin_name',
            'keyExpression' => 'LOWER(TRIM(name))',
        ],
        [
            'label' => 'Workout origins',
            'table' => 'workout_origin',
            'keyExpression' => 'CONCAT((SELECT LOWER(TRIM(won.name)) FROM workout_origin_name won WHERE won.id = name_id), '
                .'\'|\', COALESCE(year::TEXT, \'\'))',
        ],
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('catalog', null, InputOption::VALUE_REQUIRED, 'Restrict cleanup to one catalog table.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum duplicate groups per catalog. 0 means all.', 0)
            ->addOption('write', null, InputOption::VALUE_NONE, 'Persist remaps and deletions.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $catalogFilter = $this->stringOrNull($input->getOption('catalog'));
        $limit = max(0, (int) $input->getOption('limit'));
        $write = (bool) $input->getOption('write');
        $catalogs = $this->catalogs($catalogFilter);

        if ($catalogs === []) {
            $io->error(sprintf('Unknown catalog "%s".', $catalogFilter));

            return Command::FAILURE;
        }

        $runner = fn (): array => array_map(
            fn (array $catalog): array => $this->cleanupCatalog($catalog, $limit, $write),
            $catalogs,
        );
        $report = $write ? $this->connection->transactional($runner) : $runner();

        $io->title($write ? 'Workout catalog duplicate cleanup' : 'Workout catalog duplicate cleanup dry run');
        $io->table(
            ['Catalog', 'Groups', $write ? 'Remapped refs' : 'Would remap refs', $write ? 'Deleted rows' : 'Would delete rows', 'Skipped rows'],
            array_map(
                static fn (array $catalog): array => [
                    $catalog['label'],
                    $catalog['groupCount'],
                    $catalog['remappedReferenceCount'],
                    $catalog['deletedRowCount'],
                    $catalog['skippedRowCount'],
                ],
                $report,
            ),
        );

        foreach ($report as $catalog) {
            if ($catalog['actions'] === []) {
                continue;
            }

            $io->section($catalog['label']);
            $io->table(
                ['Key', 'Canonical ID', $write ? 'Remapped' : 'Would remap', $write ? 'Deleted' : 'Would delete', 'Skipped'],
                array_map(
                    static fn (array $action): array => [
                        $action['key'],
                        $action['canonicalId'],
                        $action['remappedReferenceCount'],
                        $action['deletedRowCount'],
                        $action['skippedRowCount'],
                    ],
                    array_slice($catalog['actions'], 0, 20),
                ),
            );
        }

        if (!$write) {
            $io->note('Dry run only. Re-run with --write to persist.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{label: string, table: string, keyExpression: string}>
     */
    private function catalogs(?string $catalogFilter): array
    {
        if ($catalogFilter === null) {
            return self::CATALOGS;
        }

        return array_values(array_filter(
            self::CATALOGS,
            static fn (array $catalog): bool => $catalog['table'] === $catalogFilter,
        ));
    }

    /**
     * @param array{label: string, table: string, keyExpression: string} $catalog
     *
     * @return array<string, mixed>
     */
    private function cleanupCatalog(array $catalog, int $limit, bool $write): array
    {
        $usageSources = $this->foreignKeyUsageSources($catalog['table']);
        $groupKeys = $this->duplicateGroupKeys($catalog, $limit, $usageSources);
        $actions = [];

        foreach ($groupKeys as $groupKey) {
            $actions[] = $this->cleanupGroup($catalog, $groupKey, $usageSources, $write);
        }

        return [
            'label' => $catalog['label'],
            'table' => $catalog['table'],
            'groupCount' => count($actions),
            'remappedReferenceCount' => array_sum(array_column($actions, 'remappedReferenceCount')),
            'deletedRowCount' => array_sum(array_column($actions, 'deletedRowCount')),
            'skippedRowCount' => array_sum(array_column($actions, 'skippedRowCount')),
            'usageSources' => array_map(
                static fn (array $usage): string => sprintf('%s.%s', $usage['table'], $usage['column']),
                $usageSources,
            ),
            'actions' => $actions,
        ];
    }

    /**
     * @param array{label: string, table: string, keyExpression: string} $catalog
     * @param list<array{table: string, column: string}>                 $usageSources
     *
     * @return list<string|null>
     */
    private function duplicateGroupKeys(array $catalog, int $limit, array $usageSources): array
    {
        $limitSql = $limit > 0 ? sprintf('LIMIT %d', $limit) : '';

        return array_map(
            static fn (mixed $key): ?string => $key === null ? null : (string) $key,
            $this->connection->fetchFirstColumn(sprintf(
                <<<'SQL'
                    SELECT duplicate_key
                    FROM (
                        SELECT
                            %s AS duplicate_key,
                            %s AS reference_count
                        FROM %s
                    ) catalog_rows
                    GROUP BY duplicate_key
                    HAVING COUNT(*) > 1
                    ORDER BY COALESCE(SUM(reference_count), 0) DESC, COUNT(*) DESC, duplicate_key ASC
                    %s
                    SQL,
                $catalog['keyExpression'],
                $this->usageExpression($usageSources),
                $this->identifier($catalog['table']),
                $limitSql,
            )),
        );
    }

    /**
     * @param array{label: string, table: string, keyExpression: string} $catalog
     * @param list<array{table: string, column: string}>                 $usageSources
     *
     * @return array<string, mixed>
     */
    private function cleanupGroup(array $catalog, ?string $groupKey, array $usageSources, bool $write): array
    {
        $rows = $this->duplicateRows($catalog, $groupKey, $usageSources);
        $canonical = $rows[0] ?? null;
        if ($canonical === null) {
            return [
                'key' => $groupKey ?? '',
                'canonicalId' => null,
                'remappedReferenceCount' => 0,
                'deletedRowCount' => 0,
                'skippedRowCount' => 0,
            ];
        }

        $remappedReferences = 0;
        $deletedRows = 0;
        $skippedRows = 0;

        foreach (array_slice($rows, 1) as $duplicate) {
            $duplicateId = (string) $duplicate['id'];
            $referenceCount = (int) $duplicate['reference_count'];
            $remappedReferences += $write
                ? $this->remapReferences($usageSources, $duplicateId, (string) $canonical['id'])
                : $referenceCount;

            if (!$write) {
                ++$deletedRows;
                continue;
            }

            if ($this->referenceCount($usageSources, $duplicateId) > 0) {
                ++$skippedRows;
                continue;
            }

            $deletedRows += $this->deleteRow($catalog['table'], $duplicateId);
        }

        return [
            'key' => $groupKey ?? '',
            'canonicalId' => (string) $canonical['id'],
            'remappedReferenceCount' => $remappedReferences,
            'deletedRowCount' => $deletedRows,
            'skippedRowCount' => $skippedRows,
        ];
    }

    /**
     * @param array{label: string, table: string, keyExpression: string} $catalog
     * @param list<array{table: string, column: string}>                 $usageSources
     *
     * @return list<array{id: string, reference_count: int}>
     */
    private function duplicateRows(array $catalog, ?string $groupKey, array $usageSources): array
    {
        /** @var list<array{id: string, reference_count: int}> $rows */
        $rows = $this->connection->fetchAllAssociative(sprintf(
            <<<'SQL'
                SELECT id::TEXT AS id, reference_count
                FROM (
                    SELECT
                        id,
                        %s AS duplicate_key,
                        %s AS reference_count
                    FROM %s
                ) catalog_rows
                WHERE duplicate_key IS NOT DISTINCT FROM :duplicateKey
                ORDER BY reference_count DESC, id ASC
                SQL,
            $catalog['keyExpression'],
            $this->usageExpression($usageSources),
            $this->identifier($catalog['table']),
        ), ['duplicateKey' => $groupKey]);

        return $rows;
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private function foreignKeyUsageSources(string $referencedTable): array
    {
        /** @var list<array{table_name: string, column_name: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT kcu.table_name, kcu.column_name
                FROM information_schema.table_constraints tc
                INNER JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                INNER JOIN information_schema.constraint_column_usage ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = 'public'
                    AND ccu.table_schema = 'public'
                    AND ccu.table_name = :referencedTable
                    AND ccu.column_name = 'id'
                ORDER BY kcu.table_name, kcu.column_name
                SQL,
            ['referencedTable' => $referencedTable],
        );

        return array_values(array_map(
            fn (array $row): array => [
                'table' => $this->identifier($row['table_name']),
                'column' => $this->identifier($row['column_name']),
            ],
            $rows,
        ));
    }

    /**
     * @param list<array{table: string, column: string}> $usageSources
     */
    private function remapReferences(array $usageSources, string $duplicateId, string $canonicalId): int
    {
        $updated = 0;
        foreach ($usageSources as $usage) {
            $updated += $this->connection->executeStatement(sprintf(
                'UPDATE %s SET %s = :canonicalId WHERE %s = :duplicateId',
                $this->identifier($usage['table']),
                $this->identifier($usage['column']),
                $this->identifier($usage['column']),
            ), [
                'canonicalId' => $canonicalId,
                'duplicateId' => $duplicateId,
            ]);
        }

        return $updated;
    }

    /**
     * @param list<array{table: string, column: string}> $usageSources
     */
    private function referenceCount(array $usageSources, string $id): int
    {
        $count = 0;
        foreach ($usageSources as $usage) {
            $count += (int) $this->connection->fetchOne(sprintf(
                'SELECT COUNT(*) FROM %s WHERE %s = :id',
                $this->identifier($usage['table']),
                $this->identifier($usage['column']),
            ), ['id' => $id]);
        }

        return $count;
    }

    private function deleteRow(string $table, string $id): int
    {
        return $this->connection->executeStatement(sprintf(
            'DELETE FROM %s WHERE id = :id',
            $this->identifier($table),
        ), ['id' => $id]);
    }

    /**
     * @param list<array{table: string, column: string}> $usageSources
     */
    private function usageExpression(array $usageSources): string
    {
        if ($usageSources === []) {
            return '0';
        }

        return implode(
            ' + ',
            array_map(
                fn (array $usage): string => sprintf(
                    '(SELECT COUNT(*) FROM %s usage_table WHERE usage_table.%s = id)',
                    $this->identifier($usage['table']),
                    $this->identifier($usage['column']),
                ),
                $usageSources,
            ),
        );
    }

    private function identifier(string $identifier): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException(sprintf('Unsafe SQL identifier "%s".', $identifier));
        }

        return $identifier;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
