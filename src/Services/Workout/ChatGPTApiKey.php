<?php

namespace App\Services\Workout;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatGPTApiKey implements ChatGPTApiKeyInterface, ChatGPTUsageAwareInterface
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $lastUsage = null;

    public function __construct(
        public readonly string $chatGPTApiKey,
        private readonly string $openAiModel,
        private readonly HttpClientInterface $client,
    ) {
    }

    public function getWorkoutFlowFromPrompt(string $prompt): string
    {
        $startedAt = microtime(true);
        $this->lastUsage = null;

        if (trim($this->chatGPTApiKey) === '') {
            throw new \RuntimeException('CHAT_GPT_API_KEY is required to generate workouts.');
        }

        //        $client = $this->client->withOptions([
        //            'base_uri' => 'https://api.openai.com/v1/',
        //            'headers' =>
        //                ['Authorization' => 'Bearer ' . $this->chatGPTApiKey,
        //                'Content-Type' => 'application/json'],
        //            'extra' => ['prompt' => $prompt],
        //        ]);
        //        $response = $client->request(
        //            'POST',
        //            'https://api.openai.com/v1/'
        //        );

        $response = $this->client->request(
            'POST',
            'https://api.openai.com/v1/responses',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->chatGPTApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'input' => $prompt,
                    'max_output_tokens' => 1024,
                ],
            ]
        );

        try {
            $data = $response->toArray();
        } catch (ClientExceptionInterface|
        DecodingExceptionInterface|
        RedirectionExceptionInterface|
        ServerExceptionInterface|
        TransportExceptionInterface $e) {
            throw new \RuntimeException('OpenAI workout generation failed: '.$this->errorMessage($e), 0, $e);
        }
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $content = trim((string) ($data['output_text'] ?? ''));
        if ($content === '') {
            $content = $this->extractOutputText($data);
        }
        if ($content === '') {
            throw new \RuntimeException('OpenAI workout generation returned an empty response.');
        }

        $this->lastUsage = $this->usageFromResponse($data, $durationMs);

        return $content;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    private function errorMessage(\Throwable $exception): string
    {
        if (method_exists($exception, 'getResponse')) {
            $response = $exception->getResponse();
            try {
                $content = trim($response->getContent(false));
                if ($content !== '') {
                    return $content;
                }
            } catch (\Throwable) {
                // Keep the original exception message if the error body cannot be read.
            }
        }

        return $exception->getMessage();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractOutputText(array $data): string
    {
        $parts = [];
        foreach (($data['output'] ?? []) as $output) {
            if (!is_array($output)) {
                continue;
            }
            foreach (($output['content'] ?? []) as $content) {
                if (!is_array($content)) {
                    continue;
                }
                $text = $content['text'] ?? null;
                if (is_string($text) && $text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function usageFromResponse(array $data, int $durationMs): array
    {
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        return [
            'request_type' => 'workout_generation',
            'model' => is_string($data['model'] ?? null) ? $data['model'] : $this->openAiModel,
            'prompt_tokens' => $this->nullableInt($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? null),
            'completion_tokens' => $this->nullableInt($usage['output_tokens'] ?? $usage['completion_tokens'] ?? null),
            'total_tokens' => $this->nullableInt($usage['total_tokens'] ?? null),
            'duration_ms' => $durationMs,
            'status' => 'success',
            'estimated_cost_usd' => null,
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }
}
