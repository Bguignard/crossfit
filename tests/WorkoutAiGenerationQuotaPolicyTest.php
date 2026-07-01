<?php

namespace App\Tests;

use App\Entity\Security\User;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationActor;
use App\Services\Workout\AiGeneration\WorkoutAiGenerationQuotaPolicy;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
final class WorkoutAiGenerationQuotaPolicyTest extends TestCase
{
    public function testDailyLimitsKeepAnonymousUserAndAdminEntitlementsCentralized(): void
    {
        $policy = new WorkoutAiGenerationQuotaPolicy(5, 10);

        self::assertSame(
            5,
            $policy->dailyLimitFor(new WorkoutAiGenerationActor(null, WorkoutAiGenerationUsage::ACTOR_ANONYMOUS, 'visitor-hash'))
        );
        self::assertSame(
            10,
            $policy->dailyLimitFor(new WorkoutAiGenerationActor(new User('free@example.com'), WorkoutAiGenerationUsage::ACTOR_USER, null))
        );
        self::assertNull(
            $policy->dailyLimitFor(new WorkoutAiGenerationActor(new User('admin@example.com'), WorkoutAiGenerationUsage::ACTOR_ADMIN, null))
        );
    }
}
