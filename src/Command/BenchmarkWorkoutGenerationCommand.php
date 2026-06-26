<?php

namespace App\Command;

use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:benchmark-generation',
    description: 'Build a dry-run benchmark matrix for generated WOD scenarios.',
)]
final class BenchmarkWorkoutGenerationCommand extends Command
{
    public function __construct(
        private readonly WorkoutStimulusAuditor $auditor,
        private readonly WorkoutGenerationBenchmarkMatrixBuilder $matrixBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('models', null, InputOption::VALUE_REQUIRED, 'Comma-separated OpenAI models to include in the matrix.', 'gpt-5.4-mini,gpt-5.4')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'JSON report path.', 'var/reports/workout-generation-benchmark.json')
            ->addOption('markdown-output', null, InputOption::VALUE_REQUIRED, 'Markdown report path.', 'var/reports/workout-generation-benchmark.md');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $models = $this->matrixBuilder->normalizeModels(explode(',', $this->stringOption($input->getOption('models'))));
        if ($models === []) {
            throw new \InvalidArgumentException('At least one model must be provided.');
        }

        $scenarios = $this->auditor->scenarios();
        $report = $this->matrixBuilder->buildDryRunReport($scenarios, $models);
        $jsonPath = $this->stringOption($input->getOption('output'));
        $markdownPath = $this->stringOption($input->getOption('markdown-output'));

        $this->writeFile($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->writeFile($markdownPath, $this->markdownReport($report));

        $io->title('Workout generation benchmark matrix');
        $io->table(
            ['models', 'strategies', 'scenarios', 'entries', 'dry_run'],
            [[$report['modelCount'], $report['strategyCount'], $report['scenarioCount'], $report['entryCount'], $report['dryRun'] ? 'yes' : 'no']],
        );
        $io->table(
            ['Strategy', 'Entries', 'Passed'],
            array_map(
                static fn (string $strategy, array $summary): array => [
                    $strategy,
                    $summary['entryCount'],
                    $summary['passedCount'],
                ],
                array_keys($report['summary']['byStrategy']),
                $report['summary']['byStrategy'],
            ),
        );
        $io->success(sprintf('Wrote benchmark matrix to %s and %s.', $jsonPath, $markdownPath));
        $io->note('Dry run only: no OpenAI call is performed. Live multi-model execution is the next step.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function markdownReport(array $report): string
    {
        $lines = [
            '# Workout Generation Benchmark Matrix',
            '',
            sprintf('- Generated at: `%s`', $report['generatedAt']),
            sprintf('- Dry run: `%s`', $report['dryRun'] ? 'true' : 'false'),
            sprintf('- Models: `%s`', implode(', ', $report['models'])),
            sprintf('- Strategies: `%s`', implode(', ', array_keys($report['strategies']))),
            sprintf('- Scenarios: `%d`', $report['scenarioCount']),
            sprintf('- Entries: `%d`', $report['entryCount']),
            '',
            '## Strategies',
            '',
        ];

        foreach ($report['strategies'] as $strategy => $description) {
            $lines[] = sprintf('- `%s`: %s', $strategy, $description);
        }

        $lines[] = '';
        $lines[] = '## Matrix';
        $lines[] = '';
        $lines[] = '| Model | Strategy | Scenario | Status | Passed | Tokens | Retries | Duration | Cost |';
        $lines[] = '| --- | --- | --- | --- | --- | --- | --- | --- | --- |';

        foreach ($report['entries'] as $entry) {
            $lines[] = sprintf(
                '| `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` | `%s` |',
                $entry['model'],
                $entry['strategy'],
                $entry['scenario'],
                $entry['status'],
                $entry['passed'] ? 'yes' : 'no',
                $this->nullableMarkdownValue($entry['tokenUsage']['totalTokens']),
                $this->nullableMarkdownValue($entry['retryCount']),
                $this->nullableMarkdownValue($entry['durationMs']),
                $this->nullableMarkdownValue($entry['estimatedCostUsd']),
            );
        }

        $lines[] = '';
        $lines[] = '## Live Mode';
        $lines[] = '';
        $lines[] = sprintf('- Available: `%s`', $report['liveMode']['available'] ? 'true' : 'false');
        $lines[] = sprintf('- Reason: %s', $report['liveMode']['reason']);

        return implode("\n", $lines)."\n";
    }

    private function nullableMarkdownValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    private function stringOption(mixed $value): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : throw new \InvalidArgumentException('Option must be a non-empty string.');
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create report directory "%s".', $directory));
        }

        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException(sprintf('Unable to write report "%s".', $path));
        }
    }
}
