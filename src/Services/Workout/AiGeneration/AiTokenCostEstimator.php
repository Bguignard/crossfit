<?php

namespace App\Services\Workout\AiGeneration;

final class AiTokenCostEstimator
{
    /**
     * Pricing snapshot in USD per 1M tokens. Update this table when OpenAI pricing changes.
     *
     * @var array<string, array{input: int, output: int}>
     */
    private const PRICING_MICROS_PER_MILLION_TOKENS = [
        'gpt-5' => ['input' => 1_250_000, 'output' => 10_000_000],
        'gpt-5-mini' => ['input' => 250_000, 'output' => 2_000_000],
        'gpt-5-nano' => ['input' => 50_000, 'output' => 400_000],
        'gpt-5.4-mini' => ['input' => 1_000_000, 'output' => 8_000_000],
    ];

    public function estimateUsd(?string $model, ?int $promptTokens, ?int $completionTokens): ?string
    {
        $micros = $this->estimateMicros($model, $promptTokens, $completionTokens);

        return $micros === null ? null : sprintf('%.6F', $micros / 1_000_000);
    }

    public function estimateMicros(?string $model, ?int $promptTokens, ?int $completionTokens): ?int
    {
        $pricing = $this->pricingForModel($model);
        if ($pricing === null) {
            return null;
        }

        $promptTokens = max(0, $promptTokens ?? 0);
        $completionTokens = max(0, $completionTokens ?? 0);
        if ($promptTokens === 0 && $completionTokens === 0) {
            return null;
        }

        return (int) round(
            (($promptTokens * $pricing['input']) + ($completionTokens * $pricing['output'])) / 1_000_000,
        );
    }

    public function canonicalModel(?string $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $model = trim($model);
        if ($model === '') {
            return null;
        }

        return preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $model) ?? $model;
    }

    /**
     * @return array{input: int, output: int}|null
     */
    private function pricingForModel(?string $model): ?array
    {
        $canonicalModel = $this->canonicalModel($model);
        if ($canonicalModel === null) {
            return null;
        }

        return self::PRICING_MICROS_PER_MILLION_TOKENS[$canonicalModel] ?? null;
    }
}
