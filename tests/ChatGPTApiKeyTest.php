<?php

namespace App\Tests;

use App\Services\Workout\ChatGPTApiKey;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ChatGPTApiKeyTest extends TestCase
{
    public function testWorkoutGenerationUsesResponsesApiWithConfiguredModelAndModernTokenParameter(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $payload = $this->payloadFromOptions($options);

            self::assertSame('POST', $method);
            self::assertSame('https://api.openai.com/v1/responses', $url);
            self::assertSame('gpt-5.4-mini', $payload['model']);
            self::assertArrayHasKey('max_output_tokens', $payload);
            self::assertArrayNotHasKey('max_tokens', $payload);
            self::assertArrayNotHasKey('max_completion_tokens', $payload);
            self::assertSame('Create a workout.', $payload['input']);

            return new MockResponse(json_encode([
                'output_text' => 'AMRAP 12 minutes',
            ], JSON_THROW_ON_ERROR));
        });

        $client = new ChatGPTApiKey('test-key', 'gpt-5.4-mini', $httpClient);

        self::assertSame('AMRAP 12 minutes', $client->getWorkoutFlowFromPrompt('Create a workout.'));
    }

    public function testWorkoutGenerationReadsResponsesOutputContentWhenOutputTextIsMissing(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
                'output' => [
                    [
                        'content' => [
                            ['text' => 'For time'],
                            ['text' => '21-15-9 row and burpees'],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new ChatGPTApiKey('test-key', 'gpt-5.4-mini', $httpClient);

        self::assertSame("For time\n21-15-9 row and burpees", $client->getWorkoutFlowFromPrompt('Create a workout.'));
    }

    public function testWorkoutGenerationErrorIncludesOpenAiResponseBody(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
                'error' => ['message' => 'Unsupported parameter: max_tokens'],
            ], JSON_THROW_ON_ERROR), ['http_code' => 400]),
        ]);
        $client = new ChatGPTApiKey('test-key', 'gpt-5.4-mini', $httpClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported parameter: max_tokens');

        $client->getWorkoutFlowFromPrompt('Create a workout.');
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function payloadFromOptions(array $options): array
    {
        self::assertArrayHasKey('body', $options);
        self::assertIsString($options['body']);

        $payload = json_decode($options['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
