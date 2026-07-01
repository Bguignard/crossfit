<?php

namespace App\Tests;

use App\Services\Workout\AiGeneration\AiTokenCostEstimator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class AiTokenCostEstimatorTest extends TestCase
{
    public function testEstimatesInputOnlyCost(): void
    {
        $estimator = new AiTokenCostEstimator();

        self::assertSame('0.000250', $estimator->estimateUsd('gpt-5-mini', 1000, null));
    }

    public function testEstimatesOutputOnlyCost(): void
    {
        $estimator = new AiTokenCostEstimator();

        self::assertSame('0.002000', $estimator->estimateUsd('gpt-5-mini', null, 1000));
    }

    public function testEstimatesCombinedInputAndOutputCost(): void
    {
        $estimator = new AiTokenCostEstimator();

        self::assertSame('0.002250', $estimator->estimateUsd('gpt-5-mini', 1000, 1000));
    }

    public function testNormalizesVersionedModelNames(): void
    {
        $estimator = new AiTokenCostEstimator();

        self::assertSame('gpt-5.4-mini', $estimator->canonicalModel('gpt-5.4-mini-2026-03-17'));
        self::assertSame('0.009000', $estimator->estimateUsd('gpt-5.4-mini-2026-03-17', 1000, 1000));
    }

    public function testUnknownModelKeepsCostUnknown(): void
    {
        $estimator = new AiTokenCostEstimator();

        self::assertNull($estimator->estimateUsd('unknown-model', 1000, 1000));
        self::assertNull($estimator->estimateUsd('gpt-5-mini', null, null));
    }
}
