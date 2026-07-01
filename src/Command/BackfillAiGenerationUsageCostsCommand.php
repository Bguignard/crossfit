<?php

namespace App\Command;

use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Services\Workout\AiGeneration\AiTokenCostEstimator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ai-usage:backfill-costs',
    description: 'Backfill estimated OpenAI costs on stored workout AI usage rows.',
)]
final class BackfillAiGenerationUsageCostsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AiTokenCostEstimator $costEstimator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('write', null, InputOption::VALUE_NONE, 'Persist the computed costs. Defaults to dry-run.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of candidate rows to inspect.', null)
            ->addOption('flush-every', null, InputOption::VALUE_REQUIRED, 'Flush interval when --write is used.', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $write = (bool) $input->getOption('write');
        $limit = $this->positiveIntOption($input->getOption('limit'), 'limit');
        $flushEvery = $this->positiveIntOption($input->getOption('flush-every'), 'flush-every') ?? 100;

        $inspected = 0;
        $priceable = 0;
        $updated = 0;
        $unknown = [];

        foreach ($this->candidateUsages() as $usage) {
            if (!$usage instanceof WorkoutAiGenerationUsage) {
                continue;
            }
            if ($limit !== null && $inspected >= $limit) {
                break;
            }

            ++$inspected;
            $estimatedCost = $this->costEstimator->estimateUsd(
                $usage->getModel(),
                $usage->getPromptTokens(),
                $usage->getCompletionTokens(),
            );

            if ($estimatedCost === null) {
                $unknown[$usage->getModel() ?? 'unknown'] = true;
                continue;
            }

            ++$priceable;
            if (!$write) {
                continue;
            }

            $usage->setEstimatedCostUsd($estimatedCost);
            ++$updated;
            if ($updated % $flushEvery === 0) {
                $this->entityManager->flush();
            }
        }

        if ($write && $updated > 0) {
            $this->entityManager->flush();
        }

        $io->section($write ? 'AI usage cost backfill' : 'AI usage cost backfill dry-run');
        $io->writeln(sprintf('Rows inspected: %d', $inspected));
        $io->writeln(sprintf('Rows priceable: %d', $priceable));
        $io->writeln(sprintf('Rows updated: %d', $updated));
        if ($unknown !== []) {
            $models = array_keys($unknown);
            sort($models);
            $io->writeln(sprintf('Unpriced models: %s', implode(', ', $models)));
        }
        if (!$write) {
            $io->note('Dry-run only. Re-run with --write to persist estimated costs.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return iterable<WorkoutAiGenerationUsage>
     */
    private function candidateUsages(): iterable
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('usage')
            ->from(WorkoutAiGenerationUsage::class, 'usage')
            ->andWhere('usage.estimatedCostUsd IS NULL')
            ->andWhere('usage.model IS NOT NULL')
            ->andWhere('(usage.promptTokens IS NOT NULL OR usage.completionTokens IS NOT NULL)')
            ->orderBy('usage.createdAt', 'ASC')
            ->getQuery()
            ->toIterable();
    }

    private function positiveIntOption(mixed $value, string $name): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $name));
        }
        if (preg_match('/^\d+$/', (string) $value) !== 1 || (int) $value < 1) {
            throw new \InvalidArgumentException(sprintf('Option --%s must be a positive integer.', $name));
        }

        return (int) $value;
    }
}
