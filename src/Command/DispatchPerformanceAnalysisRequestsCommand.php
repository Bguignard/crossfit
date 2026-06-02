<?php

namespace App\Command;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:performance-analysis:dispatch',
    description: 'Dispatch queued personal performance analysis requests to the Python worker.',
)]
final class DispatchPerformanceAnalysisRequestsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonWorkerClientInterface $pythonWorkerClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum queued requests to dispatch.', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $processed = 0;
        $completed = 0;
        $failed = 0;

        foreach ($this->queuedRequests($limit) as $request) {
            ++$processed;
            $request->markRunning();
            $this->entityManager->flush();

            try {
                $response = $this->pythonWorkerClient->submitPerformanceAnalysis($request);
                $analysis = $response['analysis'] ?? null;

                if (!is_array($analysis)) {
                    throw new \RuntimeException('Python worker response did not include an analysis object.');
                }

                $request->markCompleted($analysis);
                ++$completed;
                $io->writeln(sprintf('Completed analysis request %s', $request->getId()));
            } catch (\Throwable $exception) {
                $request->markFailed($exception->getMessage());
                ++$failed;
                $io->warning(sprintf('Failed analysis request %s: %s', $request->getId(), $exception->getMessage()));
            }

            $this->entityManager->flush();
        }

        $io->table(['processed', 'completed', 'failed'], [[$processed, $completed, $failed]]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return list<PerformanceAnalysisRequest>
     */
    private function queuedRequests(int $limit): array
    {
        return $this->entityManager->getRepository(PerformanceAnalysisRequest::class)
            ->createQueryBuilder('request')
            ->andWhere('request.status = :status')
            ->setParameter('status', AnalysisRequestStatusEnum::QUEUED)
            ->orderBy('request.queuedAt', 'ASC')
            ->addOrderBy('request.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
