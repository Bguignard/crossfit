<?php

namespace App\Services\Workout;

interface ChatGPTUsageAwareInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function getLastUsage(): ?array;
}
