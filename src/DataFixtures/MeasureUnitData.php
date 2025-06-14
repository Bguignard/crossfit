<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\MeasureUnit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MeasureUnitData extends Fixture
{
    public const string MEASURE_UNIT_METER = 'measure-unit-meter';
    public const string MEASURE_UNIT_CENTIMETER = 'measure-unit-centimeter';
    public const string MEASURE_UNIT_REPETITION = 'measure-unit-repetition';
    public const string MEASURE_UNIT_SECOND = 'measure-unit-second';
    public const string MEASURE_UNIT_KILOGRAM = 'measure-unit-kilogram';
    public const string MEASURE_UNIT_CALORIE = 'measure-unit-calorie';
    public const string MEASURE_UNIT_KILOMETER = 'measure-unit-kilometer';
    public const string MEASURE_UNIT_MINUTE = 'measure-unit-minute';
    public const string MEASURE_UNIT_HOUR = 'measure-unit-hour';
    public const string MEASURE_UNIT_PERCENT = 'measure-unit-percent';
    public const string MEASURE_UNIT_RPE = 'measure-unit-RPE';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMeasureUnits() as $reference => $measureUnit) {
            $measureUnitEntity = new MeasureUnit($measureUnit);
            $manager->persist($measureUnitEntity);
            $this->addReference($reference, $measureUnitEntity);
        }
        $manager->flush();
    }

    private function getMeasureUnits(): array
    {
        return [
            self::MEASURE_UNIT_METER => MeasureUnitEnum::METER,
            self::MEASURE_UNIT_CENTIMETER => MeasureUnitEnum::CENTIMETER,
            self::MEASURE_UNIT_REPETITION => MeasureUnitEnum::REPETITION,
            self::MEASURE_UNIT_SECOND => MeasureUnitEnum::SECOND,
            self::MEASURE_UNIT_KILOGRAM => MeasureUnitEnum::KILOGRAM,
            self::MEASURE_UNIT_CALORIE => MeasureUnitEnum::CALORIE,
            self::MEASURE_UNIT_KILOMETER => MeasureUnitEnum::KILOMETER,
            self::MEASURE_UNIT_MINUTE => MeasureUnitEnum::MINUTE,
            self::MEASURE_UNIT_HOUR => MeasureUnitEnum::HOUR,
            self::MEASURE_UNIT_PERCENT => MeasureUnitEnum::PERCENT,
            self::MEASURE_UNIT_RPE => MeasureUnitEnum::RPE,
        ];
    }
}
