<?php

namespace App\MessageHandler;

use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Message\DispatchProgrammingSessionDetailRequestMessage;
use App\Services\Profile\ProgrammingSessionDetailRequestProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DispatchProgrammingSessionDetailRequestMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProgrammingSessionDetailRequestProcessor $processor,
    ) {
    }

    public function __invoke(DispatchProgrammingSessionDetailRequestMessage $message): void
    {
        /** @var ProgrammingSessionDetailRequest|null $request */
        $request = $this->entityManager->getRepository(ProgrammingSessionDetailRequest::class)->find($message->requestId);
        if ($request === null) {
            return;
        }

        $this->processor->process($request);
    }
}
