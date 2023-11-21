<?php

namespace App\DataFixtures;

use App\Entity\Workout\BodyPart;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BodyPartData extends Fixture
{
    public const BODY_PART_PECTORALS = 'body-part-pectorals';
    public const BODY_PART_LOWER_BACK = 'body-part-lower-back';
    public const BODY_PART_SHOULDERS = 'body-part-shoulders';
    public const BODY_PART_BICEPS = 'body-part-biceps';
    public const BODY_PART_TRICEPS = 'body-part-triceps';
    public const BODY_PART_LEGS = 'body-part-legs';
    public const BODY_PART_ABDOMINALS = 'body-part-abdominals';
    public const BODY_PART_GLUTES = 'body-part-glutes';
    public const BODY_PART_HAMSTRINGS = 'body-part-hamstrings';
    public const BODY_PART_QUADRICEPS = 'body-part-quadriceps';
    public const BODY_PART_TRAPEZIUS = 'body-part-trapezius';
    public const BODY_PART_CALVES = 'body-part-calves';
    public const BODY_PART_RHOMBOIDS = 'body-part-rhomboids';
    public const BODY_PART_FOREARMS = 'body-part-forearms';
    public const BODY_PART_LATISSIMUS_DORSI = 'body-part-latissimus-dorsi';
    public const BODY_PART_HIP_FLEXORS = 'body-part-hip-flexors';

    public function load(ObjectManager $manager)
    {
        foreach ($this->getBodyParts() as $reference => $bodyPartName) {
            $bodyPartEntity = new BodyPart($bodyPartName);
            $manager->persist($bodyPartEntity);
            $this->addReference($reference, $bodyPartEntity);
        }
        $manager->flush();
    }

    private function getBodyParts(): array
    {
        return [
            self::BODY_PART_PECTORALS => 'Pectorals',
            self::BODY_PART_LOWER_BACK => 'Lower back',
            self::BODY_PART_SHOULDERS => 'Shoulders',
            self::BODY_PART_BICEPS => 'Biceps',
            self::BODY_PART_TRICEPS => 'Triceps',
            self::BODY_PART_LEGS => 'Legs',
            self::BODY_PART_ABDOMINALS => 'Abdominals',
            self::BODY_PART_GLUTES => 'Glutes',
            self::BODY_PART_HAMSTRINGS => 'Hamstrings',
            self::BODY_PART_QUADRICEPS => 'Quadriceps',
            self::BODY_PART_TRAPEZIUS => 'Trapezius',
            self::BODY_PART_CALVES => 'Calves',
            self::BODY_PART_RHOMBOIDS => 'Rhomboids',
            self::BODY_PART_FOREARMS => 'Forearms',
            self::BODY_PART_LATISSIMUS_DORSI => 'Latissimus dorsi',
            self::BODY_PART_HIP_FLEXORS => 'Hip flexors',
        ];
    }

    public function getByReference(string $reference): BodyPart
    {
        return $this->getReference($reference);
    }
}
