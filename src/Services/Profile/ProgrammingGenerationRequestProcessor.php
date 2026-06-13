<?php

namespace App\Services\Profile;

use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class ProgrammingGenerationRequestProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PythonWorkerClientInterface $pythonWorkerClient,
        private ?ProgrammingNotificationSenderInterface $notificationSender = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function process(ProgrammingGenerationRequest $request): bool
    {
        if ($request->getStatus() !== ProgrammingGenerationRequestStatusEnum::QUEUED) {
            return false;
        }

        $request->markRunning();
        $this->entityManager->flush();

        try {
            $response = $this->pythonWorkerClient->submitProgrammingGeneration($request);
            $programming = $response['programming'] ?? null;

            if (!is_array($programming)) {
                throw new \RuntimeException('Python worker response did not include a programming object.');
            }

            $request->markCompleted($programming);
        } catch (\Throwable $exception) {
            $request->markFailed($exception->getMessage());
        }

        $this->entityManager->flush();

        if ($request->getStatus() === ProgrammingGenerationRequestStatusEnum::COMPLETED) {
            try {
                $this->notificationSender?->sendProgrammingReady($request);
            } catch (\Throwable $exception) {
                $this->logger?->warning('Unable to send programming ready email.', [
                    'request_id' => (string) $request->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $request->getStatus() === ProgrammingGenerationRequestStatusEnum::COMPLETED;
    }
}
