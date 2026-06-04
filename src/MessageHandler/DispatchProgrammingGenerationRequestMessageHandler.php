<?php

namespace App\MessageHandler;

use App\Entity\Product\ProgrammingGenerationRequest;
use App\Message\DispatchProgrammingGenerationRequestMessage;
use App\Services\Profile\ProgrammingGenerationRequestProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DispatchProgrammingGenerationRequestMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProgrammingGenerationRequestProcessor $processor,
    ) {
    }

    public function __invoke(DispatchProgrammingGenerationRequestMessage $message): void
    {
        /** @var ProgrammingGenerationRequest|null $request */
        $request = $this->entityManager->getRepository(ProgrammingGenerationRequest::class)->find($message->requestId);
        if ($request === null) {
            return;
        }

        $this->processor->process($request);
    }
}
