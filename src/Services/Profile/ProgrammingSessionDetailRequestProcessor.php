<?php

namespace App\Services\Profile;

use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class ProgrammingSessionDetailRequestProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PythonWorkerClientInterface $pythonWorkerClient,
        private ?ProgrammingNotificationSenderInterface $notificationSender = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function process(ProgrammingSessionDetailRequest $request): bool
    {
        if ($request->getStatus() !== ProgrammingGenerationRequestStatusEnum::QUEUED) {
            return false;
        }

        $request->markRunning();
        $this->entityManager->flush();

        try {
            $response = $this->pythonWorkerClient->submitProgrammingSessionDetails($request);
            $detailedProgramming = $response['detailed_programming'] ?? null;

            if (!is_array($detailedProgramming)) {
                throw new \RuntimeException('Python worker response did not include a detailed_programming object.');
            }

            $request->markCompleted($detailedProgramming);
        } catch (\Throwable $exception) {
            $request->markFailed($exception->getMessage());
        }

        $this->entityManager->flush();

        if ($request->getStatus() === ProgrammingGenerationRequestStatusEnum::COMPLETED) {
            try {
                $this->notificationSender?->sendSessionDetailsReady($request);
            } catch (\Throwable $exception) {
                $this->logger?->warning('Unable to send programming session details ready email.', [
                    'request_id' => (string) $request->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $request->getStatus() === ProgrammingGenerationRequestStatusEnum::COMPLETED;
    }
}
