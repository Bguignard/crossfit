<?php

namespace App\Tests;

use App\Services\Competition\AthleteNameNormalizer;
use PHPUnit\Framework\TestCase;

final class AthleteNameNormalizerTest extends TestCase
{
    public function testNormalizesNamesWithoutAccents(): void
    {
        $normalizer = new AthleteNameNormalizer();

        self::assertSame('oceane garat', $normalizer->normalize('Océane Garat'));
        self::assertSame('oceane garat', $normalizer->normalize('Oceane Garat'));
        self::assertSame('tia clair toomey', $normalizer->normalize('Tia-Clair Toomey'));
    }
}
