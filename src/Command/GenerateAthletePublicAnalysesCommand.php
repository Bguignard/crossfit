<?php

namespace App\Command;

use App\Services\Competition\AthletePublicAnalysisGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:athletes:generate-public-analyses',
    description: 'Generate or refresh stored public AI analyses for CrossFit Games athletes.',
)]
final class GenerateAthletePublicAnalysesCommand extends Command
{
    public function __construct(private readonly AthletePublicAnalysisGenerator $generator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum eligible athletes to inspect.', '25')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Regenerate even when a fresh analysis already exists.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $force = (bool) $input->getOption('force');
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->generator->eligibleAthletes($limit) as $athlete) {
            try {
                if (!$force && !$this->generator->shouldGenerate($athlete)) {
                    ++$skipped;
                    continue;
                }

                $analysis = $force
                    ? $this->generator->generate($athlete)
                    : $this->generator->generateIfNeeded($athlete);

                if ($analysis === null) {
                    ++$skipped;
                    continue;
                }

                ++$generated;
                $io->writeln(sprintf('Generated %s', $athlete->getDisplayName()));
            } catch (\Throwable $exception) {
                ++$failed;
                $io->warning(sprintf('%s: %s', $athlete->getDisplayName(), $exception->getMessage()));
            }
        }

        $io->table(['generated', 'skipped', 'failed'], [[$generated, $skipped, $failed]]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
