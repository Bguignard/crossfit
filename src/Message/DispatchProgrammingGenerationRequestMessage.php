<?php

namespace App\Message;

final readonly class DispatchProgrammingGenerationRequestMessage
{
    public function __construct(
        public string $requestId,
    ) {
    }
}
