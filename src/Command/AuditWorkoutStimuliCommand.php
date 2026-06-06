<?php

namespace App\Command;

use App\Services\Workout\Audit\WorkoutStimulusAuditor;
use App\Services\Workout\Audit\WorkoutStimulusAuditResult;
use App\Services\Workout\Audit\WorkoutStimulusAuditScenario;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:audit-stimuli',
    description: 'Generate a dry-run audit report for workout generation stimuli.',
)]
final class AuditWorkoutStimuliCommand extends Command
{
    public function __construct(private readonly WorkoutStimulusAuditor $auditor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'JSON report path.', 'var/reports/workout-stimulus-audit.json')
            ->addOption('markdown-output', null, InputOption::VALUE_REQUIRED, 'Markdown report path.', 'var/reports/workout-stimulus-audit.md');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $scenarios = $this->auditor->scenarios();
        $results = array_map(
            fn (WorkoutStimulusAuditScenario $scenario): WorkoutStimulusAuditResult => $this->auditor->evaluate($scenario, null),
            $scenarios,
        );
        $report = $this->report($scenarios, $results);
        $jsonPath = $this->stringOption($input->getOption('output'));
        $markdownPath = $this->stringOption($input->getOption('markdown-output'));

        $this->writeFile($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->writeFile($markdownPath, $this->markdownReport($report));

        $io->title('Workout stimulus audit dry run');
        $io->table(
            ['scenarios', 'generated_workouts', 'passed'],
            [[count($scenarios), 0, 0]],
        );
        $io->table(
            ['Stimulus', 'Type', 'Time cap', 'Movements'],
            array_map(
                fn (WorkoutStimulusAuditScenario $scenario): array => [
                    $scenario->stimulus,
                    $scenario->workoutType,
                    sprintf('%d min', $scenario->timeCap),
                    $scenario->movementCount,
                ],
                $scenarios,
            ),
        );
        $io->success(sprintf('Wrote reports to %s and %s.', $jsonPath, $markdownPath));
        $io->note('Dry run only: generated WODs are not created yet. Use this report as the audit scenario baseline.');

        return Command::SUCCESS;
    }

    /**
     * @param list<WorkoutStimulusAuditScenario> $scenarios
     * @param list<WorkoutStimulusAuditResult>   $results
     *
     * @return array<string, mixed>
     */
    private function report(array $scenarios, array $results): array
    {
        return [
            'kind' => 'workout_stimulus_audit_v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'dryRun' => true,
            'scenarioCount' => count($scenarios),
            'generatedWorkoutCount' => 0,
            'passedCount' => 0,
            'scenarios' => array_map(
                fn (WorkoutStimulusAuditScenario $scenario): array => [
                    'slug' => $scenario->slug,
                    'stimulus' => $scenario->stimulus,
                    'intent' => $scenario->intent,
                    'payload' => $scenario->payload(),
                    'expectedTerms' => $scenario->expectedTerms,
                    'expectedScalingTerms' => $scenario->expectedScalingTerms,
                    'forbiddenTerms' => $scenario->forbiddenTerms,
                    'expectedStationCount' => $scenario->expectedStationCount,
                ],
                $scenarios,
            ),
            'results' => array_map(
                fn (WorkoutStimulusAuditResult $result): array => $result->toArray(),
                $results,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function markdownReport(array $report): string
    {
        $lines = [
            '# Workout Stimulus Audit',
            '',
            sprintf('- Generated at: `%s`', $report['generatedAt']),
            sprintf('- Dry run: `%s`', $report['dryRun'] ? 'true' : 'false'),
            sprintf('- Scenarios: `%d`', $report['scenarioCount']),
            '',
            '## Scenarios',
            '',
        ];

        foreach ($report['scenarios'] as $scenario) {
            $lines[] = sprintf('### %s', $scenario['stimulus']);
            $lines[] = '';
            $lines[] = sprintf('- Slug: `%s`', $scenario['slug']);
            $lines[] = sprintf('- Type: `%s`', $scenario['payload']['workoutType']);
            $lines[] = sprintf('- Time cap: `%d min`', $scenario['payload']['timeCap']);
            $lines[] = sprintf('- Movements: `%d`', $scenario['payload']['numberOfDifferentMovements']);
            $lines[] = sprintf('- Intent: %s', $scenario['intent']);
            $lines[] = sprintf('- Expected terms: `%s`', implode(', ', $scenario['expectedTerms']));
            $lines[] = sprintf('- Scaling terms: `%s`', implode(', ', $scenario['expectedScalingTerms']));
            $lines[] = sprintf('- Forbidden terms: `%s`', implode(', ', $scenario['forbiddenTerms']));
            $lines[] = '';
        }

        $lines[] = '## Dry Run Checks';
        $lines[] = '';
        $lines[] = '| Scenario | Generated workout | Passed |';
        $lines[] = '| --- | --- | --- |';
        foreach ($report['results'] as $result) {
            $lines[] = sprintf(
                '| `%s` | `%s` | `%s` |',
                $result['scenario'],
                $result['checks']['generated_workout_available'] ? 'yes' : 'no',
                $result['passed'] ? 'yes' : 'no'
            );
        }

        return implode("\n", $lines)."\n";
    }

    private function stringOption(mixed $value): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : throw new \InvalidArgumentException('Output path must be a non-empty string.');
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
