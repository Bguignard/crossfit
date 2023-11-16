<?php

namespace App\DataFixtures;

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
    public const PULL_UP_BAR = 'implement-pull-up-bar';
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
        foreach ($this->getImplements() as $implement) {
            $implement = new Implement($implement['name']);
            $manager->persist($implement);
            $this->addReference($implement['reference'], $implement);
        }
        $manager->flush();
    }

    private function getImplements(): array
    {
        return [
            [
                'reference' => self::IMPLEMENT_BARBELL,
                'name' => 'Barbell',
            ],
            [
                'reference' => self::IMPLEMENT_DUMBBELL,
                'name' => 'Dumbbell',
            ],
            [
                'reference' => self::IMPLEMENT_KETTLEBELL,
                'name' => 'Kettlebell',
            ],
            [
                'reference' => self::IMPLEMENT_ASSAULT_BIKE,
                'name' => 'Assault bike',
            ],
            [
                'reference' => self::IMPLEMENT_SKI_ERG,
                'name' => 'Ski erg',
            ],
            [
                'reference' => self::IMPLEMENT_BIKE_ERG,
                'name' => 'Bike erg',
            ],
            [
                'reference' => self::IMPLEMENT_ROWER,
                'name' => 'Row',
            ],
            [
                'reference' => self::PULL_UP_BAR,
                'name' => 'Pull up bar',
            ],
            [
                'reference' => self::IMPLEMENT_MEDICINE_BALL,
                'name' => 'Wall ball',
            ],
            [
                'reference' => self::IMPLEMENT_BOX,
                'name' => 'Box',
            ],
            [
                'reference' => self::IMPLEMENT_JUMP_ROPE,
                'name' => 'Jump rope',
            ],
            [
                'reference' => self::IMPLEMENT_BENCH,
                'name' => 'Bench',
            ],
            [
                'reference' => self::IMPLEMENT_ROPE,
                'name' => 'Rope',
            ],
            [
                'reference' => self::IMPLEMENT_DOUBLE_KETTLEBELLS,
                'name' => 'Double kettlebells',
            ],
            [
                'reference' => self::IMPLEMENT_DOUBLE_DUMBBELLS,
                'name' => 'Double dumbbells',
            ],
            [
                'reference' => self::IMPLEMENT_ECHO_BIKE,
                'name' => 'Echo bike',
            ],
            [
                'reference' => self::IMPLEMENT_PLATE,
                'name' => 'Plate',
            ],
            [
                'reference' => self::IMPLEMENT_PARALLETTE,
                'name' => 'Parallette',
            ],
            [
                'reference' => self::IMPLEMENT_SLAM_BALL,
                'name' => 'Slam ball',
            ],
            [
                'reference' => self::IMPLEMENT_SLED,
                'name' => 'Sled',
            ],
            [
                'reference' => self::IMPLEMENT_TIRE,
                'name' => 'Tire',
            ],
            [
                'reference' => self::IMPLEMENT_HAMMER,
                'name' => 'Hammer',
            ],
            [
                'reference' => self::IMPLEMENT_SLEDGE,
                'name' => 'Sledge',
            ],
            [
                'reference' => self::IMPLEMENT_SAND_BAG,
                'name' => 'Sand bag',
            ],
            [
                'reference' => self::IMPLEMENT_HUSAFELL_BAG,
                'name' => 'Husafell bag',
            ],
            [
                'reference' => self::IMPLEMENT_YOKE,
                'name' => 'Yoke',
            ],
            [
                'reference' => self::IMPLEMENT_HEAVY_JUMP_ROPE,
                'name' => 'Heavy jump rope',
            ],
            [
                'reference' => self::IMPLEMENT_RINGS,
                'name' => 'Rings',
            ],
            [
                'reference' => self::IMPLEMENT_WORM,
                'name' => 'Worm',
            ],
            [
                'reference' => self::IMPLEMENT_BAND,
                'name' => 'Band',
            ],
            [
                'reference' => self::IMPLEMENT_STICK,
                'name' => 'Stick',
            ],
            [
                'reference' => self::IMPLEMENT_AXLE_BARBELL,
                'name' => 'Axle barbell',
            ],
            [
                'reference' => self::IMPLEMENT_PIG,
                'name' => 'Pig',
            ],
        ];
    }
}
