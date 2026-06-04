<?php

namespace App\MessageHandler;

use App\Entity\Product\PerformanceAnalysisRequest;
use App\Message\DispatchPerformanceAnalysisRequestMessage;
use App\Services\Profile\PerformanceAnalysisRequestProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DispatchPerformanceAnalysisRequestMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PerformanceAnalysisRequestProcessor $processor,
    ) {
    }

    public function __invoke(DispatchPerformanceAnalysisRequestMessage $message): void
    {
        /** @var PerformanceAnalysisRequest|null $request */
        $request = $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->find($message->requestId);
        if ($request === null) {
            return;
        }

        $this->processor->process($request);
    }
}
