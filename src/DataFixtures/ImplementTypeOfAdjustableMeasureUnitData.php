<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;
use App\Entity\Workout\MeasureUnit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ImplementTypeOfAdjustableMeasureUnitData extends Fixture implements DependentFixtureInterface
{
    public const string IMPLEMENT_ADJUSTABLE_WEIGHT = 'implement-adjustable-weight';
    public const string IMPLEMENT_ADJUSTABLE_DISTANCE = 'implement-adjustable-distance';
    public const string IMPLEMENT_ADJUSTABLE_HEIGHT = 'implement-adjustable-height';
    public const string IMPLEMENT_ADJUSTABLE_PERCENTAGE_OF_1_RM = 'implement-adjustable-percentage-of-1-rm';
    public const string IMPLEMENT_ADJUSTABLE_ENERGY = 'implement-adjustable-energy';
    public const string IMPLEMENT_ADJUSTABLE_RESISTANCE = 'implement-adjustable-resistance';
    public const string IMPLEMENT_ADJUSTABLE_DIFFICULTY = 'implement-adjustable-difficulty';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getImplementTypeOfAdjustableMeasureUnit() as $reference => $implementTypesUnit) {
            $implementObject = new ImplementTypeOfAdjustableMeasureUnit($implementTypesUnit['typeOfAdjustableMeasure']);
            foreach ($implementTypesUnit['measureUnit'] as $measureUnit) {
                $implementObject->addMeasureUnit($this->getReference($measureUnit, MeasureUnit::class));
            }
            $manager->persist($implementObject);
            $this->addReference($reference, $implementObject);
        }
        $manager->flush();
    }

    private function getImplementTypeOfAdjustableMeasureUnit(): array
    {
        return [
            self::IMPLEMENT_ADJUSTABLE_WEIGHT => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::WEIGHT,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_KILOGRAM,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
            self::IMPLEMENT_ADJUSTABLE_DISTANCE => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::DISTANCE,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_METER,
                    MeasureUnitData::MEASURE_UNIT_KILOMETER,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
            self::IMPLEMENT_ADJUSTABLE_HEIGHT => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::HEIGHT,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_CENTIMETER,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
            self::IMPLEMENT_ADJUSTABLE_PERCENTAGE_OF_1_RM => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::PERCENTAGE_OF_1_RM,
                'measureUnit' => [MeasureUnitData::MEASURE_UNIT_PERCENT],
            ],
            self::IMPLEMENT_ADJUSTABLE_ENERGY => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::ENERGY,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_CALORIE,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
            self::IMPLEMENT_ADJUSTABLE_RESISTANCE => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::RESISTANCE,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
            self::IMPLEMENT_ADJUSTABLE_DIFFICULTY => [
                'typeOfAdjustableMeasure' => ImplementTypeOfMeasureEnum::DIFFICULTY,
                'measureUnit' => [
                    MeasureUnitData::MEASURE_UNIT_RPE,
                    MeasureUnitData::MEASURE_UNIT_PERCENT,
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            MeasureUnitData::class,
        ];
    }
}
