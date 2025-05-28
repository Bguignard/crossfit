<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\WorkoutOriginName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorkoutOriginNameData extends Fixture
{
    public const string WORKOUT_ORIGIN_NAME_CROSSFIT_GAMES = 'workout-origin-name-crossfit-games';
    public const string WORKOUT_ORIGIN_NAME_CROSSFIT_REGIONALS = 'workout-origin-name-crossfit-regionals';
    public const string WORKOUT_ORIGIN_NAME_CROSSFIT_SEMIFINALS = 'workout-origin-name-crossfit-semifinals';
    public const string WORKOUT_ORIGIN_NAME_CROSSFIT_QUARTERFINALS = 'workout-origin-name-crossfit-quarterfinals';
    public const string WORKOUT_ORIGIN_NAME_CROSSFIT_OPEN_WORKOUT = 'workout-origin-name-crossfit-open-workout';
    public const string WORKOUT_ORIGIN_NAME_GIRLS_WORKOUT = 'workout-origin-name-girls-workout';
    public const string WORKOUT_ORIGIN_NAME_HERO_WORKOUT = 'workout-origin-name-hero-workout';
    public const string WORKOUT_ORIGIN_NAME_SANCTIONAL_EVENTS = 'workout-origin-name-sanctional-events';
    public const string WORKOUT_ORIGIN_NAME_OTHER = 'workout-origin-name-other';
    public const string WORKOUT_ORIGIN_NAME_CUSTOM = 'workout-origin-name-custom';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkoutOrigins() as $reference => $workoutOriginName) {
            $bodyPartEntity = new WorkoutOriginName($workoutOriginName);
            $manager->persist($bodyPartEntity);
            $this->addReference($reference, $bodyPartEntity);
        }
        $manager->flush();
    }

    private function getWorkoutOrigins(): array
    {
        return [
            self::WORKOUT_ORIGIN_NAME_CROSSFIT_GAMES => WorkoutOriginNameEnum::CROSSFIT_GAMES,
            self::WORKOUT_ORIGIN_NAME_CROSSFIT_REGIONALS => WorkoutOriginNameEnum::CROSSFIT_REGIONALS,
            self::WORKOUT_ORIGIN_NAME_CROSSFIT_SEMIFINALS => WorkoutOriginNameEnum::CROSSFIT_SEMIFINALS,
            self::WORKOUT_ORIGIN_NAME_CROSSFIT_QUARTERFINALS => WorkoutOriginNameEnum::CROSSFIT_QUARTERFINALS,
            self::WORKOUT_ORIGIN_NAME_CROSSFIT_OPEN_WORKOUT => WorkoutOriginNameEnum::CROSSFIT_OPEN_WORKOUT,
            self::WORKOUT_ORIGIN_NAME_GIRLS_WORKOUT => WorkoutOriginNameEnum::GIRLS_WORKOUT,
            self::WORKOUT_ORIGIN_NAME_HERO_WORKOUT => WorkoutOriginNameEnum::HERO_WORKOUT,
            self::WORKOUT_ORIGIN_NAME_SANCTIONAL_EVENTS => WorkoutOriginNameEnum::SANCTIONAL_EVENTS,
            self::WORKOUT_ORIGIN_NAME_OTHER => WorkoutOriginNameEnum::OTHER,
            self::WORKOUT_ORIGIN_NAME_CUSTOM => WorkoutOriginNameEnum::CUSTOM,
        ];
    }
}
