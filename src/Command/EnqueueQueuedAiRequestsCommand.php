<?php

namespace App\Command;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Message\DispatchPerformanceAnalysisRequestMessage;
use App\Message\DispatchProgrammingGenerationRequestMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:ai-requests:enqueue-queued',
    description: 'Enqueue queued personal AI requests into Messenger for asynchronous processing.',
)]
final class EnqueueQueuedAiRequestsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum queued requests to enqueue per type.', '20');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $analysisRequests = $this->queuedAnalysisRequests($limit);
        $programmingRequests = $this->queuedProgrammingRequests($limit);

        foreach ($analysisRequests as $request) {
            $this->messageBus->dispatch(new DispatchPerformanceAnalysisRequestMessage((string) $request->getId()));
        }

        foreach ($programmingRequests as $request) {
            $this->messageBus->dispatch(new DispatchProgrammingGenerationRequestMessage((string) $request->getId()));
        }

        $io->table(
            ['analysis_enqueued', 'programming_enqueued'],
            [[count($analysisRequests), count($programmingRequests)]]
        );

        return Command::SUCCESS;
    }

    /**
     * @return list<PerformanceAnalysisRequest>
     */
    private function queuedAnalysisRequests(int $limit): array
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

    /**
     * @return list<ProgrammingGenerationRequest>
     */
    private function queuedProgrammingRequests(int $limit): array
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
