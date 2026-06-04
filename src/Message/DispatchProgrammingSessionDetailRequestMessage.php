<?php

namespace App\Message;

final readonly class DispatchProgrammingSessionDetailRequestMessage
{
    public function __construct(
        public string $requestId,
    ) {
    }
}
