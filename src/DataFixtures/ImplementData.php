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
    public const string IMPLEMENT_BARBELL = 'implement-barbell';
    public const string IMPLEMENT_DUMBBELL = 'implement-dumbbell';
    public const string IMPLEMENT_KETTLEBELL = 'implement-kettlebell';
    public const string IMPLEMENT_ASSAULT_BIKE = 'implement-assault-bike';
    public const string IMPLEMENT_SKI_ERG = 'implement-ski-erg';
    public const string IMPLEMENT_BIKE_ERG = 'implement-bike-erg';
    public const string IMPLEMENT_ROWER = 'implement-rower';
    public const string IMPLEMENT_PULL_UP_BAR = 'implement-pull-up-bar';
    public const string IMPLEMENT_MEDICINE_BALL = 'implement-medicine-ball';
    public const string IMPLEMENT_BOX = 'implement-box';
    public const string IMPLEMENT_JUMP_ROPE = 'implement-jump-rope';
    public const string IMPLEMENT_BENCH = 'implement-bench';
    public const string IMPLEMENT_ROPE = 'implement-rope';
    public const string IMPLEMENT_DOUBLE_KETTLEBELLS = 'implement-double-kettlebells';
    public const string IMPLEMENT_DOUBLE_DUMBBELLS = 'implement-double-dumbbells';
    public const string IMPLEMENT_ECHO_BIKE = 'implement-echo-bike';
    public const string IMPLEMENT_PLATE = 'implement-plate';
    public const string IMPLEMENT_PARALLETTE = 'implement-parallette';
    public const string IMPLEMENT_SLAM_BALL = 'implement-slam-ball';
    public const string IMPLEMENT_SLED = 'implement-sled';
    public const string IMPLEMENT_TIRE = 'implement-tire';
    public const string IMPLEMENT_HAMMER = 'implement-hammer';
    public const string IMPLEMENT_SLEDGE = 'implement-sledge';
    public const string IMPLEMENT_SAND_BAG = 'implement-sand-bag';
    public const string IMPLEMENT_HUSAFELL_BAG = 'implement-husafell-bag';
    public const string IMPLEMENT_YOKE = 'implement-yoke';
    public const string IMPLEMENT_HEAVY_JUMP_ROPE = 'implement-heavy-jump-rope';
    public const string IMPLEMENT_RINGS = 'implement-rings';
    public const string IMPLEMENT_WORM = 'implement-worm';
    public const string IMPLEMENT_BAND = 'implement-band';
    public const string IMPLEMENT_STICK = 'implement-stick';
    public const string IMPLEMENT_AXLE_BARBELL = 'implement-axle-barbell';
    public const string IMPLEMENT_PIG = 'implement-pig';
    public const string IMPLEMENT_LINE = 'implement-line';
    public const string IMPLEMENT_GHD = 'implement-ghd';
    public const string IMPLEMENT_BIKE = 'implement-bike';
    public const string IMPLEMENT_PADDLE = 'implement-paddle';
    public const string IMPLEMENT_WEIGHTED_VEST = 'implement-weighted-vest';
    public const string IMPLEMENT_PARTNER = 'implement-partner';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getImplements() as $reference => $implementEnum) {
            $typeOfAdjustableMeasure = $implementEnum['typeOfAdjustableMeasure'] !== null ? $this->getReference($implementEnum['typeOfAdjustableMeasure'], ImplementTypeOfAdjustableMeasureUnit::class) : null;
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
            self::IMPLEMENT_WEIGHTED_VEST => [
                'name' => ImplementEnum::WEIGHTED_VEST,
                'typeOfAdjustableMeasure' => ImplementTypeOfAdjustableMeasureUnitData::IMPLEMENT_ADJUSTABLE_WEIGHT,
            ],
            self::IMPLEMENT_PARTNER => [
                'name' => ImplementEnum::PARTNER,
                'typeOfAdjustableMeasure' => null,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            ImplementTypeOfAdjustableMeasureUnitData::class,
        ];
    }
}
