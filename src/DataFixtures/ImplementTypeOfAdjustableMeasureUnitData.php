<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ImplementTypeOfAdjustableMeasureUnitData extends Fixture
{
    public const WEIGHT_KILOGRAM = 'weight-kilogram';
    public const DISTANCE_METER = 'distance-meter';
    public const DISTANCE_KILOMETER = 'distance-kilometer';
    public const HEIGHT_CENTIMETER = 'height-centimeter';
    public const PERCENTAGE_OF_1_RM_PERCENT = 'percentage-of-1-rm-percent';
    public const ENERGY_CALORIE = 'energy-calorie';
    public const RESISTANCE_PERCENT = 'resistance-percent';
    public const DIFFICULTY_RPE = 'difficulty-rpe';

    public function load(ObjectManager $manager)
    {
        foreach ($this->getImplementTypeOfAdjustableMeasureUnit() as $reference => $implementTypesUnit) {
            $implementObject = new ImplementTypeOfAdjustableMeasureUnit($implementTypesUnit['typeOfAdjustableMeasure'], $implementTypesUnit['measureUnit']);
            $manager->persist($implementObject);
            $this->addReference($reference, $implementObject);
        }
        $manager->flush();
    }

    private function getImplementTypeOfAdjustableMeasureUnit(): array
    {
        return [
            self::WEIGHT_KILOGRAM => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                'measureUnit' => MeasureUnitEnum::KILOGRAM,
                ],
            self::DISTANCE_METER => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::DISTANCE,
                'measureUnit' => MeasureUnitEnum::METER,
                ],
            self::DISTANCE_KILOMETER => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::DISTANCE,
                'measureUnit' => MeasureUnitEnum::KILOMETER,
                ],
            self::HEIGHT_CENTIMETER => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::HEIGHT,
                'measureUnit' => MeasureUnitEnum::CENTIMETER,
                ],
            self::PERCENTAGE_OF_1_RM_PERCENT => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::PERCENTAGE_OF_1_RM,
                'measureUnit' => MeasureUnitEnum::PERCENT,
                ],
            self::ENERGY_CALORIE => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::ENERGY,
                'measureUnit' => MeasureUnitEnum::CALORIE,
                ],
            self::RESISTANCE_PERCENT => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                'measureUnit' => MeasureUnitEnum::PERCENT,
                ],
            self::DIFFICULTY_RPE => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::DIFFICULTY,
                'measureUnit' => MeasureUnitEnum::RPE,
                ],
        ];
    }
}
