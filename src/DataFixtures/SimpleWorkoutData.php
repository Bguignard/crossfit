<?php

namespace App\DataFixtures;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\SimpleWorkout;
use App\Entity\Workout\WorkoutOrigin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SimpleWorkoutData extends Fixture implements DependentFixtureInterface
{
    public const string SIMPLE_WORKOUT_ANGIE = 'Angie';
    public const string SIMPLE_WORKOUT_FRAN = 'Fran';
    public const string SIMPLE_WORKOUT_BARBARA = 'Barbara';
    public const string SIMPLE_WORKOUT_CHELSEA = 'Chelsea';
    public const string SIMPLE_WORKOUT_DIANE = 'Diane';
    public const string SIMPLE_WORKOUT_ELIZABETH = 'Elizabeth';
    public const string SIMPLE_WORKOUT_GRACE = 'Grace';
    public const string SIMPLE_WORKOUT_HELEN = 'Helen';
    public const string SIMPLE_WORKOUT_JACKIE = 'Jackie';
    public const string SIMPLE_WORKOUT_KAREN = 'Karen';
    public const string SIMPLE_WORKOUT_LINDA = 'Linda';
    public const string SIMPLE_WORKOUT_MARY = 'Mary';
    public const string SIMPLE_WORKOUT_NANCY = 'Nancy';
    public const string SIMPLE_WORKOUT_ANNIE = 'Annie';
    public const string SIMPLE_WORKOUT_ISABEL = 'Isabel';
    public const string SIMPLE_WORKOUT_EVA = 'Eva';
    public const string SIMPLE_WORKOUT_KELLY = 'Kelly';
    public const string SIMPLE_WORKOUT_NICOLE = 'Nicole';
    public const string SIMPLE_WORKOUT_LYNNE = 'Lynne';
    public const string SIMPLE_WORKOUT_CINDY = 'Cindy';
    public const string SIMPLE_WORKOUT_AMANDA = 'Amanda';
    public const string SIMPLE_WORKOUT_MURPH = 'Murph';
    public const string SIMPLE_WORKOUT_NATE = 'Nate';
    public const string SIMPLE_WORKOUT_RANDY = 'Randy';
    public const string SIMPLE_WORKOUT_JOSH = 'Josh';
    public const string SIMPLE_WORKOUT_BADGER = 'Badger';
    public const string SIMPLE_WORKOUT_ROY = 'Roy';
    public const string SIMPLE_WORKOUT_DANIEL = 'Daniel';
    public const string SIMPLE_WORKOUT_JERRY = 'Jerry';
    public const string SIMPLE_WORKOUT_HOLLEYMAN = 'Holleyman';
    public const string SIMPLE_WORKOUT_LEDESMA = 'ledesma';
    public const string SIMPLE_WORKOUT_WITTMAN = 'wittman';
    public const string SIMPLE_WORKOUT_MCCLUSKEY = 'mccluskey';
    public const string SIMPLE_WORKOUT_WEAVER = 'weaver';
    public const string SIMPLE_WORKOUT_ABBATE = 'abbate';
    public const string SIMPLE_WORKOUT_HAMMER = 'hammer';
    public const string SIMPLE_WORKOUT_MOORE = 'moore';
    public const string SIMPLE_WORKOUT_WILMOT = 'wilmot';
    public const string SIMPLE_WORKOUT_MOON = 'moon';
    public const string SIMPLE_WORKOUT_SMALL = 'small';
    public const string SIMPLE_WORKOUT_MORRISON = 'morrison';
    public const string SIMPLE_WORKOUT_MANION = 'manion';
    public const string SIMPLE_WORKOUT_GATOR = 'gator';
    public const string SIMPLE_WORKOUT_BRADLEY = 'bradley';
    public const string SIMPLE_WORKOUT_MEADOWS = 'meadows';
    public const string SIMPLE_WORKOUT_SANTIAGO = 'santiago';
    public const string SIMPLE_WORKOUT_CARSE = 'carse';
    public const string SIMPLE_WORKOUT_BRADSHAW = 'bradshaw';
    public const string SIMPLE_WORKOUT_WHITE = 'white';
    public const string SIMPLE_WORKOUT_SANTORA = 'santora';
    public const string SIMPLE_WORKOUT_WOOD = 'wood';
    public const string SIMPLE_WORKOUT_HIDALGO = 'hidalgo';
    public const string SIMPLE_WORKOUT_RICKY = 'ricky';
    public const string SIMPLE_WORKOUT_DAE_HAN = 'dae_han';
    public const string SIMPLE_WORKOUT_DESFORGES = 'desforges';
    public const string SIMPLE_WORKOUT_RAHOI = 'rahoi';
    public const string SIMPLE_WORKOUT_ZIMMERMAN = 'zimmerman';
    public const string SIMPLE_WORKOUT_KLEPTO = 'klepto';
    public const string SIMPLE_WORKOUT_DEL = 'del';
    public const string SIMPLE_WORKOUT_PHEEZY = 'pheezy';
    public const string SIMPLE_WORKOUT_JJ = 'jj';
    public const string SIMPLE_WORKOUT_JAG_28 = 'jag_28';
    public const string SIMPLE_WORKOUT_BRIAN = 'brian';
    public const string SIMPLE_WORKOUT_NICK = 'nick';
    public const string SIMPLE_WORKOUT_STRANGE = 'strange';
    public const string SIMPLE_WORKOUT_TUMILSON = 'tumilson';
    public const string SIMPLE_WORKOUT_SHIP = 'ship';
    public const string SIMPLE_WORKOUT_JARED = 'jared';
    public const string SIMPLE_WORKOUT_FULLY_TULLY = 'tully';
    public const string SIMPLE_WORKOUT_ADRIAN = 'adrian';
    public const string SIMPLE_WORKOUT_GLEN = 'glen';
    public const string SIMPLE_WORKOUT_TOM = 'tom';
    public const string SIMPLE_WORKOUT_RALPH = 'ralph';
    public const string SIMPLE_WORKOUT_CLOVIS = 'clovis';
    public const string SIMPLE_WORKOUT_WESTON = 'weston';
    public const string SIMPLE_WORKOUT_LOREDO = 'loredo';
    public const string SIMPLE_WORKOUT_SEAN = 'sean';
    public const string SIMPLE_WORKOUT_HORTMAN = 'hortman';
    public const string SIMPLE_WORKOUT_HAMILTON = 'hamilton';
    public const string SIMPLE_WORKOUT_ZEUS = 'zeus';
    public const string SIMPLE_WORKOUT_BARRAZA = 'barraza';
    public const string SIMPLE_WORKOUT_CAMERON = 'cameron';
    public const string SIMPLE_WORKOUT_JORGE = 'jorge';
    public const string SIMPLE_WORKOUT_SCHMALLS = 'schmalls';
    public const string SIMPLE_WORKOUT_BREHM = 'brehm';
    public const string SIMPLE_WORKOUT_OMAR = 'omar';
    public const string SIMPLE_WORKOUT_GALLANT = 'gallant';
    public const string SIMPLE_WORKOUT_BRUCK = 'bruck';
    public const string SIMPLE_WORKOUT_SMYKOWSKI = 'smykowski';
    public const string SIMPLE_WORKOUT_FALKEL = 'falkel';
    public const string SIMPLE_WORKOUT_DONNY = 'donny';
    public const string SIMPLE_WORKOUT_DOBOGAI = 'dobogai';
    public const string SIMPLE_WORKOUT_HOTSHOTS_19 = 'hotshots_19';
    public const string SIMPLE_WORKOUT_RONEY = 'roney';
    public const string SIMPLE_WORKOUT_THE_DON = 'the_don';
    public const string SIMPLE_WORKOUT_DRAGON = 'dragon';
    public const string SIMPLE_WORKOUT_WALSH = 'walsh';
    public const string SIMPLE_WORKOUT_LEE = 'lee';
    public const string SIMPLE_WORKOUT_WILLY = 'willy';
    public const string SIMPLE_WORKOUT_COFFEY = 'coffey';
    public const string SIMPLE_WORKOUT_DG = 'dg';
    public const string SIMPLE_WORKOUT_TK = 'tk';
    public const string SIMPLE_WORKOUT_TAYLOR = 'taylor';
    public const string SIMPLE_WORKOUT_JUSTIN = 'justin';
    public const string SIMPLE_WORKOUT_NUKES = 'nukes';
    public const string SIMPLE_WORKOUT_ZEMBIEC = 'zembiec';
    public const string SIMPLE_WORKOUT_ALEXANDER = 'alexander';
    public const string SIMPLE_WORKOUT_WYK = 'wyk';
    public const string SIMPLE_WORKOUT_BELL = 'bell';
    public const string SIMPLE_WORKOUT_JBO = 'jbo';
    public const string SIMPLE_WORKOUT_KEVIN = 'kevin';
    public const string SIMPLE_WORKOUT_ROCKET = 'rocket';
    public const string SIMPLE_WORKOUT_RILEY = 'riley';
    public const string SIMPLE_WORKOUT_FEEKS = 'feeks';
    public const string SIMPLE_WORKOUT_NED = 'ned';
    public const string SIMPLE_WORKOUT_SHAM = 'sham';
    public const string SIMPLE_WORKOUT_OZZY = 'ozzy';
    public const string SIMPLE_WORKOUT_JENNY = 'jenny';
    public const string SIMPLE_WORKOUT_SPEHAR = 'spehar';
    public const string SIMPLE_WORKOUT_LUKE = 'luke';
    public const string SIMPLE_WORKOUT_ROBBIE = 'robbie';
    public const string SIMPLE_WORKOUT_SHAWN = 'shawn';
    public const string SIMPLE_WORKOUT_FOO = 'foo';
    public const string SIMPLE_WORKOUT_BOWEN = 'bowen';
    public const string SIMPLE_WORKOUT_GAZA = 'gaza';
    public const string SIMPLE_WORKOUT_CRAIN = 'crain';
    public const string SIMPLE_WORKOUT_CAPOOT = 'capoot';
    public const string SIMPLE_WORKOUT_HALL = 'hall';
    public const string SIMPLE_WORKOUT_SERVAIS = 'servais';
    public const string SIMPLE_WORKOUT_PK = 'pk';
    public const string SIMPLE_WORKOUT_MARCO = 'marco';
    public const string SIMPLE_WORKOUT_RENE = 'rene';
    public const string SIMPLE_WORKOUT_PIKE = 'pike';
    public const string SIMPLE_WORKOUT_KUTSCHBACH = 'kutschbach';
    public const string SIMPLE_WORKOUT_JENNIFER = 'jennifer';
    public const string SIMPLE_WORKOUT_HORTON = 'horton';
    public const string SIMPLE_WORKOUT_SCOOTER = 'scooter';
    public const string SIMPLE_WORKOUT_MATT_16 = 'matt_16';
    public const string SIMPLE_WORKOUT_TUP = 'tup';
    public const string SIMPLE_WORKOUT_HARPER = 'harper';
    public const string SIMPLE_WORKOUT_SISSON = 'sisson';
    public const string SIMPLE_WORKOUT_TERRY = 'terry';
    public const string SIMPLE_WORKOUT_BIG_SEXY = 'big_sexy';
    public const string SIMPLE_WORKOUT_WOEHLKE = 'woehlke';
    public const string SIMPLE_WORKOUT_MAUPIN = 'maupin';
    public const string SIMPLE_WORKOUT_HILDY = 'hildy';
    public const string SIMPLE_WORKOUT_TJ = 'tj';
    public const string SIMPLE_WORKOUT_MONTI = 'monti';
    public const string SIMPLE_WORKOUT_DVB = 'dvb';
    public const string SIMPLE_WORKOUT_NICKMAN = 'nickman';
    public const string SIMPLE_WORKOUT_MARSTON = 'marston';
    public const string SIMPLE_WORKOUT_ARTIE = 'artie';
    public const string SIMPLE_WORKOUT_HOLLYWOOD = 'hollywood';
    public const string SIMPLE_WORKOUT_MANUEL = 'manuel';
    public const string SIMPLE_WORKOUT_TIFF = 'tiff';
    public const string SIMPLE_WORKOUT_PAUL_PENA = 'paul_pena';
    public const string SIMPLE_WORKOUT_YETI = 'yeti';
    public const string SIMPLE_WORKOUT_LIAM = 'liam';
    public const string SIMPLE_WORKOUT_WES = 'wes';
    public const string SIMPLE_WORKOUT_MIRON = 'miron';
    public const string SIMPLE_WORKOUT_PAT = 'pat';
    public const string SIMPLE_WORKOUT_SCOTTY = 'scotty';
    public const string SIMPLE_WORKOUT_RICH = 'rich';
    public const string SIMPLE_WORKOUT_DALLAS_5 = 'dallas_5';
    public const string SIMPLE_WORKOUT_DUNN = 'dunn';
    public const string SIMPLE_WORKOUT_KEV = 'kev';
    public const string SIMPLE_WORKOUT_EMILY = 'emily';
    public const string SIMPLE_WORKOUT_ANDY = 'andy';
    public const string SIMPLE_WORKOUT_VIOLA = 'viola';
    public const string SIMPLE_WORKOUT_COFFLAND = 'coffland';
    public const string SIMPLE_WORKOUT_LYON = 'the_lyon';
    public const string SIMPLE_WORKOUT_T = 't';
    public const string SIMPLE_WORKOUT_HAVANA = 'havana';
    public const string SIMPLE_WORKOUT_TAMA = 'tama';
    public const string SIMPLE_WORKOUT_OTIS = 'otis';
    public const string SIMPLE_WORKOUT_JOSIE = 'josie';
    public const string SIMPLE_WORKOUT_DORK = 'dork';
    public const string SIMPLE_WORKOUT_BERT = 'bert';
    public const string SIMPLE_WORKOUT_WADE = 'wade';
    public const string SIMPLE_WORKOUT_FOURNIER = 'fournier';
    public const string SIMPLE_WORKOUT_LARRY = 'larry';
    public const string SIMPLE_WORKOUT_KELLY_BROWN = 'kelly_brown';
    public const string SIMPLE_WORKOUT_KERRIE = 'kerrie';
    public const string SIMPLE_WORKOUT_MARTIN = 'martin';
    public const string SIMPLE_WORKOUT_LAURA = 'laura';
    public const string SIMPLE_WORKOUT_LORENZO = 'lorenzo';
    public const string SIMPLE_WORKOUT_PEYTON = 'peyton';
    public const string SIMPLE_WORKOUT_MAXTON = 'maxton';
    public const string SIMPLE_WORKOUT_EVA_STRONG = 'eva_strong';
    public const string SIMPLE_WORKOUT_CHAD1000X = 'chad1000x';
    public const string SIMPLE_WORKOUT_TPT9000 = 'tpt9000';
    public const string SIMPLE_WORKOUT_GARBO = 'garbo';
    public const string SIMPLE_WORKOUT_MCCARTNEY = 'mccartney';
    public const string SIMPLE_WORKOUT_WESLEY = 'wesley';
    public const string SIMPLE_WORKOUT_HAMMY = 'hammy';
    public const string SIMPLE_WORKOUT_TRIPLE_DEUCE = 'triple_deuce';
    public const string SIMPLE_WORKOUT_K27 = 'k27';
    public const string SIMPLE_WORKOUT_BURIAK = 'buriak';
    public const string SIMPLE_WORKOUT_ODA_7313 = 'oda_7313';
    public const string SIMPLE_WORKOUT_GOOSE = 'goose';
    public const string SIMPLE_WORKOUT_PIKEY = 'pikey';
    public const string SIMPLE_WORKOUT_GALE_FORCE = 'gale_force';
    public const string SIMPLE_WORKOUT_NORTHRUP = 'northrup';
    public const string SIMPLE_WORKOUT_FERN = 'fern';
    public const string SIMPLE_WORKOUT_FINSETH = 'finseth';
    public const string SIMPLE_WORKOUT_GAGE = 'gage';
    public const string SIMPLE_WORKOUT_JOSH_O = 'josh_o';
    public const string SIMPLE_WORKOUT_WHITT = 'whitt';
    public const string SIMPLE_WORKOUT_RYAN_SO = 'ryan_so';
    public const string SIMPLE_WORKOUT_HOOVER = 'hoover';
    public const string SIMPLE_WORKOUT_CITY_100 = 'city_100';
    public const string SIMPLE_WORKOUT_ALEC = 'alec';
    public const string SIMPLE_WORKOUT_MULLER = 'muller';
    public const string SIMPLE_WORKOUT_DOMINIC_J_HALL = 'dominic_j_hall';
    public const string SIMPLE_WORKOUT_JONATHAN_FARMER = 'jonathan_farmer';
    public const string SIMPLE_WORKOUT_RYAN_COMAS = 'ryan_comas';
    public const string SIMPLE_WORKOUT_TIMOTHY_HELTON = 'timothy_helton';
    public const string SIMPLE_WORKOUT_TOPSY = 'topsy';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkouts() as $reference => $workout) {
            $implements = [];
            $movements = [];
            foreach ($workout['implements'] as $implementReference) {
                $implements[] = $this->getReference($implementReference, Implement::class);
            }
            foreach ($workout['movements'] as $movement) {
                $movements[] = $this->getReference($movement, Movement::class);
            }

            $simpleWorkout = new SimpleWorkout(
                $workout['name'],
                $workout['flow'],
                $workout['timeCap'] ?? null,
                $this->getReference($workout['origin'], WorkoutOrigin::class),
                $implements,
                $movements
            );
            $manager->persist($simpleWorkout);
            $this->addReference($reference, $simpleWorkout);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WorkoutOriginData::class,
            MovementData::class,
            ImplementData::class,
        ];
    }

    public function getWorkouts(): array
    {
        return [
            self::SIMPLE_WORKOUT_ANGIE => [
                'name' => 'Angie',
                'flow' => <<<TXT
                For time:
                100 Pull-Ups
                100 Push-Ups
                100 Sit-Ups
                100 Air Squats
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_FRAN => [
                'name' => 'Fran',
                'flow' => <<<TXT
                For time:
                21-15-9
                Thrusters (95/65 lb)
                Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BARBARA => [
                'name' => 'Barbara',
                'flow' => <<<TXT
                5 rounds for time:
                20 Pull-Ups
                30 Push-Ups
                40 Sit-Ups
                50 Air Squats
                Rest 3 min between rounds
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_CHELSEA => [
                'name' => 'Chelsea',
                'flow' => <<<TXT
                EMOM 30 min:
                5 Pull-Ups
                10 Push-Ups
                15 Air Squats
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_DIANE => [
                'name' => 'Diane',
                'flow' => <<<TXT
                For time:
                21-15-9
                Deadlifts (225/155 lb)
                Handstand Push-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ELIZABETH => [
                'name' => 'Elizabeth',
                'flow' => <<<TXT
                For time:
                21-15-9
                Cleans (135/95 lb)
                Ring Dips
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_RINGS],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_DIP,
                ],
            ],
            self::SIMPLE_WORKOUT_GRACE => [
                'name' => 'Grace',
                'flow' => <<<TXT
                For time:
                30 Clean and Jerks (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                ],
            ],
            self::SIMPLE_WORKOUT_HELEN => [
                'name' => 'Helen',
                'flow' => <<<TXT
                3 rounds for time:
                400 m Run
                21 Kettlebell Swings (53/35 lb)
                12 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_KETTLEBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_JACKIE => [
                'name' => 'Jackie',
                'flow' => <<<TXT
                For time:
                1000 m Row
                50 Thrusters (45/35 lb)
                30 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_ROWER, ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_KAREN => [
                'name' => 'Karen',
                'flow' => <<<TXT
                For time:
                150 Wall-Ball Shots (20/14 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_MEDICINE_BALL],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                ],
            ],
            self::SIMPLE_WORKOUT_LINDA => [
                'name' => 'Linda',
                'flow' => <<<TXT
                For time (10-9-8-...-1):
                Deadlift (1.5x BW)
                Bench Press (1x BW)
                Clean (0.75x BW)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BENCH_PRESS,
                    MovementData::MOVEMENT_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_MARY => [
                'name' => 'Mary',
                'flow' => <<<TXT
                AMRAP 20:
                5 Handstand Push-Ups
                10 Pistols (alternating)
                15 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_ALTERNATE_PISTOL_SQUAT,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_NANCY => [
                'name' => 'Nancy',
                'flow' => <<<TXT
                5 rounds for time:
                400 m Run
                15 Overhead Squats (95/65 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_OVERHEAD_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_ANNIE => [
                'name' => 'Annie',
                'flow' => <<<TXT
                For time:
                50-40-30-20-10
                Double-Unders
                Sit-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_JUMP_ROPE],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_SIT_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ISABEL => [
                'name' => 'Isabel',
                'flow' => <<<TXT
                For time:
                30 Snatches (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_EVA => [
                'name' => 'Eva',
                'flow' => <<<TXT
                5 rounds for time:
                800 m Run
                30 Kettlebell Swings (70/53 lb)
                30 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_KETTLEBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_KELLY => [
                'name' => 'Kelly',
                'flow' => <<<TXT
                5 rounds for time:
                400 m Run
                30 Box Jumps (24/20 in)
                30 Wall-Ball Shots (20/14 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BOX, ImplementData::IMPLEMENT_MEDICINE_BALL],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                ],
            ],
            self::SIMPLE_WORKOUT_NICOLE => [
                'name' => 'Nicole',
                'flow' => <<<TXT
                AMRAP 20:
                400 m Run
                Max Rep Pull-Ups (each round)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_LYNNE => [
                'name' => 'Lynne',
                'flow' => <<<TXT
                5 rounds (not for time):
                Max Rep Bodyweight Bench Press
                Max Rep Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_BENCH_PRESS,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_CINDY => [
                'name' => 'Cindy',
                'flow' => <<<TXT
                AMRAP 20:
                5 Pull-Ups
                10 Push-Ups
                15 Air Squats
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_AMANDA => [
                'name' => 'Amanda',
                'flow' => <<<TXT
                For time:
                9-7-5
                Muscle-Ups
                Snatches (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_RINGS, ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            // --- HERO WODs (AJOUT) ---
            self::SIMPLE_WORKOUT_MURPH => [
                'name' => 'Murph',
                'flow' => <<<TXT
                For time:
                1 mile Run
                100 Pull-Ups
                200 Push-Ups
                300 Air Squats
                1 mile Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_NATE => [
                'name' => 'Nate',
                'flow' => <<<TXT
                AMRAP 20:
                2 Muscle-Ups
                4 Handstand Push-Ups
                8 Kettlebell Swings (70/53 lb)
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_RINGS, ImplementData::IMPLEMENT_KETTLEBELL],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                ],
            ],
            self::SIMPLE_WORKOUT_RANDY => [
                'name' => 'Randy',
                'flow' => <<<TXT
                For time:
                75 Snatches (75/55 lb)
                TXT,
                'timeCap' => 15,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_JOSH => [
                'name' => 'Josh',
                'flow' => <<<TXT
                For time:
                21 Overhead Squats (95/65 lb)
                42 Pull-Ups
                15 Overhead Squats
                30 Pull-Ups
                9 Overhead Squats
                18 Pull-Ups
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_OVERHEAD_SQUAT,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BADGER => [
                'name' => 'Badger',
                'flow' => <<<TXT
                3 rounds for time:
                30 Squat Cleans (95/65 lb)
                30 Pull-Ups
                800 m Run
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_ROY => [
                'name' => 'Roy',
                'flow' => <<<TXT
                5 rounds for time:
                15 Deadlifts (225/155 lb)
                20 Box Jumps (24/20 in)
                25 Pull-Ups
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_BOX, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_DANIEL => [
                'name' => 'Daniel',
                'flow' => <<<TXT
                For time:
                50 Pull-Ups
                400 m Run
                21 Thrusters (95/65 lb)
                800 m Run
                21 Thrusters (95/65 lb)
                400 m Run
                50 Pull-Ups
                TXT,
                'timeCap' => 28,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR, ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_JERRY => [
                'name' => 'Jerry',
                'flow' => <<<TXT
                For time:
                1 mile Run
                2000 m Row
                1 mile Run
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_ROWER],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_ROW,
                ],
            ],
            self::SIMPLE_WORKOUT_HOLLEYMAN => [
                'name' => 'Holleyman',
                'flow' => <<<TXT
                30 rounds for time:
                5 Wall-Ball Shots (20/14 lb)
                3 Handstand Push-Ups
                1 Clean (225/155 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [ImplementData::IMPLEMENT_MEDICINE_BALL, ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_LEDESMA => [
                'name' => 'Ledesma',
                'flow' => <<<TXT
                AMRAP 20:
                5 Deadlifts (275/185 lb)
                13 Push-Ups
                17 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_WITTMAN => [
                'name' => 'Wittman',
                'flow' => <<<TXT
                7 rounds for time:
                15 Kettlebell Swings (53/35 lb)
                15 Power Cleans (95/65 lb)
                15 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_MCCLUSKEY => [
                'name' => 'McCluskey',
                'flow' => <<<TXT
                3 rounds for time:
                9 Muscle-Ups
                15 Burpee Pull-Ups
                21 Pull-Ups
                800 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_BURPEE_PULL_UP,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_WEAVER => [
                'name' => 'Weaver',
                'flow' => <<<TXT
                4 rounds for time:
                10 Pull-Ups
                15 Push-Ups
                20 Sit-Ups
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_ABBATE => [
                'name' => 'Abbate',
                'flow' => <<<TXT
                For time:
                1 mile Run
                21 Clean and Jerks (155/105 lb)
                800 m Run
                21 Clean and Jerks (155/105 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                ],
            ],
            self::SIMPLE_WORKOUT_HAMMER => [
                'name' => 'Hammer',
                'flow' => <<<TXT
                5 rounds for time:
                5 Power Cleans (135/95 lb)
                10 Front Squats (135/95 lb)
                5 Jerks (135/95 lb)
                20 Pull-Ups
                Rest 90 seconds
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                    MovementData::MOVEMENT_JERK,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_MOORE => [
                'name' => 'Moore',
                'flow' => <<<TXT
                AMRAP 20:
                1 Rope Climb
                400 m Run
                Max Rep Handstand Push-Ups
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WILMOT => [
                'name' => 'Wilmot',
                'flow' => <<<TXT
                6 rounds for time:
                50 Squats
                25 Ring Dips
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                ],
                'movements' => [
                    MovementData::MOVEMENT_SQUAT,
                    MovementData::MOVEMENT_DIP,
                ],
            ],
            self::SIMPLE_WORKOUT_MOON => [
                'name' => 'Moon',
                'flow' => <<<TXT
                7 rounds for time:
                10 Hang Squat Cleans (135/95 lb)
                1 Rope Climb
                400 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_HANG_SQUAT_CLEAN,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SMALL => [
                'name' => 'Small',
                'flow' => <<<TXT
                3 rounds for time:
                1000 m Row
                50 Box Jumps (24/20 in)
                50 Dumbbell Hang Cleans (50/35 lb)
                50 Pull-Ups
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROWER,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_HANG_CLEAN,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_MORRISON => [
                'name' => 'Morrison',
                'flow' => <<<TXT
                For time:
                50-40-30-20-10
                Wall-Ball Shots (20/14 lb)
                Box Jumps (24/20 in)
                Kettlebell Swings (53/35 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                ],
            ],
            self::SIMPLE_WORKOUT_MANION => [
                'name' => 'Manion',
                'flow' => <<<TXT
                7 rounds for time:
                400 m Run
                29 Back Squats (135/95 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BACK_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_GATOR => [
                'name' => 'Gator',
                'flow' => <<<TXT
                8 rounds for time:
                10 Deadlifts (185/135 lb)
                26 Push-Ups (hand release)
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HAND_RELEASE_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BRADLEY => [
                'name' => 'Bradley',
                'flow' => <<<TXT
                10 rounds for time:
                Sprint 100 m
                10 Pull-Ups
                Sprint 100 m
                10 Burpees
                Rest 30 seconds
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_MEADOWS => [
                'name' => 'Meadows',
                'flow' => <<<TXT
                20 rounds for time:
                6 Pull-Ups
                6 Push-Ups
                6 Squats
                400 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SQUAT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SANTIAGO => [
                'name' => 'Santiago',
                'flow' => <<<TXT
                7 rounds for time:
                18 Dumbbell Hang Squat Cleans (35/25 lb)
                18 Pull-Ups
                10 Power Snatches (95/65 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_HANG_SQUAT_CLEAN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_CARSE => [
                'name' => 'Carse',
                'flow' => <<<TXT
                21-18-15-12-9-6-3 reps for time:
                Squat Cleans (95/65 lb)
                Double-Unders
                Deadlifts (185/135 lb)
                Box Jumps (24/20 in)
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_BRADSHAW => [
                'name' => 'Bradshaw',
                'flow' => <<<TXT
                10 rounds for time:
                3 Handstand Push-Ups
                6 Deadlifts (225/155 lb)
                12 Pull-Ups
                24 Double-Unders
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                ],
            ],
            self::SIMPLE_WORKOUT_WHITE => [
                'name' => 'White',
                'flow' => <<<TXT
                5 rounds for time:
                3 Rope Climbs
                10 Toes-to-Bar
                21 Overhead Walking Lunges (45/35 lb plate)
                400 m Run
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_PLATE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_OVERHEAD_WALKING_LUNGE,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SANTORA => [
                'name' => 'Santora',
                'flow' => <<<TXT
                AMRAP 20:
                22 Wall-Ball Shots (20/14 lb)
                22 Double-Unders
                22 Power Cleans (95/65 lb)
                22 Pull-Ups
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WOOD => [
                'name' => 'Wood',
                'flow' => <<<TXT
                5 rounds for time:
                Run 400 m
                10 Burpee Box Jumps (24/20 in)
                10 Sumo Deadlift High-Pulls (95/65 lb)
                10 Thrusters (95/65 lb)
                Rest 1 min
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_SUMO_DEADLIFT_HIGH_PULL,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_HIDALGO => [
                'name' => 'Hidalgo',
                'flow' => <<<TXT
                For time:
                2 mile Run
                20 Squat Cleans (135/95 lb)
                20 Box Jumps (24/20 in)
                20 Overhead Walking Lunges (45/35 lb plate)
                2 mile Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PLATE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_OVERHEAD_WALKING_LUNGE,
                ],
            ],
            self::SIMPLE_WORKOUT_RICKY => [
                'name' => 'Ricky',
                'flow' => <<<TXT
                4 rounds for time:
                800 m Run
                50 Pull-Ups
                100 Push-Ups
                150 Squats
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_DAE_HAN => [
                'name' => 'Dae Han',
                'flow' => <<<TXT
                3 rounds for time:
                800 m Run (with 45/35 lb vest)
                3 Rope Climbs
                12 Thrusters (135/95 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_WEIGHTED_VEST,
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_DESFORGES => [
                'name' => 'Desforges',
                'flow' => <<<TXT
                5 rounds for time:
                12 Deadlifts (225/155 lb)
                20 Pull-Ups
                12 Power Cleans (135/95 lb)
                20 Push-Ups
                12 Front Squats (135/95 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_RAHOI => [
                'name' => 'Rahoi',
                'flow' => <<<TXT
                AMRAP 12:
                12 Box Jumps (24/20 in)
                6 Thrusters (95/65 lb)
                6 Bar-Facing Burpees
                TXT,
                'timeCap' => 12,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_BURPEE_OVER_FACING,
                ],
            ],
            self::SIMPLE_WORKOUT_ZIMMERMAN => [
                'name' => 'Zimmerman',
                'flow' => <<<TXT
                AMRAP 25:
                11 Chest-to-Bar Pull-Ups
                2 Deadlifts (315/225 lb)
                10 Handstand Push-Ups
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CHEST_TO_BAR_PULL_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_KLEPTO => [
                'name' => 'Klepto',
                'flow' => <<<TXT
                4 rounds for time:
                27 Box Jumps (24/20 in)
                20 Burpees
                11 Squat Cleans (145/100 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_DEL => [
                'name' => 'Del',
                'flow' => <<<TXT
                For time:
                25 Burpees
                Run 400 m with a medicine ball (20/14 lb)
                25 Push-Ups
                Run 400 m with a medicine ball
                25 Walking Lunges
                Run 400 m with a medicine ball
                25 Air Squats
                Run 400 m with a medicine ball
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_WALKING_LUNGE,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_PHEEZY => [
                'name' => 'Pheezy',
                'flow' => <<<TXT
                3 rounds for time:
                5 Front Squats (225/155 lb)
                18 Pull-Ups
                5 Deadlifts (225/155 lb)
                18 Toes-to-Bar
                5 Push Jerks (225/155 lb)
                18 Hand Release Push-Ups
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_FRONT_SQUAT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_JERK,
                    MovementData::MOVEMENT_HAND_RELEASE_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_JJ => [
                'name' => 'JJ',
                'flow' => <<<TXT
                AMRAP 20:
                10 Push Presses (115/85 lb)
                10 Deadlifts (115/85 lb)
                10 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PUSH_PRESS,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_JAG_28 => [
                'name' => 'Jag 28',
                'flow' => <<<TXT
                For time:
                800 m Run
                28 Kettlebell Swings (53/35 lb)
                28 Pull-Ups
                28 Push-Ups
                28 Squats
                800 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_BRIAN => [
                'name' => 'Brian',
                'flow' => <<<TXT
                3 rounds for time:
                5 Rope Climbs
                25 Back Squats (185/125 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_BACK_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_NICK => [
                'name' => 'Nick',
                'flow' => <<<TXT
                12 rounds for time:
                10 Handstand Push-Ups
                15 Deadlifts (185/135 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                ],
            ],
            self::SIMPLE_WORKOUT_STRANGE => [
                'name' => 'Strange',
                'flow' => <<<TXT
                5 rounds for time:
                5 Deadlifts (275/185 lb)
                5 Rope Climbs
                5 Thrusters (135/95 lb)
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_TUMILSON => [
                'name' => 'Tumilson',
                'flow' => <<<TXT
                8 rounds for time:
                Run 200 m
                11 Dumbbell Burpee Deadlifts (60/40 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE_DEADLIFT,
                ],
            ],
            self::SIMPLE_WORKOUT_SHIP => [
                'name' => 'Ship',
                'flow' => <<<TXT
                9 rounds for time:
                7 Squat Cleans (185/125 lb)
                8 Burpee Box Jumps (36/30 in)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_JARED => [
                'name' => 'Jared',
                'flow' => <<<TXT
                4 rounds for time:
                Run 800 m
                40 Pull-Ups
                70 Push-Ups
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_FULLY_TULLY => [
                'name' => 'Tully',
                'flow' => <<<TXT
                5 rounds for time:
                200 m Farmer Carry (2x50/35 lb dumbbells)
                23 Pull-Ups
                23 Burpee Box Jumps (24/20 in)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_FARMER_CARRY,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_ADRIAN => [
                'name' => 'Adrian',
                'flow' => <<<TXT
                7 rounds for time:
                3 Forward Rolls
                5 Wall Walks
                7 Toes-to-Bar
                9 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_FORWARD_ROLL,
                    MovementData::MOVEMENT_WALL_WALK,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_GLEN => [
                'name' => 'Glen',
                'flow' => <<<TXT
                For time:
                30 Clean and Jerks (135/95 lb)
                Run 1 mile
                10 Rope Climbs
                Run 1 mile
                100 Burpees
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_TOM => [
                'name' => 'Tom',
                'flow' => <<<TXT
                25 rounds for time:
                7 Muscle-Ups
                11 Thrusters (155/105 lb)
                14 Toes-to-Bar
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                ],
            ],
            self::SIMPLE_WORKOUT_RALPH => [
                'name' => 'Ralph',
                'flow' => <<<TXT
                4 rounds for time:
                8 Deadlifts (250/175 lb)
                16 Pull-Ups
                24 Power Cleans (135/95 lb)
                32 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_CLOVIS => [
                'name' => 'Clovis',
                'flow' => <<<TXT
                For time:
                Run 10 miles
                150 Burpee Pull-Ups
                TXT,
                'timeCap' => 120,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WESTON => [
                'name' => 'Weston',
                'flow' => <<<TXT
                5 rounds for time:
                Row 1000 m
                200 m Farmer Carry (2x45/35 lb dumbbells)
                50 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROWER,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_FARMER_CARRY,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_LOREDO => [
                'name' => 'Loredo',
                'flow' => <<<TXT
                6 rounds for time:
                24 Squats
                24 Push-Ups
                24 Walking Lunges
                Run 400 m
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_SQUAT,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_WALKING_LUNGE,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SEAN => [
                'name' => 'Sean',
                'flow' => <<<TXT
                10 rounds for time:
                11 Chest-to-Bar Pull-Ups
                22 Front Squats (75/55 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CHEST_TO_BAR_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HORTMAN => [
                'name' => 'Hortman',
                'flow' => <<<TXT
                AMRAP 45:
                15 ft Rope Climb
                7 Clean and Jerks (155/105 lb)
                50 Air Squats
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HAMILTON => [
                'name' => 'Hamilton',
                'flow' => <<<TXT
                3 rounds for time:
                Run 800 m
                50 Deadlifts (185/135 lb)
                50 Push-Ups
                50 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_ZEUS => [
                'name' => 'Zeus',
                'flow' => <<<TXT
                3 rounds for time:
                30 Wall-Ball Shots (20/14 lb)
                30 Sumo Deadlift High-Pulls (75/55 lb)
                30 Box Jumps (20 in)
                30 Push Presses (75/55 lb)
                30 Row Calories
                30 Push-Ups
                10 Back Squats (Bodyweight)
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_ROWER,
                ],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_SUMO_DEADLIFT_HIGH_PULL,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_PRESS,
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_BACK_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_BARRAZA => [
                'name' => 'Barraza',
                'flow' => <<<TXT
                4 rounds for time:
                Run 200 m
                9 Deadlifts (275/185 lb)
                6 Burpee Bar Muscle-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BURPEE_MUSCLE_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_CAMERON => [
                'name' => 'Cameron',
                'flow' => <<<TXT
                For time:
                50 Walking Lunges
                25 Box Jumps (24/20 in)
                800 m Run
                50 Push-Ups
                25 Pull-Ups
                800 m Run
                50 Sit-Ups
                25 Squats
                800 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_WALKING_LUNGE,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_JORGE => [
                'name' => 'Jorge',
                'flow' => <<<TXT
                3 rounds for time:
                30 GHD Sit-Ups
                15 Deadlifts (275/185 lb)
                30 Handstand Push-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_GHD_SIT_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_SCHMALLS => [
                'name' => 'Schmalls',
                'flow' => <<<TXT
                For time:
                Run 800 m
                50 Burpees
                40 Pull-Ups
                30 Kettlebell Swings (70/53 lb)
                20 Box Jumps (24/20 in)
                10 Dumbbell Snatches (each arm, 50/35 lb)
                Run 800 m
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_BREHM => [
                'name' => 'Brehm',
                'flow' => <<<TXT
                For time:
                10 Rope Climbs
                20 Back Squats (225/155 lb)
                30 Handstand Push-Ups
                40 Calorie Row
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROWER,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_ROW,
                ],
            ],
            self::SIMPLE_WORKOUT_OMAR => [
                'name' => 'Omar',
                'flow' => <<<TXT
                For time:
                10 Thrusters (95/65 lb)
                15 Bar-Facing Burpees
                20 Thrusters (95/65 lb)
                25 Bar-Facing Burpees
                30 Thrusters (95/65 lb)
                35 Bar-Facing Burpees
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_BURPEE_OVER_FACING,
                ],
            ],
            self::SIMPLE_WORKOUT_GALLANT => [
                'name' => 'Gallant',
                'flow' => <<<TXT
                For time:
                1 mile Run
                60 Burpee Pull-Ups
                800 m Run
                30 Burpee Pull-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BRUCK => [
                'name' => 'Bruck',
                'flow' => <<<TXT
                4 rounds for time:
                Run 400 m
                24 Back Squats (185/135 lb)
                24 Jerk (185/135 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_JERK,
                ],
            ],
            self::SIMPLE_WORKOUT_SMYKOWSKI => [
                'name' => 'Smykowski',
                'flow' => <<<TXT
                7 rounds for time:
                11 Power Snatches (95/65 lb)
                11 Burpee Box Jumps (24/20 in)
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_POWER_SNATCH,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_FALKEL => [
                'name' => 'Falkel',
                'flow' => <<<TXT
                AMRAP 25:
                8 Handstand Push-Ups
                8 Box Jumps (24/20 in)
                1 Rope Climb
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                ],
            ],
            self::SIMPLE_WORKOUT_DONNY => [
                'name' => 'Donny',
                'flow' => <<<TXT
                For time:
                21-15-9-9-15-21
                Deadlifts (225/155 lb)
                Burpees
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_DOBOGAI => [
                'name' => 'Dobogai',
                'flow' => <<<TXT
                7 rounds for time:
                Run 800 m
                30 Dumbbell Hang Squat Cleans (35/25 lb)
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_HANG_SQUAT_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_HOTSHOTS_19 => [
                'name' => 'Hotshots 19',
                'flow' => <<<TXT
                6 rounds for time:
                30 Squats
                19 Power Cleans (135/95 lb)
                7 Strict Pull-Ups
                Run 400 m
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_SQUAT,
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_RONEY => [
                'name' => 'Roney',
                'flow' => <<<TXT
                5 rounds for time:
                15 Deadlifts (185/135 lb)
                15 Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_THE_DON => [
                'name' => 'The Don',
                'flow' => <<<TXT
                5 rounds for time:
                22 Deadlifts (110/75 lb)
                22 Box Jumps (24/20 in)
                22 Kettlebell Swings (53/35 lb)
                22 Burpees
                22 Wall-Ball Shots (20/14 lb)
                400 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_DRAGON => [
                'name' => 'Dragon',
                'flow' => <<<TXT
                5 rounds for time:
                5 Muscle-Ups
                10 Burpee Box Jumps (24/20 in)
                15 Deadlifts (225/155 lb)
                TXT,
                            'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_DEADLIFT,
                ],
            ],
            self::SIMPLE_WORKOUT_WALSH => [
                'name' => 'Walsh',
                'flow' => <<<TXT
                4 rounds for time:
                22 Burpee Pull-Ups
                22 Back Squats (185/135 lb)
                22 Sit-Ups
                400 m Run
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BURPEE_PULL_UP,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_LEE => [
                'name' => 'Lee',
                'flow' => <<<TXT
                AMRAP 23:
                400 m Run
                1 Rope Climb
                15 Thrusters (95/65 lb)
                TXT,
                'timeCap' => 23,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_WILLY => [
                'name' => 'Willy',
                'flow' => <<<TXT
                3 rounds for time:
                100 Double-Unders
                50 Box Jumps (24/20 in)
                25 Ring Dips
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_RINGS,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_DIP,
                ],
            ],
            self::SIMPLE_WORKOUT_COFFEY => [
                'name' => 'Coffey',
                'flow' => <<<TXT
                6 rounds for time:
                800 m Run
                50 Push-Ups
                50 Sit-Ups
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_DG => [
                'name' => 'DG',
                'flow' => <<<TXT
                10 rounds for time:
                8 Toes-to-Bar
                35 Pound Dumbbell Thrusters (10 reps, each arm)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_TK => [
                'name' => 'TK',
                'flow' => <<<TXT
                AMRAP 20:
                8 Pull-Ups
                8 Box Jumps (24/20 in)
                8 Dumbbell Snatches (each arm, 55/35 lb)
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_TAYLOR => [
                'name' => 'Taylor',
                'flow' => <<<TXT
                4 rounds for time:
                Run 400 m
                5 Burpee Rope Climbs
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE_ROPE_CLIMB,
                ],
            ],
            self::SIMPLE_WORKOUT_JUSTIN => [
                'name' => 'Justin',
                'flow' => <<<TXT
                30-20-10 reps for time:
                Pull-Ups
                Kettlebell Swings (53/35 lb)
                Box Jumps (24/20 in)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_NUKES => [
                'name' => 'Nukes',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Handstand Push-Ups
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_ZEMBIEC => [
                'name' => 'Zembiec',
                'flow' => <<<TXT
                5 rounds for time:
                11 Back Squats (185/135 lb)
                7 Burpee Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_ALEXANDER => [
                'name' => 'Alexander',
                'flow' => <<<TXT
                5 rounds for time:
                31 Back Squats (135/95 lb)
                12 Power Cleans (185/135 lb)
                31 Push-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_POWER_CLEAN,
                    MovementData::MOVEMENT_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WYK => [
                'name' => 'Wyk',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BELL => [
                'name' => 'Bell',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_JBO => [
                'name' => 'JBo',
                'flow' => <<<TXT
                AMRAP 28:
                9 Overhead Squats (115/75 lb)
                1 Rope Climb
                12 Burpee Box Jumps (24/20 in)
                TXT,
                'timeCap' => 28,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_OVERHEAD_SQUAT,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_KEVIN => [
                'name' => 'Kevin',
                'flow' => <<<TXT
                For time:
                32 Deadlifts (185/135 lb)
                32 Box Jumps (24/20 in)
                32 Push-Ups
                32 Sit-Ups
                32 Wall-Ball Shots (20/14 lb)
                32 Pull-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ROCKET => [
                'name' => 'Rocket',
                'flow' => <<<TXT
                5 rounds for time:
                10 Thrusters (95/65 lb)
                10 Pull-Ups
                400 m Run
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_RILEY => [
                'name' => 'Riley',
                'flow' => <<<TXT
                For time:
                Run 1.5 miles
                150 Burpees
                Run 1.5 miles
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_FEEKS => [
                'name' => 'Feeks',
                'flow' => <<<TXT
                6 rounds for time:
                8 Deadlifts (225/155 lb)
                8 Handstand Push-Ups
                400 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_NED => [
                'name' => 'Ned',
                'flow' => <<<TXT
                7 rounds for time:
                11 Back Squats (185/125 lb)
                1 Rope Climb
                400 m Run
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SHAM => [
                'name' => 'Sham',
                'flow' => <<<TXT
                7 rounds for time:
                11 Deadlifts (185/135 lb)
                100 m Sprint
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_OZZY => [
                'name' => 'Ozzy',
                'flow' => <<<TXT
                5 rounds for time:
                10 Power Cleans (135/95 lb)
                10 Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_POWER_CLEAN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_JENNY => [
                'name' => 'Jenny',
                'flow' => <<<TXT
                AMRAP 20:
                20 Overhead Squats (45/35 lb)
                20 Back Squats (45/35 lb)
                400 m Run
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_OVERHEAD_SQUAT,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SPEHAR => [
                'name' => 'Spehar',
                'flow' => <<<TXT
                For time:
                100 Double-Unders
                10 Burpee Box Jump-Overs (24/20 in)
                10 Dumbbell Squat Cleans (50/35 lb)
                800 m Run
                100 Double-Unders
                10 Burpee Box Jump-Overs
                10 Dumbbell Squat Cleans
                800 m Run
                100 Double-Unders
                10 Burpee Box Jump-Overs
                10 Dumbbell Squat Cleans
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP_OVER,
                    MovementData::MOVEMENT_SQUAT_CLEAN,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_LUKE => [
                'name' => 'Luke',
                'flow' => <<<TXT
                For time:
                400 m Run
                15 Clean and Jerks (155/105 lb)
                400 m Run
                30 Toes-to-Bar
                400 m Run
                45 Wall-Ball Shots (20/14 lb)
                400 m Run
                60 Burpees
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_ROBBIE => [
                'name' => 'Robbie',
                'flow' => <<<TXT
                5 rounds for time:
                8 Power Snatches (135/95 lb)
                8 Burpee Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_POWER_SNATCH,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SHAWN => [
                'name' => 'Shawn',
                'flow' => <<<TXT
                10 rounds for time:
                13 Deadlifts (185/135 lb)
                13 Push-Ups
                13 Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_FOO => [
                'name' => 'Foo',
                'flow' => <<<TXT
                5 rounds for time:
                10 Thrusters (95/65 lb)
                10 Pull-Ups
                400 m Run
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_BOWEN => [
                'name' => 'Bowen',
                'flow' => <<<TXT
                3 rounds for time:
                800 m Run
                7 Deadlifts (275/185 lb)
                10 Burpee Box Jumps (24/20 in)
                7 Power Cleans (155/105 lb)
                10 Burpee Box Jumps
                7 Front Squats (155/105 lb)
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_POWER_CLEAN,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_GAZA => [
                'name' => 'Gaza',
                'flow' => <<<TXT
                5 rounds for time:
                35 Kettlebell Swings (53/35 lb)
                30 Push-Ups
                25 Pull-Ups
                20 Box Jumps (24/20 in)
                400 m Run
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_CRAIN => [
                'name' => 'Crain',
                'flow' => <<<TXT
                2 rounds for time:
                34 Push-Ups
                50 Deadlifts (135/95 lb)
                50 Box Jumps (24/20 in)
                50 Sit-Ups
                50 Hang Power Cleans (135/95 lb)
                50 Double-Unders
                1 mile Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_HANG_POWER_CLEAN,
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_CAPOOT => [
                'name' => 'Capoot',
                'flow' => <<<TXT
                For time:
                100 Push-Ups
                800 m Run
                75 Push-Ups
                1200 m Run
                50 Push-Ups
                1600 m Run
                25 Push-Ups
                2000 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_HALL => [
                'name' => 'Hall',
                'flow' => <<<TXT
                5 rounds for time:
                3 Cleans (225/155 lb)
                200 m Sprint
                20 Kettlebell Snatches (53/35 lb)
                3 Cleans
                200 m Sprint
                20 Kettlebell Snatches
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_SERVAIS => [
                'name' => 'Servais',
                'flow' => <<<TXT
                AMRAP 20:
                5 Pull-Ups
                10 Push-Ups
                15 Air Squats
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_PK => [
                'name' => 'PK',
                'flow' => <<<TXT
                5 rounds for time:
                10 Back Squats (225/155 lb)
                10 Deadlifts (225/155 lb)
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_MARCO => [
                'name' => 'Marco',
                'flow' => <<<TXT
                3 rounds for time:
                21 Pull-Ups
                15 Handstand Push-Ups
                9 Thrusters (135/95 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_RENE => [
                'name' => 'Rene',
                'flow' => <<<TXT
                7 rounds for time:
                Run 400 m
                21 Walking Lunges
                15 Pull-Ups
                9 Burpees
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_WALKING_LUNGE,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_PIKE => [
                'name' => 'Pike',
                'flow' => <<<TXT
                5 rounds for time:
                10 Pike Push-Ups
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PIKE_PUSH_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_KUTSCHBACH => [
                'name' => 'Kutschbach',
                'flow' => <<<TXT
                7 rounds for time:
                11 Deadlifts (185/135 lb)
                11 Pull-Ups
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_JENNIFER => [
                'name' => 'Jennifer',
                'flow' => <<<TXT
                AMRAP 26:
                10 Pull-Ups
                15 Kettlebell Swings (53/35 lb)
                20 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 26,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_HORTON => [
                'name' => 'Horton',
                'flow' => <<<TXT
                9 rounds for time:
                9 Bar Muscle-Ups
                11 Clean and Jerks (155/105 lb)
                50-yard Buddy Carry
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                    ImplementData::IMPLEMENT_PARTNER,
                ],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                    MovementData::MOVEMENT_BUDDY_CARRY,
                ],
            ],
            self::SIMPLE_WORKOUT_SCOOTER => [
                'name' => 'Scooter',
                'flow' => <<<TXT
                AMRAP 30:
                30 Double-Unders
                15 Pull-Ups
                15 Push-Ups
                100 m Sprint
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_MATT_16 => [
                'name' => 'Matt 16',
                'flow' => <<<TXT
                4 rounds for time:
                16 Deadlifts (185/135 lb)
                16 Box Jumps (24/20 in)
                16 Push-Ups
                400 m Run
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_TUP => [
                'name' => 'Tup',
                'flow' => <<<TXT
                4 rounds for time:
                15 Pull-Ups
                15 Deadlifts (185/135 lb)
                15 Hang Power Cleans (135/95 lb)
                15 Front Squats (135/95 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANG_POWER_CLEAN,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HARPER => [
                'name' => 'Harper',
                'flow' => <<<TXT
                AMRAP 23:
                9 Chest-to-Bar Pull-Ups
                15 Power Cleans (135/95 lb)
                21 Air Squats
                TXT,
                'timeCap' => 23,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_CHEST_TO_BAR_PULL_UP,
                    MovementData::MOVEMENT_POWER_CLEAN,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_SISSON => [
                'name' => 'Sisson',
                'flow' => <<<TXT
                AMRAP 20:
                1 Rope Climb
                5 Burpees
                200 m Run
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_TERRY => [
                'name' => 'Terry',
                'flow' => <<<TXT
                5 rounds for time:
                100 m Farmer Carry (2x45/35 lb dumbbells)
                24 Burpees
                19 Kettlebell Swings (53/35 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_FARMER_CARRY,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                ],
            ],
            self::SIMPLE_WORKOUT_BIG_SEXY => [
                'name' => 'Big Sexy',
                'flow' => <<<TXT
                5 rounds for time:
                5 Deadlifts (315/225 lb)
                5 Back Squats (225/155 lb)
                5 Bench Presses (225/155 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_BENCH_PRESS,
                ],
            ],
            self::SIMPLE_WORKOUT_WOEHLKE => [
                'name' => 'Woehlke',
                'flow' => <<<TXT
                3 rounds for time:
                12 Deadlifts (225/155 lb)
                9 Hang Power Cleans (135/95 lb)
                6 Push Jerks (135/95 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANG_POWER_CLEAN,
                    MovementData::MOVEMENT_PUSH_JERK,
                ],
            ],
            self::SIMPLE_WORKOUT_MAUPIN => [
                'name' => 'Maupin',
                'flow' => <<<TXT
                4 rounds for time:
                800 m Run
                49 Push-Ups
                49 Sit-Ups
                49 Air Squats
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HILDY => [
                'name' => 'Hildy',
                'flow' => <<<TXT
                For time:
                100 Wall-Ball Shots (20/14 lb)
                75 Power Cleans (135/95 lb)
                50 Box Jumps (24/20 in)
                25 Strict Pull-Ups
                100 Wall-Ball Shots
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                    MovementData::MOVEMENT_POWER_CLEAN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_TJ => [
                'name' => 'TJ',
                'flow' => <<<TXT
                10 rounds for time:
                10 Bench Presses (225/155 lb)
                10 Back Squats (225/155 lb)
                10 Deadlifts (225/155 lb)
                TXT,
                'timeCap' => 50,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BENCH_PRESS,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_DEADLIFT,
                ],
            ],
            self::SIMPLE_WORKOUT_MONTI => [
                'name' => 'Monti',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_DVB => [
                'name' => 'DVB',
                'flow' => <<<TXT
                3 rounds for time:
                15 Deadlifts (225/155 lb)
                15 Box Jumps (24/20 in)
                15 Pull-Ups
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_NICKMAN => [
                'name' => 'Nickman',
                'flow' => <<<TXT
                4 rounds for time:
                20 Deadlifts (185/135 lb)
                20 Box Jumps (24/20 in)
                20 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_MARSTON => [
                'name' => 'Marston',
                'flow' => <<<TXT
                AMRAP 20:
                1 Deadlift (405/285 lb)
                10 Toes-to-Bar
                15 Bar-Facing Burpees
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_BURPEE_OVER_FACING,
                ],
            ],
            self::SIMPLE_WORKOUT_ARTIE => [
                'name' => 'Artie',
                'flow' => <<<TXT
                AMRAP 20:
                5 Pull-Ups
                10 Push-Ups
                15 Squats
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HOLLYWOOD => [
                'name' => 'Hollywood',
                'flow' => <<<TXT
                5 rounds for time:
                22 Deadlifts (185/135 lb)
                22 Box Jumps (24/20 in)
                22 Pull-Ups
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_MANUEL => [
                'name' => 'Manuel',
                'flow' => <<<TXT
                5 rounds for time:
                50 Box Jumps (24/20 in)
                50 Push-Ups
                50 Sit-Ups
                400 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_TIFF => [
                'name' => 'Tiff',
                'flow' => <<<TXT
                AMRAP 25:
                1 Rope Climb
                2 Deadlifts (185/135 lb)
                3 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROPE_CLIMB,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_PAUL_PENA => [
                'name' => 'Paul Pena',
                'flow' => <<<TXT
                7 rounds for time:
                100 m Sprint
                19 Deadlifts (185/135 lb)
                19 Box Jumps (24/20 in)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                ],
            ],
            self::SIMPLE_WORKOUT_YETI => [
                'name' => 'Yeti',
                'flow' => <<<TXT
                5 rounds for time:
                25 Pull-Ups
                25 Push-Ups
                25 Sit-Ups
                25 Air Squats
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_LIAM => [
                'name' => 'Liam',
                'flow' => <<<TXT
                For time:
                800 m Run
                100 Sit-Ups
                800 m Run
                100 Push-Ups
                800 m Run
                100 Air Squats
                800 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_WES => [
                'name' => 'Wes',
                'flow' => <<<TXT
                4 rounds for time:
                800 m Run
                40 Squats
                20 Kettlebell Swings (53/35 lb)
                10 Pull-Ups
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AIR_SQUAT,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_MIRON => [
                'name' => 'Miron',
                'flow' => <<<TXT
                3 rounds for time:
                800 m Run
                23 Back Squats (135/95 lb)
                23 Pull-Ups
                23 Burpees
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BACK_SQUAT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_PAT => [
                'name' => 'Pat',
                'flow' => <<<TXT
                6 rounds for time:
                25 Pull-Ups
                50 Push-Ups
                100 Squats
                400 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_SCOTTY => [
                'name' => 'Scotty',
                'flow' => <<<TXT
                11 rounds for time:
                11 Deadlifts (185/135 lb)
                11 Pull-Ups
                11 Push-Ups
                200 m Run
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_RICH => [
                'name' => 'Rich',
                'flow' => <<<TXT
                3 rounds for time:
                1000 m Row
                10 Burpee Box Jumps (24/20 in)
                10 Power Cleans (135/95 lb)
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROWER,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_BURPEE_BOX_JUMP,
                    MovementData::MOVEMENT_POWER_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_DALLAS_5 => [
                'name' => 'Dallas 5',
                'flow' => <<<TXT
                5 rounds for time:
                30 Box Jumps (24/20 in)
                20 Push-Ups
                30 Sit-Ups
                400 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_DUNN => [
                'name' => 'Dunn',
                'flow' => <<<TXT
                4 rounds for time:
                800 m Run
                30 Push-Ups
                30 Sit-Ups
                30 Air Squats
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_KEV => [
                'name' => 'Kev',
                'flow' => <<<TXT
                8 rounds for time:
                50 m Bear Crawl
                50 m Walking Lunges
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_BEAR_CRAWL,
                    MovementData::MOVEMENT_WALKING_LUNGE,
                ],
            ],
            self::SIMPLE_WORKOUT_EMILY => [
                'name' => 'Emily',
                'flow' => <<<TXT
                10 rounds for time:
                30 Double-Unders
                15 Pull-Ups
                15 Push-Ups
                100 m Sprint
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_ANDY => [
                'name' => 'Andy',
                'flow' => <<<TXT
                5 rounds for time:
                25 Pull-Ups
                50 Push-Ups
                75 Squats
                100 m Sprint
                TXT,
                'timeCap' => 45,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_VIOLA => [
                'name' => 'Viola',
                'flow' => <<<TXT
                AMRAP 20:
                400 m Run
                11 Power Snatches (95/65 lb)
                17 Pull-Ups
                13 Thrusters (95/65 lb)
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_POWER_SNATCH,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_THRUSTER,
                ],
            ],
            self::SIMPLE_WORKOUT_COFFLAND => [
                'name' => 'Coffland',
                'flow' => <<<TXT
                For time:
                Hang from Pull-Up Bar for 6 minutes
                Every time you drop:
                800 m Run
                30 Push-Ups
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEAD_HANG,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_LYON => [
                'name' => 'The Lyon',
                'flow' => <<<TXT
                5 rounds for time:
                7 Squat Cleans (165/115 lb)
                7 Shoulder-to-Overheads (165/115 lb)
                7 Burpee Chest-to-Bar Pull-Ups
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_SQUAT_CLEAN,
                    MovementData::MOVEMENT_SHOULDER_TO_OVERHEAD,
                    MovementData::MOVEMENT_BURPEE_CHEST_TO_BAR_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_T => [
                'name' => 'T',
                'flow' => <<<TXT
                4 rounds for time:
                100 m Farmer Carry (2x45/35 lb dumbbells)
                24 Burpees
                19 Kettlebell Swings (53/35 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_FARMER_CARRY,
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                ],
            ],
            self::SIMPLE_WORKOUT_HAVANA => [
                'name' => 'Havana',
                'flow' => <<<TXT
                AMRAP 25:
                150 Double-Unders
                50 Push-Ups
                15 Power Cleans (185/125 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_POWER_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_TAMA => [
                'name' => 'Tama',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_OTIS => [
                'name' => 'Otis',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_JOSIE => [
                'name' => 'Josie',
                'flow' => <<<TXT
                3 rounds for time:
                1 mile Run
                49 Push-Ups
                49 Sit-Ups
                49 Air Squats
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_DORK => [
                'name' => 'Dork',
                'flow' => <<<TXT
                6 rounds for time:
                60 Double-Unders
                30 Kettlebell Swings (53/35 lb)
                15 Burpees
                TXT,
                'timeCap' => 35,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_BURPEE,
                ],
            ],
            self::SIMPLE_WORKOUT_BERT => [
                'name' => 'Bert',
                'flow' => <<<TXT
                For time:
                50 Burpees
                400 m Run
                100 Push-Ups
                400 m Run
                150 Walking Lunges
                400 m Run
                200 Air Squats
                400 m Run
                150 Walking Lunges
                400 m Run
                100 Push-Ups
                400 m Run
                50 Burpees
                TXT,
                'timeCap' => 90,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [],
                'movements' => [
                    MovementData::MOVEMENT_BURPEE,
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_WALKING_LUNGE,
                    MovementData::MOVEMENT_AIR_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_WADE => [
                'name' => 'Wade',
                'flow' => <<<TXT
                4 rounds for time:
                800 m Run
                21 Box Jumps (24/20 in)
                21 Push-Ups
                15 Pull-Ups
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_FOURNIER => [
                'name' => 'Fournier',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_LARRY => [
                'name' => 'Larry',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_KELLY_BROWN => [
                'name' => 'Kelly Brown',
                'flow' => <<<TXT
                5 rounds for time:
                400 m Run
                30 Box Jumps (24/20 in)
                30 Wall-Ball Shots (20/14 lb)
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                ],
            ],
            self::SIMPLE_WORKOUT_KERRIE => [
                'name' => 'Kerrie',
                'flow' => <<<TXT
                10 rounds for time:
                100 m Run
                100 m Farmer Carry (2x35/25 lb dumbbells)
                100 m Sled Push (180/125 lb)
                100 m Run
                100 m Bear Crawl
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_SLED,
                ],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_FARMER_CARRY,
                    MovementData::MOVEMENT_SLED_PUSH,
                    MovementData::MOVEMENT_BEAR_CRAWL,
                ],
            ],
            self::SIMPLE_WORKOUT_MARTIN => [
                'name' => 'Martin',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_LAURA => [
                'name' => 'Laura',
                'flow' => <<<TXT
                AMRAP 20:
                30 Double-Unders
                15 Pull-Ups
                15 Push-Ups
                100 m Sprint
                TXT,
                'timeCap' => 20,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_LORENZO => [
                'name' => 'Lorenzo',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_PEYTON => [
                'name' => 'Peyton',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_MAXTON => [
                'name' => 'Maxton',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_EVA_STRONG => [
                'name' => 'Eva Strong',
                'flow' => <<<TXT
                5 rounds for time:
                24 Double-Unders
                19 Toes-to-Bar
                6 Clean and Jerks (135/95 lb)
                400 m Run
                TXT,
                'timeCap' => 40,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_BARBELL,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_TOES_TO_BAR,
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                    MovementData::MOVEMENT_RUN,
                ],
            ],
            self::SIMPLE_WORKOUT_CHAD1000X => [
                'name' => 'Chad1000X',
                'flow' => <<<TXT
                For time:
                1000 Box Step-Ups (20 in)
                TXT,
                'timeCap' => 60,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movements' => [
                    MovementData::MOVEMENT_BOX_STEP_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_TPT9000 => [
                'name' => 'TPT9000',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_GARBO => [
                'name' => 'Garbo',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_MCCARTNEY => [
                'name' => 'McCartney',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WESLEY => [
                'name' => 'Wesley',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_HAMMY => [
                'name' => 'Hammy',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_TRIPLE_DEUCE => [
                'name' => 'Triple Deuce',
                'flow' => <<<TXT
                3 rounds for time:
                22 Deadlifts (185/135 lb)
                22 Box Jumps (24/20 in)
                22 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_K27 => [
                'name' => 'K27',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BURIAK => [
                'name' => 'Buriak',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_ODA_7313 => [
                'name' => 'ODA 7313',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_GOOSE => [
                'name' => 'Goose',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_PIKEY => [
                'name' => 'Pikey',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_GALE_FORCE => [
                'name' => 'Gale Force',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_NORTHRUP => [
                'name' => 'Northrup',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_FERN => [
                'name' => 'Fern',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_FINSETH => [
                'name' => 'Finseth',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_GAGE => [
                'name' => 'Gage',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_JOSH_O => [
                'name' => 'Josh O',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_WHITT => [
                'name' => 'Whitt',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_RYAN_SO => [
                'name' => 'Ryan SO',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_HOOVER => [
                'name' => 'Hoover',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_CITY_100 => [
                'name' => 'City 100',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ALEC => [
                'name' => 'Alec',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_MULLER => [
                'name' => 'Muller',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_DOMINIC_J_HALL => [
                'name' => 'Dominic J Hall',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_JONATHAN_FARMER => [
                'name' => 'Jonathan Farmer',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_RYAN_COMAS => [
                'name' => 'Ryan Comas',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_TIMOTHY_HELTON => [
                'name' => 'Timothy Helton',
                'flow' => <<<TXT
                5 rounds for time:
                10 Deadlifts (225/155 lb)
                10 Box Jumps (24/20 in)
                10 Pull-Ups
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_TOPSY => [
                'name' => 'Topsy',
                'flow' => <<<TXT
                3 rounds for time:
                21 Deadlifts (185/135 lb)
                15 Pull-Ups
                9 Front Squats (185/135 lb)
                TXT,
                'timeCap' => 25,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_HERO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_FRONT_SQUAT,
                ],
            ],
        ];
    }
}
