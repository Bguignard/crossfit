<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ImplementData extends Fixture implements DependentFixtureInterface
{
    public const IMPLEMENT_BARBELL = 'implement-barbell';
    public const IMPLEMENT_DUMBBELL = 'implement-dumbbell';
    public const IMPLEMENT_KETTLEBELL = 'implement-kettlebell';
    public const IMPLEMENT_ASSAULT_BIKE = 'implement-assault-bike';
    public const IMPLEMENT_SKI_ERG = 'implement-ski-erg';
    public const IMPLEMENT_BIKE_ERG = 'implement-bike-erg';
    public const IMPLEMENT_ROWER = 'implement-rower';
    public const IMPLEMENT_PULL_UP_BAR = 'implement-pull-up-bar';
    public const IMPLEMENT_MEDICINE_BALL = 'implement-medicine-ball';
    public const IMPLEMENT_BOX = 'implement-box';
    public const IMPLEMENT_JUMP_ROPE = 'implement-jump-rope';
    public const IMPLEMENT_BENCH = 'implement-bench';
    public const IMPLEMENT_ROPE = 'implement-rope';
    public const IMPLEMENT_DOUBLE_KETTLEBELLS = 'implement-double-kettlebells';
    public const IMPLEMENT_DOUBLE_DUMBBELLS = 'implement-double-dumbbells';
    public const IMPLEMENT_ECHO_BIKE = 'implement-echo-bike';
    public const IMPLEMENT_PLATE = 'implement-plate';
    public const IMPLEMENT_PARALLETTE = 'implement-parallette';
    public const IMPLEMENT_SLAM_BALL = 'implement-slam-ball';
    public const IMPLEMENT_SLED = 'implement-sled';
    public const IMPLEMENT_TIRE = 'implement-tire';
    public const IMPLEMENT_HAMMER = 'implement-hammer';
    public const IMPLEMENT_SLEDGE = 'implement-sledge';
    public const IMPLEMENT_SAND_BAG = 'implement-sand-bag';
    public const IMPLEMENT_HUSAFELL_BAG = 'implement-husafell-bag';
    public const IMPLEMENT_YOKE = 'implement-yoke';
    public const IMPLEMENT_HEAVY_JUMP_ROPE = 'implement-heavy-jump-rope';
    public const IMPLEMENT_RINGS = 'implement-rings';
    public const IMPLEMENT_WORM = 'implement-worm';
    public const IMPLEMENT_BAND = 'implement-band';
    public const IMPLEMENT_STICK = 'implement-stick';
    public const IMPLEMENT_AXLE_BARBELL = 'implement-axle-barbell';
    public const IMPLEMENT_PIG = 'implement-pig';
    public const IMPLEMENT_LINE = 'implement-line';
    public const IMPLEMENT_GHD = 'implement-ghd';
    public const IMPLEMENT_BIKE = 'implement-bike';
    public const IMPLEMENT_PADDLE = 'implement-paddle';

    public function load(ObjectManager $manager)
    {
        foreach ($this->getImplements() as $reference => $implementEnum) {
            $typeOfAdjustableMeasure = null !== $implementEnum['typeOfAdjustableMeasure'] ? $this->getReference($implementEnum['typeOfAdjustableMeasure'], ImplementTypeOfAdjustableMeasureUnit::class) : null;
            $implementObject = new Implement($implementEnum['name'], $typeOfAdjustableMeasure);
            $manager->persist($implementObject);
            $this->addReference($reference, $implementObject);
        }
        $manager->flush();
    }

    private function getImplements(): array
    {
        return [
            self::IMPLEMENT_BARBELL => [
                'name' => ImplementEnum::BARBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_DUMBBELL => [
                'name' => ImplementEnum::DUMBBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_KETTLEBELL => [
                'name' => ImplementEnum::KETTLEBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_ASSAULT_BIKE => [
                'name' => ImplementEnum::ASSAULT_BIKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_SKI_ERG => [
                'name' => ImplementEnum::SKI_ERG,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_BIKE_ERG => [
                'name' => ImplementEnum::BIKE_ERG,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_ROWER => [
                'name' => ImplementEnum::ROWER,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_PULL_UP_BAR => [
                'name' => ImplementEnum::PULL_UP_BAR,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_MEDICINE_BALL => [
                'name' => ImplementEnum::MEDICINE_BALL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_BOX => [
                'name' => ImplementEnum::BOX,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_HEIGHT,
                ],
            self::IMPLEMENT_JUMP_ROPE => [
                'name' => ImplementEnum::JUMP_ROPE,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_BENCH => [
                'name' => ImplementEnum::BENCH,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_ROPE => [
                'name' => ImplementEnum::ROPE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_HEIGHT,
                ],
            self::IMPLEMENT_DOUBLE_KETTLEBELLS => [
                'name' => ImplementEnum::DOUBLE_KETTLEBELLS,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_DOUBLE_DUMBBELLS => [
                'name' => ImplementEnum::DOUBLE_DUMBBELLS,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_ECHO_BIKE => [
                'name' => ImplementEnum::ECHO_BIKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_PLATE => [
                'name' => ImplementEnum::PLATE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_PARALLETTE => [
                'name' => ImplementEnum::PARALLETTE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_HEIGHT,
                ],
            self::IMPLEMENT_SLAM_BALL => [
                'name' => ImplementEnum::SLAM_BALL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_SLED => [
                'name' => ImplementEnum::SLED,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_TIRE => [
                'name' => ImplementEnum::TIRE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_HAMMER => [
                'name' => ImplementEnum::HAMMER,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_SLEDGE => [
                'name' => ImplementEnum::SLEDGE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_SAND_BAG => [
                'name' => ImplementEnum::SAND_BAG,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_HUSAFELL_BAG => [
                'name' => ImplementEnum::HUSAFELL_BAG,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_YOKE => [
                'name' => ImplementEnum::YOKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_HEAVY_JUMP_ROPE => [
                'name' => ImplementEnum::HEAVY_JUMP_ROPE,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_RINGS => [
                'name' => ImplementEnum::RINGS,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_WORM => [
                'name' => ImplementEnum::WORM,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_BAND => [
                'name' => ImplementEnum::BAND,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_RESISTANCE,
                ],
            self::IMPLEMENT_STICK => [
                'name' => ImplementEnum::STICK,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_AXLE_BARBELL => [
                'name' => ImplementEnum::AXLE_BARBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_PIG => [
                'name' => ImplementEnum::PIG,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
                ],
            self::IMPLEMENT_LINE => [
                'name' => ImplementEnum::LINE,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_GHD => [
                'name' => ImplementEnum::GHD,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_BIKE => [
                'name' => ImplementEnum::BIKE,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_PADDLE => [
                'name' => ImplementEnum::PADDLE,
                'typeOfAdjustableMeasure' => null,
                ],
        ];
    }

    public function getDependencies()
    {
        return [
            ImplementTypeOfAdjustableMeasureUnitData::class,
        ];
    }
}
