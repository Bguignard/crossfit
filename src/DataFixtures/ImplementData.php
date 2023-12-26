<?php

namespace App\DataFixtures;

use App\Entity\Workout\Implement;
use App\Enum\ImplementEnum;
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
            $implementObject = new Implement($implementEnum);
            $manager->persist($implementObject);
            $this->addReference($reference, $implementObject);
        }
        $manager->flush();
    }

    private function getImplements(): array
    {
        return [
            self::IMPLEMENT_BARBELL => ImplementEnum::BARBELL,
            self::IMPLEMENT_DUMBBELL => ImplementEnum::DUMBBELL,
            self::IMPLEMENT_KETTLEBELL => ImplementEnum::KETTLEBELL,
            self::IMPLEMENT_ASSAULT_BIKE => ImplementEnum::ASSAULT_BIKE,
            self::IMPLEMENT_SKI_ERG => ImplementEnum::SKI_ERG,
            self::IMPLEMENT_BIKE_ERG => ImplementEnum::BIKE_ERG,
            self::IMPLEMENT_ROWER => ImplementEnum::ROWER,
            self::IMPLEMENT_PULL_UP_BAR => ImplementEnum::PULL_UP_BAR,
            self::IMPLEMENT_MEDICINE_BALL => ImplementEnum::MEDICINE_BALL,
            self::IMPLEMENT_BOX => ImplementEnum::BOX,
            self::IMPLEMENT_JUMP_ROPE => ImplementEnum::JUMP_ROPE,
            self::IMPLEMENT_BENCH => ImplementEnum::BENCH,
            self::IMPLEMENT_ROPE => ImplementEnum::ROPE,
            self::IMPLEMENT_DOUBLE_KETTLEBELLS => ImplementEnum::DOUBLE_KETTLEBELLS,
            self::IMPLEMENT_DOUBLE_DUMBBELLS => ImplementEnum::DOUBLE_DUMBBELLS,
            self::IMPLEMENT_ECHO_BIKE => ImplementEnum::ECHO_BIKE,
            self::IMPLEMENT_PLATE => ImplementEnum::PLATE,
            self::IMPLEMENT_PARALLETTE => ImplementEnum::PARALLETTE,
            self::IMPLEMENT_SLAM_BALL => ImplementEnum::SLAM_BALL,
            self::IMPLEMENT_SLED => ImplementEnum::SLED,
            self::IMPLEMENT_TIRE => ImplementEnum::TIRE,
            self::IMPLEMENT_HAMMER => ImplementEnum::HAMMER,
            self::IMPLEMENT_SLEDGE => ImplementEnum::SLEDGE,
            self::IMPLEMENT_SAND_BAG => ImplementEnum::SAND_BAG,
            self::IMPLEMENT_HUSAFELL_BAG => ImplementEnum::HUSAFELL_BAG,
            self::IMPLEMENT_YOKE => ImplementEnum::YOKE,
            self::IMPLEMENT_HEAVY_JUMP_ROPE => ImplementEnum::HEAVY_JUMP_ROPE,
            self::IMPLEMENT_RINGS => ImplementEnum::RINGS,
            self::IMPLEMENT_WORM => ImplementEnum::WORM,
            self::IMPLEMENT_BAND => ImplementEnum::BAND,
            self::IMPLEMENT_STICK => ImplementEnum::STICK,
            self::IMPLEMENT_AXLE_BARBELL => ImplementEnum::AXLE_BARBELL,
            self::IMPLEMENT_PIG => ImplementEnum::PIG,
        ];
    }
}
