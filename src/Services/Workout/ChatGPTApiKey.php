<?php

namespace App\Services\Workout;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ChatGPTApiKey implements ChatGPTApiKeyInterface
{
    public function __construct(
        public string $chatGPTApiKey,
        private string $openAiModel,
        private HttpClientInterface $client,
    ) {
    }

    public function getWorkoutFlowFromPrompt(string $prompt): string
    {
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
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->chatGPTApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 512,
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
            throw new \RuntimeException('OpenAI workout generation failed: '.$e->getMessage(), 0, $e);
        }

        $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        if ($content === '') {
            throw new \RuntimeException('OpenAI workout generation returned an empty response.');
        }

        return $content;
    }
}
