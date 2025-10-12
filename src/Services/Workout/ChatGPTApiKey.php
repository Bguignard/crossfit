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
        private HttpClientInterface $client,
    ) {
    }

    public function getWorkoutFlowFromPrompt(string $prompt): string
    {
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
                    'model' => 'gpt-3.5-turbo',
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
            return 'Error: '.$e->getMessage();
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
