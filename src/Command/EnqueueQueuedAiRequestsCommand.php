<?php

namespace App\Command;

use App\Services\Profile\QueuedAiRequestMessengerDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ai-requests:enqueue-queued',
    description: 'Enqueue queued personal AI requests into Messenger for asynchronous processing.',
)]
final class EnqueueQueuedAiRequestsCommand extends Command
{
    public function __construct(
        private readonly QueuedAiRequestMessengerDispatcher $dispatcher,
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
        $result = $this->dispatcher->enqueueQueuedBacklog($limit);

        $io->table(
            ['analysis_enqueued', 'programming_enqueued'],
            [[$result['analysis'], $result['programming']]]
        );

        return Command::SUCCESS;
    }
}
