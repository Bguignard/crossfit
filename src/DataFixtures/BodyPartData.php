<?php

namespace App\DataFixtures;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\BodyPartEnum;
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

    public function load(ObjectManager $manager): void
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
            self::BODY_PART_PECTORALS => BodyPartEnum::PECTORALS,
            self::BODY_PART_LOWER_BACK => BodyPartEnum::LOWER_BACK,
            self::BODY_PART_SHOULDERS => BodyPartEnum::SHOULDERS,
            self::BODY_PART_BICEPS => BodyPartEnum::BICEPS,
            self::BODY_PART_TRICEPS => BodyPartEnum::TRICEPS,
            self::BODY_PART_LEGS => BodyPartEnum::LEGS,
            self::BODY_PART_ABDOMINALS => BodyPartEnum::ABDOMINALS,
            self::BODY_PART_GLUTES => BodyPartEnum::GLUTES,
            self::BODY_PART_HAMSTRINGS => BodyPartEnum::HAMSTRINGS,
            self::BODY_PART_QUADRICEPS => BodyPartEnum::QUADRICEPS,
            self::BODY_PART_TRAPEZIUS => BodyPartEnum::TRAPEZIUS,
            self::BODY_PART_CALVES => BodyPartEnum::CALVES,
            self::BODY_PART_RHOMBOIDS => BodyPartEnum::RHOMBOIDS,
            self::BODY_PART_FOREARMS => BodyPartEnum::FOREARMS,
            self::BODY_PART_LATISSIMUS_DORSI => BodyPartEnum::LATISSIMUS_DORSI,
            self::BODY_PART_HIP_FLEXORS => BodyPartEnum::HIP_FLEXORS,
        ];
    }

    public function getByReference(string $reference): BodyPart
    {
        return $this->getReference($reference);
    }
}
