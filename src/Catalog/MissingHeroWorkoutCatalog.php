<?php

namespace App\Catalog;

final class MissingHeroWorkoutCatalog
{
    public const string WORKOUT_JT = 'hero_jt';
    public const string WORKOUT_MICHAEL = 'hero_michael';
    public const string WORKOUT_JASON = 'hero_jason';
    public const string WORKOUT_JOSHIE = 'hero_joshie';
    public const string WORKOUT_TOMMY_V = 'hero_tommy_v';
    public const string WORKOUT_GRIFF = 'hero_griff';
    public const string WORKOUT_RYAN = 'hero_ryan';
    public const string WORKOUT_ERIN = 'hero_erin';
    public const string WORKOUT_MR_JOSHUA = 'hero_mr_joshua';
    public const string WORKOUT_DT = 'hero_dt';
    public const string WORKOUT_DANNY = 'hero_danny';
    public const string WORKOUT_HANSEN = 'hero_hansen';
    public const string WORKOUT_TYLER = 'hero_tyler';
    public const string WORKOUT_LUMBERJACK_20 = 'hero_lumberjack_20';
    public const string WORKOUT_STEPHEN = 'hero_stephen';
    public const string WORKOUT_GARRETT = 'hero_garrett';
    public const string WORKOUT_WAR_FRANK = 'hero_war_frank';
    public const string WORKOUT_MCGHEE = 'hero_mcghee';
    public const string WORKOUT_PAUL = 'hero_paul';
    public const string WORKOUT_NUTTS = 'hero_nutts';
    public const string WORKOUT_ARNIE = 'hero_arnie';
    public const string WORKOUT_THE_SEVEN = 'hero_the_seven';
    public const string WORKOUT_RJ = 'hero_rj';
    public const string WORKOUT_LUCE = 'hero_luce';
    public const string WORKOUT_JOHNSON = 'hero_johnson';
    public const string WORKOUT_ADAMBROWN = 'hero_adambrown';
    public const string WORKOUT_COE = 'hero_coe';
    public const string WORKOUT_SEVERIN = 'hero_severin';
    public const string WORKOUT_HELTON = 'hero_helton';
    public const string WORKOUT_JACK = 'hero_jack';
    public const string WORKOUT_FORREST = 'hero_forrest';
    public const string WORKOUT_BULGER = 'hero_bulger';
    public const string WORKOUT_BRENTON = 'hero_brenton';
    public const string WORKOUT_BLAKE = 'hero_blake';
    public const string WORKOUT_COLLIN = 'hero_collin';
    public const string WORKOUT_THOMPSON = 'hero_thompson';
    public const string WORKOUT_WHITTEN = 'hero_whitten';
    public const string WORKOUT_BULL = 'hero_bull';
    public const string WORKOUT_RANKEL = 'hero_rankel';
    public const string WORKOUT_HOLBROOK = 'hero_holbrook';

    /**
     * @return array<string, array{name: string, flow: string, origin: string, implements: list<string>, movements: list<string>, workoutType?: string}>
     */
    public static function workouts(): array
    {
        return [
            self::WORKOUT_JT => [
                'name' => 'JT',
                'flow' => <<<TXT
                21-15-9 reps for time of:
                Handstand push-ups
                Ring dips
                Push-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_MICHAEL => [
                'name' => 'Michael',
                'flow' => <<<TXT
                3 rounds for time of:
                Run 800 meters
                50 back extensions
                50 sit-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_JASON => [
                'name' => 'Jason',
                'flow' => <<<TXT
                For time:
                100 squats
                5 muscle-ups
                75 squats
                10 muscle-ups
                50 squats
                15 muscle-ups
                25 squats
                20 muscle-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_JOSHIE => [
                'name' => 'Joshie',
                'flow' => <<<TXT
                3 rounds for time of:
                21 dumbbell snatches, right arm
                21 L pull-ups
                21 dumbbell snatches, left arm
                21 L pull-ups
                Women: 25 lb
                Men: 40 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_TOMMY_V => [
                'name' => 'Tommy V',
                'flow' => <<<TXT
                For time:
                21 thrusters
                12 rope climbs
                15 thrusters
                9 rope climbs
                9 thrusters
                6 rope climbs
                Women: 75 lb, 15-foot rope
                Men: 115 lb, 15-foot rope
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_GRIFF => [
                'name' => 'Griff',
                'flow' => <<<TXT
                For time:
                Run 800 meters
                Run 400 meters backwards
                Run 800 meters
                Run 400 meters backwards
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_RYAN => [
                'name' => 'Ryan',
                'flow' => <<<TXT
                5 rounds for time of:
                7 muscle-ups
                21 burpees
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_ERIN => [
                'name' => 'Erin',
                'flow' => <<<TXT
                5 rounds for time of:
                15 dumbbell split cleans
                21 pull-ups
                Women: 30 lb
                Men: 40 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_MR_JOSHUA => [
                'name' => 'Mr. Joshua',
                'flow' => <<<TXT
                5 rounds for time of:
                Run 400 meters
                30 GHD sit-ups
                15 deadlifts
                Women: 175 lb
                Men: 250 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_DT => [
                'name' => 'DT',
                'flow' => <<<TXT
                5 rounds for time of:
                12 deadlifts
                9 hang power cleans
                6 push jerks
                Women: 105 lb
                Men: 155 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_DANNY => [
                'name' => 'Danny',
                'flow' => <<<TXT
                Complete as many rounds in 20 minutes as you can of:
                30 box jumps
                20 push presses
                30 pull-ups
                Women: 75-lb barbell, 20-inch box
                Men: 115-lb barbell, 24-inch box
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'AMRAP',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_HANSEN => [
                'name' => 'Hansen',
                'flow' => <<<TXT
                5 rounds for time of:
                30 kettlebell swings
                30 burpees
                30 GHD sit-ups
                Women: 53 lb
                Men: 70 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_TYLER => [
                'name' => 'Tyler',
                'flow' => <<<TXT
                5 rounds for time of:
                7 muscle-ups
                21 sumo deadlift high pulls
                Women: 65 lb
                Men: 95 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_LUMBERJACK_20 => [
                'name' => 'Lumberjack 20',
                'flow' => <<<TXT
                For time:
                20 deadlifts
                Run 400 meters
                20 kettlebell swings
                Run 400 meters
                20 overhead squats
                Run 400 meters
                20 burpees
                Run 400 meters
                20 chest-to-bar pull-ups
                Run 400 meters
                20 box jumps
                Run 400 meters
                20 dumbbell squat cleans
                Run 400 meters
                Women: 185-lb deadlifts, 53-lb kettlebell, 75-lb overhead squats, 20-inch box, 30-lb dumbbells
                Men: 275-lb deadlifts, 70-lb kettlebell, 115-lb overhead squats, 24-inch box, 45-lb dumbbells
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_STEPHEN => [
                'name' => 'Stephen',
                'flow' => <<<TXT
                30-25-20-15-10-5 reps of:
                GHD sit-ups
                Stiff-legged deadlift
                Women: 65 lb
                Men: 95 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_GARRETT => [
                'name' => 'Garrett',
                'flow' => <<<TXT
                3 rounds for time of:
                75 squats
                25 ring handstand push-ups
                25 L pull-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_WAR_FRANK => [
                'name' => 'War Frank',
                'flow' => <<<TXT
                3 rounds for time of:
                25 muscle-ups
                100 squats
                35 GHD sit-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_MCGHEE => [
                'name' => 'McGhee',
                'flow' => <<<TXT
                As many rounds as possible in 30 minutes of:
                5 deadlifts
                13 push-ups
                9 box jumps
                Women: 185-lb barbell, 20-inch box
                Men: 275-lb barbell, 24-inch box
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'AMRAP',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_PAUL => [
                'name' => 'Paul',
                'flow' => <<<TXT
                5 rounds for time of:
                50 double-unders
                35 knees-to-elbows
                20-yard overhead walk
                Women: 125 lb
                Men: 185 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_NUTTS => [
                'name' => 'Nutts',
                'flow' => <<<TXT
                For time:
                10 handstand push-ups
                15 deadlifts
                25 box jumps
                50 pull-ups
                100 wall-ball shots
                200 double-unders
                Run 400 meters with a plate
                Women: 175-lb deadlifts, 24-inch box, 14-lb medicine ball to 9 feet, 25-lb plate
                Men: 250-lb deadlifts, 30-inch box, 20-lb medicine ball to 10 feet, 45-lb plate
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_ARNIE => [
                'name' => 'Arnie',
                'flow' => <<<TXT
                21 Turkish get-ups, right arm
                50 kettlebell swings
                21 overhead squats, left arm
                50 kettlebell swings
                21 overhead squats, right arm
                50 kettlebell swings
                21 Turkish get-ups, left arm
                Women: 53 lb
                Men: 70 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_THE_SEVEN => [
                'name' => 'The Seven',
                'flow' => <<<TXT
                7 rounds for time of:
                7 handstand push-ups
                7 thrusters
                7 knees-to-elbows
                7 deadlifts
                7 burpees
                7 kettlebell swings
                7 pull-ups
                Women: 95-lb thrusters, 165-lb deadlifts, 53-lb kettlebell
                Men: 135-lb thrusters, 245-lb deadlifts, 70-lb kettlebell
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_RJ => [
                'name' => 'RJ',
                'flow' => <<<TXT
                5 rounds for time of:
                Run 800 meters
                5 rope climbs to 15 feet
                50 push-ups
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_LUCE => [
                'name' => 'Luce',
                'flow' => <<<TXT
                Wearing a weight vest, 3 rounds for time of:
                1K run
                10 muscle-ups
                100 squats
                Women: 14-lb vest
                Men: 20-lb vest
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_JOHNSON => [
                'name' => 'Johnson',
                'flow' => <<<TXT
                Complete as many rounds and reps as possible in 20 minutes of:
                9 deadlifts
                8 muscle-ups
                9 squat cleans
                Women: 165-lb deadlifts, 105-lb squat cleans
                Men: 245-lb deadlifts, 155-lb squat cleans
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'AMRAP',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_ADAMBROWN => [
                'name' => 'Adambrown',
                'flow' => <<<TXT
                2 rounds for time of:
                24 deadlifts
                24 box jumps
                24 wall-ball shots
                24 bench presses
                24 box jumps
                24 wall-ball shots
                24 cleans
                Women: 195-lb deadlifts, 20-inch box, 14-lb ball to 9 feet, 135-lb bench press, 95-lb clean
                Men: 295-lb deadlifts, 24-inch box, 20-lb ball to 10 feet, 195-lb bench press, 145-lb cleans
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_COE => [
                'name' => 'Coe',
                'flow' => <<<TXT
                10 rounds for time of:
                10 thrusters
                10 ring push-ups
                Women: 65 lb
                Men: 95 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_SEVERIN => [
                'name' => 'Severin',
                'flow' => <<<TXT
                For time:
                50 strict pull-ups
                100 hand-release push-ups
                Run 5K
                *If you've got a weight vest or body armor, wear it.
                Women: 14-lb vest
                Men: 20-lb vest
                If you've got a 20-lb vest or body armor, wear it.
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_HELTON => [
                'name' => 'Helton',
                'flow' => <<<TXT
                3 rounds for time of:
                Run 800 meters
                30 dumbbell squat cleans
                30 burpees
                Women: 35-lb dumbbells
                Men: 50-lb dumbbells
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_JACK => [
                'name' => 'Jack',
                'flow' => <<<TXT
                Complete as many rounds and reps as possible in 20 minutes of:
                10 push presses
                10 kettlebell swings
                10 box jumps
                Women: 75-lb barbell, 1-pood kettlebell, 20-inch box
                Men: 115-lb barbell, 1.5-pood kettlebell, 24-inch box
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'AMRAP',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_FORREST => [
                'name' => 'Forrest',
                'flow' => <<<TXT
                3 rounds for time of:
                20 L pull-ups
                30 toes-to-bars
                40 burpees
                Run 800 meters
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_BULGER => [
                'name' => 'Bulger',
                'flow' => <<<TXT
                10 rounds for time of:
                150-meter run
                7 chest-to-bar pull-ups
                7 front squats
                7 handstand push-ups
                Women: 95 lb
                Men: 135 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_BRENTON => [
                'name' => 'Brenton',
                'flow' => <<<TXT
                5 rounds for time of:
                100-foot bear crawl
                100-foot standing broad jump
                Do 3 burpees after every 5 broad jumps. If you've got a 20-lb vest or body armor, wear it.
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_BLAKE => [
                'name' => 'Blake',
                'flow' => <<<TXT
                4 rounds for time of:
                100-foot overhead walking lunge
                30 box jumps
                20 wall-ball shots
                10 handstand push-ups
                Women: 25-lb plate, 20-inch box, 14-lb medicine ball to a 9-foot target
                Men: 45-lb plate, 24-inch box, 20-lb medicine ball to a 10-foot target
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_COLLIN => [
                'name' => 'Collin',
                'flow' => <<<TXT
                6 rounds for time of:
                400-meter sandbag carry
                12 push presses
                12 box jumps
                12 sumo deadlift high pulls
                Women: 35-lb sandbag, 75-lb push press, 20-inch box, 65-lb sumo deadlift high pull
                Men: 50-lb sandbag, 115-lb push press, 24-inch box, 95-lb sumo deadlift high pull
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_THOMPSON => [
                'name' => 'Thompson',
                'flow' => <<<TXT
                10 rounds for time of:
                1 rope climb to 15 feet
                29 back squats
                10-meter barbell farmers carry
                Women: 65-lb back squat, 95-lb farmers carry
                Men: 95-lb back squat, 135-lb farmers carry
                Begin the rope climbs seated on the floor.
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_WHITTEN => [
                'name' => 'Whitten',
                'flow' => <<<TXT
                5 rounds for time of:
                22 kettlebell swings
                22 box jumps
                400-meter run
                22 burpees
                22 wall-ball shots
                Women: 53-lbkettlebell, 20-inch box, 14-lb medicine ball to a 9-foot target
                Men: 72-lb kettlebell, 24-inch box, 20-lb medicine ball to a 10-foot target
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_BULL => [
                'name' => 'Bull',
                'flow' => <<<TXT
                2 rounds for time of:
                200 double-unders
                50 overhead squats
                50 pull-ups
                1-mile run
                Women: 95-lb barbell
                Men: 135-lb barbell
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_RANKEL => [
                'name' => 'Rankel',
                'flow' => <<<TXT
                Complete as many rounds as possible in 20 minutes of:
                6 deadlifts
                7 burpee pull-ups
                10 kettlebell swings
                200-meter run
                Women: 155-lb barbell, 53-lb kettlebell
                Men: 225-lb barbell, 70-lb kettlebell
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'AMRAP',
                'implements' => [],
                'movements' => [],
            ],
            self::WORKOUT_HOLBROOK => [
                'name' => 'Holbrook',
                'flow' => <<<TXT
                10 rounds, each for time, of:
                5 thrusters
                10 pull-ups
                100-meter sprint
                Rest 1 minute between rounds.
                Women: 75 lb
                Men: 115 lb
                TXT,
                'origin' => 'Hero workout',
                'workoutType' => 'For time',
                'implements' => [],
                'movements' => [],
            ],
        ];
    }
}
