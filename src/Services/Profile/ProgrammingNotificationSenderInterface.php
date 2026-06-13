<?php

namespace App\Services\Profile;

use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;

interface ProgrammingNotificationSenderInterface
{
    public function sendProgrammingReady(ProgrammingGenerationRequest $request): void;

    public function sendSessionDetailsReady(ProgrammingSessionDetailRequest $request): void;

    /**
     * @param array<string, mixed> $session
     */
    public function sendCurrentSession(ProgrammingSessionDetailRequest $request, array $session): void;
}
