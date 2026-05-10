<?php

namespace App\Tests;

use App\Entity\Product\Enum\PerformanceMetricCategoryEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\PerformanceMetricValueTypeEnum;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;

class PersonalPerformanceModelTest extends AbstractIntegrationTest
{
    public function testUserCanStoreOptionalPerformanceMetrics(): void
    {
        $user = (new User('performance@example.com'))->setPassword('hashed-password');
        $profile = new UserPerformanceProfile($user);
        $backSquat = (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::BACK_SQUAT_1RM))
            ->setNumericValue(150.0, 'kg')
            ->setNotes('Solid single');
        $strictPullUp = (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::STRICT_PULL_UP))
            ->setBooleanValue(true);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($profile);
        $em->persist($backSquat);
        $em->persist($strictPullUp);
        $em->flush();
        $em->clear();

        /** @var User|null $storedUser */
        $storedUser = $this->getRepository(User::class)->findOneBy(['email' => 'performance@example.com']);

        self::assertNotNull($storedUser);
        self::assertCount(1, $storedUser->getPerformanceProfiles());

        /** @var UserPerformanceProfile $storedProfile */
        $storedProfile = $storedUser->getPerformanceProfiles()->first();
        $storedBackSquat = $storedProfile->getMetric(PerformanceMetricKeyEnum::BACK_SQUAT_1RM);
        $storedStrictPullUp = $storedProfile->getMetric(PerformanceMetricKeyEnum::STRICT_PULL_UP);

        self::assertNotNull($storedBackSquat);
        self::assertSame(PerformanceMetricCategoryEnum::STRENGTH, $storedBackSquat->getCategory());
        self::assertSame(PerformanceMetricValueTypeEnum::LOAD, $storedBackSquat->getValueType());
        self::assertSame(150.0, $storedBackSquat->getNumericValue());
        self::assertSame('kg', $storedBackSquat->getUnit());
        self::assertSame('Solid single', $storedBackSquat->getNotes());
        self::assertNotNull($storedStrictPullUp);
        self::assertTrue($storedStrictPullUp->getBooleanValue());
    }

    public function testPerformanceAnalysisRequiresStrengthWeightliftingSkillsAndThreeCardioMetrics(): void
    {
        $profile = new UserPerformanceProfile(
            (new User('analysis@example.com'))->setPassword('hashed-password')
        );

        self::assertFalse($profile->isEligibleForPerformanceAnalysis());

        foreach (PerformanceMetricKeyEnum::requiredStrengthMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setNumericValue(100.0);
        }
        foreach (PerformanceMetricKeyEnum::requiredWeightliftingMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setNumericValue(80.0);
        }
        foreach (PerformanceMetricKeyEnum::gymnasticsSkillMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setBooleanValue($metricKey === PerformanceMetricKeyEnum::STRICT_PULL_UP);
        }
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::ROW_500M_TIME))->setNumericValue(95.0);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::RUN_1600M_TIME))->setNumericValue(360.0);

        self::assertFalse($profile->isEligibleForPerformanceAnalysis());

        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::BIKE_ERG_20MIN_WATTS))->setNumericValue(245.0);

        self::assertTrue($profile->isEligibleForPerformanceAnalysis());
    }

    public function testGymnasticsCapacityMetricsDependOnDeclaredSkills(): void
    {
        $profile = new UserPerformanceProfile(
            (new User('skills@example.com'))->setPassword('hashed-password')
        );

        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::STRICT_PULL_UP))->setBooleanValue(true);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::KIPPING_BAR_MUSCLE_UP))->setBooleanValue(false);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::HANDSTAND_RAMP))->setBooleanValue(true);

        $availableMetrics = $profile->availableGymnasticsCapacityMetrics();

        self::assertContains(PerformanceMetricKeyEnum::MAX_STRICT_PULL_UPS, $availableMetrics);
        self::assertContains(PerformanceMetricKeyEnum::MAX_HANDSTAND_WALK_DISTANCE, $availableMetrics);
        self::assertNotContains(PerformanceMetricKeyEnum::MAX_BAR_MUSCLE_UPS, $availableMetrics);
    }
}
