<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Entity\Workout\Implement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ImplementData extends Fixture
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

    public function load(ObjectManager $manager)
    {
        foreach ($this->getImplements() as $reference => $implementEnum) {
            $implementObject = new Implement($implementEnum['name'], $implementEnum['typeOfAdjustableMeasure']);
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
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_DUMBBELL => [
                'name' => ImplementEnum::DUMBBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_KETTLEBELL => [
                'name' => ImplementEnum::KETTLEBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_ASSAULT_BIKE => [
                'name' => ImplementEnum::ASSAULT_BIKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_SKI_ERG => [
                'name' => ImplementEnum::SKI_ERG,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_BIKE_ERG => [
                'name' => ImplementEnum::BIKE_ERG,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_ROWER => [
                'name' => ImplementEnum::ROWER,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_PULL_UP_BAR => [
                'name' => ImplementEnum::PULL_UP_BAR,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_MEDICINE_BALL => [
                'name' => ImplementEnum::MEDICINE_BALL,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_BOX => [
                'name' => ImplementEnum::BOX,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::HEIGHT,
                ],
            self::IMPLEMENT_JUMP_ROPE => [
                'name' => ImplementEnum::JUMP_ROPE,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_BENCH => [
                'name' => ImplementEnum::BENCH,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_ROPE => [
                'name' => ImplementEnum::ROPE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::HEIGHT,
                ],
            self::IMPLEMENT_DOUBLE_KETTLEBELLS => [
                'name' => ImplementEnum::DOUBLE_KETTLEBELLS,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_DOUBLE_DUMBBELLS => [
                'name' => ImplementEnum::DOUBLE_DUMBBELLS,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_ECHO_BIKE => [
                'name' => ImplementEnum::ECHO_BIKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_PLATE => [
                'name' => ImplementEnum::PLATE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_PARALLETTE => [
                'name' => ImplementEnum::PARALLETTE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::HEIGHT,
                ],
            self::IMPLEMENT_SLAM_BALL => [
                'name' => ImplementEnum::SLAM_BALL,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_SLED => [
                'name' => ImplementEnum::SLED,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_TIRE => [
                'name' => ImplementEnum::TIRE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_HAMMER => [
                'name' => ImplementEnum::HAMMER,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_SLEDGE => [
                'name' => ImplementEnum::SLEDGE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_SAND_BAG => [
                'name' => ImplementEnum::SAND_BAG,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_HUSAFELL_BAG => [
                'name' => ImplementEnum::HUSAFELL_BAG,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_YOKE => [
                'name' => ImplementEnum::YOKE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_HEAVY_JUMP_ROPE => [
                'name' => ImplementEnum::HEAVY_JUMP_ROPE,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_RINGS => [
                'name' => ImplementEnum::RINGS,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_WORM => [
                'name' => ImplementEnum::WORM,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_BAND => [
                'name' => ImplementEnum::BAND,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                ],
            self::IMPLEMENT_STICK => [
                'name' => ImplementEnum::STICK,
                'typeOfAdjustableMeasure' => null,
                ],
            self::IMPLEMENT_AXLE_BARBELL => [
                'name' => ImplementEnum::AXLE_BARBELL,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
            self::IMPLEMENT_PIG => [
                'name' => ImplementEnum::PIG,
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                ],
        ];
    }
}
