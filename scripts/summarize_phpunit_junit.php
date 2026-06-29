<?php

declare(strict_types=1);

namespace MonWod\Tooling\PhpUnitJUnitSummary;

/**
 * @return array<string, mixed>
 */
function summarizeFile(string $path, int $limit = 15): array
{
    if (!is_file($path)) {
        throw new \InvalidArgumentException(sprintf('JUnit report "%s" does not exist.', $path));
    }

    $document = new \DOMDocument();
    $previousUseInternalErrors = libxml_use_internal_errors(true);
    $loaded = $document->load($path);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    if (!$loaded) {
        $message = $errors === [] ? 'unknown XML error' : trim($errors[0]->message);

        throw new \InvalidArgumentException(sprintf('Unable to read JUnit report "%s": %s.', $path, $message));
    }

    $testCases = [];
    $classes = [];
    $totals = [
        'tests' => 0,
        'assertions' => 0,
        'failures' => 0,
        'errors' => 0,
        'skipped' => 0,
        'time' => 0.0,
    ];

    foreach ($document->getElementsByTagName('testcase') as $testCase) {
        if (!$testCase instanceof \DOMElement) {
            continue;
        }

        $className = $testCase->getAttribute('class') !== '' ? $testCase->getAttribute('class') : '(unknown class)';
        $testName = $testCase->getAttribute('name') !== '' ? $testCase->getAttribute('name') : '(unknown test)';
        $time = (float) ($testCase->getAttribute('time') !== '' ? $testCase->getAttribute('time') : '0');
        $assertions = (int) ($testCase->getAttribute('assertions') !== '' ? $testCase->getAttribute('assertions') : '0');
        $status = statusForTestCase($testCase);

        ++$totals['tests'];
        $totals['assertions'] += $assertions;
        $totals['time'] += $time;
        if ($status === 'failure') {
            ++$totals['failures'];
        } elseif ($status === 'error') {
            ++$totals['errors'];
        } elseif ($status === 'skipped') {
            ++$totals['skipped'];
        }

        $classes[$className] ??= [
            'class' => $className,
            'tests' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'skipped' => 0,
            'time' => 0.0,
        ];
        ++$classes[$className]['tests'];
        $classes[$className]['assertions'] += $assertions;
        $classes[$className]['time'] += $time;
        if ($status === 'failure') {
            ++$classes[$className]['failures'];
        } elseif ($status === 'error') {
            ++$classes[$className]['errors'];
        } elseif ($status === 'skipped') {
            ++$classes[$className]['skipped'];
        }

        $testCases[] = [
            'class' => $className,
            'name' => $testName,
            'status' => $status,
            'assertions' => $assertions,
            'time' => $time,
        ];
    }

    usort($testCases, static fn (array $left, array $right): int => $right['time'] <=> $left['time']);
    $classRows = array_values($classes);
    usort($classRows, static fn (array $left, array $right): int => $right['time'] <=> $left['time']);

    return [
        'source' => $path,
        'totals' => $totals,
        'classes' => $classRows,
        'slowestClasses' => array_slice($classRows, 0, $limit),
        'slowestTests' => array_slice($testCases, 0, $limit),
    ];
}

/**
 * @param array<string, mixed> $summary
 */
function renderTextSummary(array $summary): string
{
    $totals = $summary['totals'];
    $lines = [
        'PHPUnit JUnit duration summary',
        sprintf('Source: %s', $summary['source']),
        sprintf(
            'Totals: %d tests, %d assertions, %.3fs, %d failures, %d errors, %d skipped',
            $totals['tests'],
            $totals['assertions'],
            $totals['time'],
            $totals['failures'],
            $totals['errors'],
            $totals['skipped'],
        ),
        '',
        'Slowest classes:',
    ];

    foreach ($summary['slowestClasses'] as $row) {
        $lines[] = sprintf(
            '- %.3fs %s (%d tests, %d assertions)',
            $row['time'],
            $row['class'],
            $row['tests'],
            $row['assertions'],
        );
    }

    $lines[] = '';
    $lines[] = 'Slowest tests:';
    foreach ($summary['slowestTests'] as $row) {
        $lines[] = sprintf(
            '- %.3fs %s::%s [%s]',
            $row['time'],
            $row['class'],
            $row['name'],
            $row['status'],
        );
    }

    return implode("\n", $lines)."\n";
}

/**
 * @param array<string, mixed> $baseline
 * @param array<string, mixed> $current
 *
 * @return array<string, mixed>
 */
function compareSummaries(array $baseline, array $current, int $limit = 15): array
{
    $baselineClasses = indexRowsByName($baseline['classes'] ?? $baseline['slowestClasses'], 'class');
    $currentClasses = indexRowsByName($current['classes'] ?? $current['slowestClasses'], 'class');
    $classNames = array_unique(array_merge(array_keys($baselineClasses), array_keys($currentClasses)));

    $classes = [];
    foreach ($classNames as $className) {
        $before = (float) ($baselineClasses[$className]['time'] ?? 0.0);
        $after = (float) ($currentClasses[$className]['time'] ?? 0.0);
        $classes[] = [
            'class' => $className,
            'before' => $before,
            'after' => $after,
            'delta' => $after - $before,
            'testsBefore' => (int) ($baselineClasses[$className]['tests'] ?? 0),
            'testsAfter' => (int) ($currentClasses[$className]['tests'] ?? 0),
        ];
    }

    usort($classes, static function (array $left, array $right): int {
        $deltaComparison = abs($right['delta']) <=> abs($left['delta']);
        if ($deltaComparison !== 0) {
            return $deltaComparison;
        }

        return $right['after'] <=> $left['after'];
    });

    return [
        'baselineSource' => $baseline['source'],
        'currentSource' => $current['source'],
        'baselineTotals' => $baseline['totals'],
        'currentTotals' => $current['totals'],
        'classDeltas' => array_slice($classes, 0, $limit),
    ];
}

/**
 * @param array<string, mixed> $comparison
 */
function renderTextComparison(array $comparison): string
{
    $baselineTotals = $comparison['baselineTotals'];
    $currentTotals = $comparison['currentTotals'];
    $totalDelta = (float) $currentTotals['time'] - (float) $baselineTotals['time'];

    $lines = [
        'PHPUnit JUnit duration comparison',
        sprintf('Baseline: %s', $comparison['baselineSource']),
        sprintf('Current: %s', $comparison['currentSource']),
        sprintf(
            'Totals: %.3fs -> %.3fs (%+.3fs), %d -> %d tests',
            $baselineTotals['time'],
            $currentTotals['time'],
            $totalDelta,
            $baselineTotals['tests'],
            $currentTotals['tests'],
        ),
        '',
        'Largest class deltas:',
    ];

    foreach ($comparison['classDeltas'] as $row) {
        $lines[] = sprintf(
            '- %+.3fs %.3fs -> %.3fs %s (%d -> %d tests)',
            $row['delta'],
            $row['before'],
            $row['after'],
            $row['class'],
            $row['testsBefore'],
            $row['testsAfter'],
        );
    }

    return implode("\n", $lines)."\n";
}

/**
 * @param array<int, array<string, mixed>> $rows
 *
 * @return array<string, array<string, mixed>>
 */
function indexRowsByName(array $rows, string $nameKey): array
{
    $indexed = [];
    foreach ($rows as $row) {
        $indexed[(string) $row[$nameKey]] = $row;
    }

    return $indexed;
}

function statusForTestCase(\DOMElement $testCase): string
{
    if ($testCase->getElementsByTagName('failure')->length > 0) {
        return 'failure';
    }
    if ($testCase->getElementsByTagName('error')->length > 0) {
        return 'error';
    }
    if ($testCase->getElementsByTagName('skipped')->length > 0) {
        return 'skipped';
    }

    return 'passed';
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    try {
        if (($argv[1] ?? null) === '--compare') {
            $baselinePath = $argv[2] ?? null;
            $currentPath = $argv[3] ?? null;
            if ($baselinePath === null || $currentPath === null) {
                throw new \InvalidArgumentException('Usage: php scripts/summarize_phpunit_junit.php --compare baseline.xml current.xml [limit]');
            }
            $limit = isset($argv[4]) ? max(1, (int) $argv[4]) : 15;

            echo renderTextComparison(compareSummaries(summarizeFile($baselinePath, PHP_INT_MAX), summarizeFile($currentPath, PHP_INT_MAX), $limit));

            return;
        }

        $path = $argv[1] ?? 'var/reports/phpunit-junit.xml';
        $limit = isset($argv[2]) ? max(1, (int) $argv[2]) : 15;

        echo renderTextSummary(summarizeFile($path, $limit));
    } catch (\Throwable $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(1);
    }
}
