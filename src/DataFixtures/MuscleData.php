<?php

namespace App\DataFixtures;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\MuscleEnum;
use App\Entity\Workout\Muscle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MuscleData extends Fixture implements DependentFixtureInterface
{
    public const string MUSCLE_TRAPEZIUS = 'muscle-trapezius';
    public const string MUSCLE_DELTOIDS = 'muscle-deltoids';
    public const string MUSCLE_RHOMBOIDS = 'muscle-rhomboids';
    public const string MUSCLE_BICEPS = 'muscle-biceps';
    public const string MUSCLE_TRICEPS = 'muscle-triceps';
    public const string MUSCLE_FOREARMS = 'muscle-forearms';
    public const string MUSCLE_PECTORALS = 'muscle-pectorals';
    public const string MUSCLE_RECTUS_ABDOMINIS = 'muscle-rectus-abdominis';
    public const string MUSCLE_OBLIQUES = 'muscle-obliques';
    public const string MUSCLE_TRANSVERSUS_ABDOMINIS = 'muscle-transversus-abdominis';
    public const string MUSCLE_HIP_FLEXORS = 'muscle-hip flexors';
    public const string MUSCLE_LATISSIMUS_DORSI = 'muscle-latissimus-dorsi';
    public const string MUSCLE_SPINAL_ERECTORS = 'muscle-spinal-erectors';
    public const string MUSCLE_GLUTEUS_MAXIMUS = 'muscle-gluteus-maximus';
    public const string MUSCLE_GLUTEUS_MEDIUS = 'muscle-gluteus-medius';
    public const string MUSCLE_HAMSTRINGS = 'muscle-hamstrings';
    public const string MUSCLE_QUADRICEPS = 'muscle-quadriceps';
    public const string MUSCLE_CALVES = 'muscle-calves';

    public function getDependencies(): array
    {
        return [
            BodyPartData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMuscles() as $reference => $muscle) {
            $muscleEntity = new Muscle($muscle['name'], $this->getReference($muscle['bodyPart'], BodyPart::class));
            $manager->persist($muscleEntity);
            $this->addReference($reference, $muscleEntity);
        }
        $manager->flush();
    }

    private function getMuscles(): array
    {
        return [
            self::MUSCLE_TRAPEZIUS => [
                'name' => MuscleEnum::TRAPEZIUS,
                'bodyPart' => BodyPartData::BODY_PART_SHOULDERS,
            ],
            self::MUSCLE_DELTOIDS => [
                'name' => MuscleEnum::DELTOIDS,
                'bodyPart' => BodyPartData::BODY_PART_SHOULDERS,
            ],
            self::MUSCLE_RHOMBOIDS => [
                'name' => MuscleEnum::RHOMBOIDS,
                'bodyPart' => BodyPartData::BODY_PART_UPPER_BACK,
            ],
            self::MUSCLE_BICEPS => [
                'name' => MuscleEnum::BICEPS,
                'bodyPart' => BodyPartData::BODY_PART_ARMS,
            ],
            self::MUSCLE_TRICEPS => [
                'name' => MuscleEnum::TRICEPS,
                'bodyPart' => BodyPartData::BODY_PART_ARMS,
            ],
            self::MUSCLE_FOREARMS => [
                'name' => MuscleEnum::FOREARMS,
                'bodyPart' => BodyPartData::BODY_PART_FOREARMS,
            ],
            self::MUSCLE_PECTORALS => [
                'name' => MuscleEnum::PECTORALS,
                'bodyPart' => BodyPartData::BODY_PART_CHEST,
            ],
            self::MUSCLE_RECTUS_ABDOMINIS => [
                'name' => MuscleEnum::RECTUS_ABDOMINIS,
                'bodyPart' => BodyPartData::BODY_PART_ABS,
            ],
            self::MUSCLE_OBLIQUES => [
                'name' => MuscleEnum::OBLIQUES,
                'bodyPart' => BodyPartData::BODY_PART_ABS,
            ],
            self::MUSCLE_TRANSVERSUS_ABDOMINIS => [
                'name' => MuscleEnum::TRANSVERSUS_ABDOMINIS,
                'bodyPart' => BodyPartData::BODY_PART_ABS,
            ],
            self::MUSCLE_HIP_FLEXORS => [
                'name' => MuscleEnum::HIP_FLEXORS,
                'bodyPart' => BodyPartData::BODY_PART_ABS,
            ],
            self::MUSCLE_LATISSIMUS_DORSI => [
                'name' => MuscleEnum::LATISSIMUS_DORSI,
                'bodyPart' => BodyPartData::BODY_PART_UPPER_BACK,
            ],
            self::MUSCLE_SPINAL_ERECTORS => [
                'name' => MuscleEnum::SPINAL_ERECTORS,
                'bodyPart' => BodyPartData::BODY_PART_LOWER_BACK,
            ],
            self::MUSCLE_GLUTEUS_MAXIMUS => [
                'name' => MuscleEnum::GLUTEUS_MAXIMUS,
                'bodyPart' => BodyPartData::BODY_PART_GLUTES,
            ],
            self::MUSCLE_GLUTEUS_MEDIUS => [
                'name' => MuscleEnum::GLUTEUS_MEDIUS,
                'bodyPart' => BodyPartData::BODY_PART_GLUTES,
            ],
            self::MUSCLE_HAMSTRINGS => [
                'name' => MuscleEnum::HAMSTRINGS,
                'bodyPart' => BodyPartData::BODY_PART_LEGS,
            ],
            self::MUSCLE_QUADRICEPS => [
                'name' => MuscleEnum::QUADRICEPS,
                'bodyPart' => BodyPartData::BODY_PART_LEGS,
            ],
            self::MUSCLE_CALVES => [
                'name' => MuscleEnum::CALVES,
                'bodyPart' => BodyPartData::BODY_PART_LEGS,
            ],
        ];
    }

    public function getByReference(string $reference): Muscle
    {
        return $this->getReference($reference, Muscle::class);
    }
}
