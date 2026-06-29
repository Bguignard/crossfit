<?php

namespace App\Tests;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Services\Workout\WorkoutLoadPrescriptionValidator;
use PHPUnit\Framework\TestCase;

final class WorkoutLoadPrescriptionValidatorTest extends TestCase
{
    public function testDetectsStrictToesToBarOnlyInMainFlow(): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();

        self::assertTrue($validator->containsStrictToesToBar("AMRAP 10\n12 strict toes to bar"));
        self::assertFalse($validator->containsStrictToesToBar("AMRAP 10\n12 Toes to Bar\n\nScaling options:\nRX: strict toes to bar accessory work"));
    }

    public function testAssociatesLoadsWithCompactMovementSegments(): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();
        $deadlift = $this->movement('Deadlift');
        $hangPowerClean = $this->movement('Hang Power Clean');

        self::assertSame(
            ['Hang Power Clean'],
            $this->missingNames($validator, '10 Deadlifts (100/70 kg)+10 Hang Power Cleans', [$deadlift, $hangPowerClean])
        );
        self::assertSame(
            ['Hang Power Clean'],
            $this->missingNames($validator, '10 Deadlifts (100/70 kg), 10 Hang Power Cleans', [$deadlift, $hangPowerClean])
        );
        self::assertSame(
            ['Hang Power Clean'],
            $this->missingNames($validator, '10 Deadlifts (100/70 kg) then 10 Hang Power Cleans', [$deadlift, $hangPowerClean])
        );
    }

    public function testDoesNotSplitCleanAndJerkWhenAssociatingLoads(): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();
        $cleanAndJerk = $this->movement('Clean and Jerk');
        $deadlift = $this->movement('Deadlift');

        self::assertSame(
            ['Clean and Jerk'],
            $this->missingNames($validator, '10 Clean and Jerks + 10 Deadlifts (100/70 kg)', [$cleanAndJerk, $deadlift])
        );
        self::assertSame(
            [],
            $this->missingNames($validator, '10 Clean and Jerks (100/70 kg)', [$cleanAndJerk])
        );
    }

    /**
     * @dataProvider loadPrescriptionFlowProvider
     */
    public function testAcceptsCommonLoadPrescriptionFormats(string $flow): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();
        $dumbbellSnatch = $this->movement('Dumbbell Snatch', ImplementEnum::DUMBBELL);

        self::assertSame([], $this->missingNames($validator, $flow, [$dumbbellSnatch]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function loadPrescriptionFlowProvider(): iterable
    {
        yield 'kg pair' => ['10 Dumbbell Snatches (100/70 kg)'];
        yield 'decimal comma' => ['10 Dumbbell Snatches (22,5/15 kg)'];
        yield 'decimal point' => ['10 Dumbbell Snatches (22.5/15 kg)'];
        yield 'hyphenated pound' => ['10 Dumbbell Snatches with a 50-lb dumbbell'];
        yield 'percent 1rm' => ['10 Dumbbell Snatches at 70% 1RM'];
        yield 'at percent' => ['10 Dumbbell Snatches at 70%'];
        yield 'at sign percent' => ['10 Dumbbell Snatches @ 70%'];
    }

    public function testKeepsBodyweightAndSupportExemptions(): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();
        $boxStepUp = $this->movement('Box Step Up', ImplementEnum::BOX, ImplementEnum::DUMBBELL);
        $burpeeOver = $this->movement('Burpee Over', ImplementEnum::BARBELL);
        $deficitHspu = $this->movement('Deficit Strict Handstand Push Up', ImplementEnum::PLATE);

        self::assertSame([], $this->missingNames($validator, '20 Box Step Ups', [$boxStepUp]));
        self::assertSame([], $this->missingNames($validator, '10 Burpee Over (barbell)', [$burpeeOver]));
        self::assertSame([], $this->missingNames($validator, '10 Deficit Strict Handstand Push Ups on plates', [$deficitHspu]));
    }

    public function testRequiresLoadForExplicitWeightedMixedMovement(): void
    {
        $validator = new WorkoutLoadPrescriptionValidator();
        $boxStepUp = $this->movement('Box Step Up', ImplementEnum::BOX, ImplementEnum::DUMBBELL);

        self::assertSame(['Box Step Up'], $this->missingNames($validator, '20 weighted Box Step Ups', [$boxStepUp]));
        self::assertSame([], $this->missingNames($validator, '20 weighted Box Step Ups (2x22,5/15 kg)', [$boxStepUp]));
    }

    /**
     * @param list<Movement> $movements
     *
     * @return list<string>
     */
    private function missingNames(WorkoutLoadPrescriptionValidator $validator, string $flow, array $movements): array
    {
        return array_map(
            static fn (Movement $movement): string => $movement->getName() ?? '',
            $validator->movementsMissingMainFlowLoadPrescription($flow, $movements)
        );
    }

    private function movement(string $name, ImplementEnum ...$implements): Movement
    {
        $movement = new Movement(
            $name,
            new MovementDifficulty(MovementDifficultyEnum::RX),
            new MovementType(MovementTypeEnum::WEIGHTLIFTING),
        );

        foreach ($implements as $implement) {
            $movement->addPossibleImplement(new Implement($implement, null));
        }

        return $movement;
    }
}
