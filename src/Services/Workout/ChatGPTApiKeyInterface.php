<?php

namespace App\Services\Workout;

interface ChatGPTApiKeyInterface
{
    public function getWorkoutFlowFromPrompt(string $prompt): string;
}
