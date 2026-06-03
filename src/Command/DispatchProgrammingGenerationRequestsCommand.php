<?php

namespace App\Command;

use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:programming-generation:dispatch',
    description: 'Dispatch queued personal programming generation requests to the Python worker.',
)]
final class DispatchProgrammingGenerationRequestsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonWorkerClientInterface $pythonWorkerClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum queued personal requests to dispatch.', '10');
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
                $response = $this->pythonWorkerClient->submitProgrammingGeneration($request);
                $programming = $response['programming'] ?? null;

                if (!is_array($programming)) {
                    throw new \RuntimeException('Python worker response did not include a programming object.');
                }

                $request->markCompleted($programming);
                ++$completed;
                $io->writeln(sprintf('Completed programming request %s', $request->getId()));
            } catch (\Throwable $exception) {
                $request->markFailed($exception->getMessage());
                ++$failed;
                $io->warning(sprintf('Failed programming request %s: %s', $request->getId(), $exception->getMessage()));
            }

            $this->entityManager->flush();
        }

        $io->table(['processed', 'completed', 'failed'], [[$processed, $completed, $failed]]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return list<ProgrammingGenerationRequest>
     */
    private function queuedRequests(int $limit): array
    {
        return $this->entityManager->getRepository(ProgrammingGenerationRequest::class)
            ->createQueryBuilder('request')
            ->andWhere('request.status = :status')
            ->andWhere('request.type = :type')
            ->setParameter('status', ProgrammingGenerationRequestStatusEnum::QUEUED)
            ->setParameter('type', ProgrammingGenerationTypeEnum::INDIVIDUAL)
            ->orderBy('request.queuedAt', 'ASC')
            ->addOrderBy('request.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
