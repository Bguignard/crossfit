<?php

namespace App\Message;

final readonly class DispatchPerformanceAnalysisRequestMessage
{
    public function __construct(
        public string $requestId,
    ) {
    }
}
