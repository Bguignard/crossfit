<?php

namespace App\DataFixtures;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\BodyPartEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BodyPartData extends Fixture
{
    public const BODY_PART_LEGS = 'body-part-legs';
    public const BODY_PART_LOWER_BACK = 'body-part-lower-back';
    public const BODY_PART_UPPER_BACK = 'body-part-upper-back';
    public const BODY_PART_SHOULDERS = 'body-part-shoulders';
    public const BODY_PART_ARMS = 'body-part-arms';
    public const BODY_PART_FOREARMS = 'body-part-forearms';
    public const BODY_PART_ABS = 'body-part-abs';
    public const BODY_PART_CHEST = 'body-part-chest';
    public const BODY_PART_GLUTES = 'body-part-glutes';

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
            self::BODY_PART_LEGS => BodyPartEnum::LEGS,
            self::BODY_PART_LOWER_BACK => BodyPartEnum::LOWER_BACK,
            self::BODY_PART_UPPER_BACK => BodyPartEnum::UPPER_BACK,
            self::BODY_PART_SHOULDERS => BodyPartEnum::SHOULDERS,
            self::BODY_PART_ARMS => BodyPartEnum::ARMS,
            self::BODY_PART_FOREARMS => BodyPartEnum::FOREARMS,
            self::BODY_PART_ABS => BodyPartEnum::ABS,
            self::BODY_PART_CHEST => BodyPartEnum::CHEST,
            self::BODY_PART_GLUTES => BodyPartEnum::GLUTES,
        ];
    }
}
