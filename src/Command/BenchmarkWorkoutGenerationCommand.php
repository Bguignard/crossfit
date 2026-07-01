<?php

namespace App\Command;

use App\Services\Workout\Audit\WorkoutGenerationBenchmarkLiveRunnerInterface;
use App\Services\Workout\Audit\WorkoutGenerationBenchmarkMatrixBuilder;
use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use App\Services\Workout\Audit\WorkoutStimulusAuditScenario;
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
        private readonly ?WorkoutGenerationBenchmarkLiveRunnerInterface $liveRunner = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('models', null, InputOption::VALUE_REQUIRED, 'Comma-separated OpenAI models to include in the matrix.', 'gpt-5.4-mini,gpt-5.4')
            ->addOption('strategies', null, InputOption::VALUE_REQUIRED, 'Comma-separated strategies to include.', implode(',', array_keys($this->matrixBuilder->strategies())))
            ->addOption('scenarios', null, InputOption::VALUE_REQUIRED, 'Comma-separated scenario slugs to include. Required in live mode.')
            ->addOption('live', null, InputOption::VALUE_NONE, 'Execute protected live benchmark entries. Dry-run remains the default.')
            ->addOption('confirm-live', null, InputOption::VALUE_NONE, 'Required with --live to acknowledge OpenAI calls and cost.')
            ->addOption('max-live-runs', null, InputOption::VALUE_REQUIRED, 'Maximum model x strategy x scenario entries allowed in live mode.', '3')
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
        $strategies = $this->matrixBuilder->normalizeStrategies(explode(',', $this->stringOption($input->getOption('strategies'))));
        if ($strategies === []) {
            throw new \InvalidArgumentException('At least one strategy must be provided.');
        }

        $live = (bool) $input->getOption('live');
        $scenarios = $this->selectedScenarios($this->auditor->scenarios(), $input->getOption('scenarios'), $live);
        $jsonPath = $this->stringOption($input->getOption('output'));
        $markdownPath = $this->stringOption($input->getOption('markdown-output'));
        $report = $live
            ? $this->liveReport($input, $models, $strategies, $scenarios)
            : $this->matrixBuilder->buildDryRunReport($scenarios, $models, $strategies);

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
        if ($live) {
            $io->warning('Live mode was explicitly enabled. Review token/cost fields before comparing model economics.');
        } else {
            $io->note('Dry run only: no OpenAI call is performed. Use --live --confirm-live with a strict --max-live-runs limit for protected live execution.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<string>                       $models
     * @param list<string>                       $strategies
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     *
     * @return array<string, mixed>
     */
    private function liveReport(InputInterface $input, array $models, array $strategies, array $scenarios): array
    {
        if ($this->liveRunner === null) {
            throw new \RuntimeException('Live benchmark runner is not configured.');
        }
        if (!$input->getOption('confirm-live')) {
            throw new \InvalidArgumentException('Live benchmark requires --confirm-live to acknowledge OpenAI calls and cost.');
        }

        $maxLiveRuns = $this->positiveIntOption($input->getOption('max-live-runs'), 'max-live-runs');
        $entryCount = count($models) * count($strategies) * count($scenarios);
        if ($entryCount > $maxLiveRuns) {
            throw new \InvalidArgumentException(sprintf('Live benchmark would create %d entries, above --max-live-runs=%d. Narrow --models, --strategies or --scenarios first.', $entryCount, $maxLiveRuns));
        }

        $requiresOpenAi = count(array_filter($strategies, fn (string $strategy): bool => $this->liveRunner->requiresOpenAi($strategy))) > 0;
        if ($requiresOpenAi && !$this->liveRunner->isConfigured()) {
            throw new \RuntimeException('CHAT_GPT_API_KEY must be configured before running live benchmark entries.');
        }

        $entries = [];
        foreach ($models as $model) {
            foreach ($strategies as $strategy) {
                foreach ($scenarios as $scenario) {
                    $entries[] = $this->liveRunner->run($model, $strategy, $scenario);
                }
            }
        }

        return $this->matrixBuilder->buildLiveReport($scenarios, $models, $strategies, $entries);
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

    /**
     * @param list<WorkoutStimulusAuditScenario> $availableScenarios
     *
     * @return list<WorkoutStimulusAuditScenario>
     */
    private function selectedScenarios(array $availableScenarios, mixed $rawSlugs, bool $live): array
    {
        if (!is_string($rawSlugs) || trim($rawSlugs) === '') {
            if ($live) {
                throw new \InvalidArgumentException('Live benchmark requires --scenarios with one or more explicit scenario slugs.');
            }

            return $availableScenarios;
        }

        $requested = array_filter(array_map('trim', explode(',', $rawSlugs)), static fn (string $slug): bool => $slug !== '');
        $scenariosBySlug = [];
        foreach ($availableScenarios as $scenario) {
            $scenariosBySlug[$scenario->slug] = $scenario;
        }

        $selected = [];
        foreach ($requested as $slug) {
            if (!array_key_exists($slug, $scenariosBySlug)) {
                throw new \InvalidArgumentException(sprintf('Unknown scenario "%s". Allowed scenarios: %s.', $slug, implode(', ', array_keys($scenariosBySlug))));
            }

            $selected[$slug] = $scenariosBySlug[$slug];
        }

        return array_values($selected);
    }

    private function stringOption(mixed $value): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : throw new \InvalidArgumentException('Option must be a non-empty string.');
    }

    private function positiveIntOption(mixed $value, string $name): int
    {
        if (!is_string($value) || preg_match('/^\d+$/', $value) !== 1 || (int) $value < 1) {
            throw new \InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $name));
        }

        return (int) $value;
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
