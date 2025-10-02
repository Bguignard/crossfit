<?php

namespace App\DataFixtures;

use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WorkoutOriginData extends Fixture implements DependentFixtureInterface
{
    public const string WORKOUT_ORIGIN_GIRLS = 'workout_origin_girls';
    public const string WORKOUT_ORIGIN_HERO = 'workout_origin_hero';

    public function getDependencies(): array
    {
        return [
            WorkoutOriginNameData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkoutOrigin() as $reference => $workoutOrigin) {
            $workoutOrigin = new WorkoutOrigin(
                $this->getReference($workoutOrigin['name'], WorkoutOriginName::class),
                $workoutOrigin['year'],
            );
            $manager->persist($workoutOrigin);
            $this->addReference($reference, $workoutOrigin);
        }
        $manager->flush();
    }

    private function getWorkoutOrigin(): array
    {
        return [
            self::WORKOUT_ORIGIN_GIRLS => [
                'name' => WorkoutOriginNameData::WORKOUT_ORIGIN_NAME_GIRLS_WORKOUT,
                'year' => null,
            ],
            self::WORKOUT_ORIGIN_HERO => [
                'name' => WorkoutOriginNameData::WORKOUT_ORIGIN_NAME_HERO_WORKOUT,
                'year' => null,
            ],
        ];
    }
}
