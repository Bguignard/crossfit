<?php

namespace App\Entity\Product\Enum;

enum PerformanceMetricKeyEnum: string
{
    case BACK_SQUAT_1RM = 'back_squat_1rm';
    case BACK_SQUAT_5RM = 'back_squat_5rm';
    case FRONT_SQUAT_1RM = 'front_squat_1rm';
    case FRONT_SQUAT_5RM = 'front_squat_5rm';
    case OVERHEAD_SQUAT_1RM = 'overhead_squat_1rm';
    case OVERHEAD_SQUAT_5RM = 'overhead_squat_5rm';
    case DEADLIFT_1RM = 'deadlift_1rm';
    case DEADLIFT_5RM = 'deadlift_5rm';
    case BENCH_PRESS_1RM = 'bench_press_1rm';
    case BENCH_PRESS_5RM = 'bench_press_5rm';
    case STRICT_PRESS_1RM = 'strict_press_1rm';
    case STRICT_PRESS_5RM = 'strict_press_5rm';
    case PUSH_PRESS_1RM = 'push_press_1rm';
    case PUSH_PRESS_5RM = 'push_press_5rm';
    case THRUSTER_1RM = 'thruster_1rm';
    case THRUSTER_5RM = 'thruster_5rm';

    case POWER_CLEAN_1RM = 'power_clean_1rm';
    case SQUAT_CLEAN_1RM = 'squat_clean_1rm';
    case MUSCLE_CLEAN_1RM = 'muscle_clean_1rm';
    case POWER_SNATCH_1RM = 'power_snatch_1rm';
    case SQUAT_SNATCH_1RM = 'squat_snatch_1rm';
    case MUSCLE_SNATCH_1RM = 'muscle_snatch_1rm';

    case WEIGHTED_PULL_UP_1RM = 'weighted_pull_up_1rm';
    case WEIGHTED_DIP_1RM = 'weighted_dip_1rm';

    case STRICT_PULL_UP = 'strict_pull_up';
    case KIPPING_PULL_UP = 'kipping_pull_up';
    case BUTTERFLY_PULL_UP = 'butterfly_pull_up';
    case KIPPING_HANDSTAND_PUSH_UP = 'kipping_handstand_push_up';
    case STRICT_HANDSTAND_PUSH_UP = 'strict_handstand_push_up';
    case KIPPING_BAR_MUSCLE_UP = 'kipping_bar_muscle_up';
    case KIPPING_RING_MUSCLE_UP = 'kipping_ring_muscle_up';
    case PULL_OVER = 'pull_over';
    case STRICT_BAR_MUSCLE_UP = 'strict_bar_muscle_up';
    case STRICT_RING_MUSCLE_UP = 'strict_ring_muscle_up';
    case HANDSTAND_RAMP = 'handstand_ramp';
    case DOUBLE_UNDER = 'double_under';
    case DOUBLE_CROSSOVER = 'double_crossover';
    case FREE_HANDSTAND_PUSH_UP = 'free_handstand_push_up';

    case MAX_STRICT_PULL_UPS = 'max_strict_pull_ups';
    case MAX_KIPPING_OR_BUTTERFLY_PULL_UPS = 'max_kipping_or_butterfly_pull_ups';
    case MAX_CHEST_TO_BAR = 'max_chest_to_bar';
    case MAX_BAR_MUSCLE_UPS = 'max_bar_muscle_ups';
    case MAX_RING_MUSCLE_UPS = 'max_ring_muscle_ups';
    case MAX_STRICT_HANDSTAND_PUSH_UPS = 'max_strict_handstand_push_ups';
    case MAX_KIPPING_HANDSTAND_PUSH_UPS = 'max_kipping_handstand_push_ups';
    case MAX_PULL_OVERS = 'max_pull_overs';
    case MAX_HANDSTAND_WALK_DISTANCE = 'max_handstand_walk_distance';
    case MAX_FREE_HANDSTAND_HOLD = 'max_free_handstand_hold';

    case ROW_500M_TIME = 'row_500m_time';
    case ROW_1KM_TIME = 'row_1km_time';
    case ROW_5KM_TIME = 'row_5km_time';
    case BIKE_ERG_20MIN_WATTS = 'bike_erg_20min_watts';
    case ECHO_BIKE_10MIN_WATTS = 'echo_bike_10min_watts';
    case ECHO_BIKE_20MIN_WATTS = 'echo_bike_20min_watts';
    case ECHO_BIKE_30MIN_WATTS = 'echo_bike_30min_watts';
    case RUN_1600M_TIME = 'run_1600m_time';
    case RUN_5KM_TIME = 'run_5km_time';
    case RUN_10KM_TIME = 'run_10km_time';
    case MAX_UNBROKEN_DOUBLE_UNDERS = 'max_unbroken_double_unders';
    case MAX_WALLBALLS_UNBROKEN = 'max_wallballs_unbroken';

    public function category(): PerformanceMetricCategoryEnum
    {
        return match ($this) {
            self::BACK_SQUAT_1RM,
            self::BACK_SQUAT_5RM,
            self::FRONT_SQUAT_1RM,
            self::FRONT_SQUAT_5RM,
            self::OVERHEAD_SQUAT_1RM,
            self::OVERHEAD_SQUAT_5RM,
            self::DEADLIFT_1RM,
            self::DEADLIFT_5RM,
            self::BENCH_PRESS_1RM,
            self::BENCH_PRESS_5RM,
            self::STRICT_PRESS_1RM,
            self::STRICT_PRESS_5RM,
            self::PUSH_PRESS_1RM,
            self::PUSH_PRESS_5RM,
            self::THRUSTER_1RM,
            self::THRUSTER_5RM => PerformanceMetricCategoryEnum::STRENGTH,
            self::POWER_CLEAN_1RM,
            self::SQUAT_CLEAN_1RM,
            self::MUSCLE_CLEAN_1RM,
            self::POWER_SNATCH_1RM,
            self::SQUAT_SNATCH_1RM,
            self::MUSCLE_SNATCH_1RM => PerformanceMetricCategoryEnum::WEIGHTLIFTING,
            self::WEIGHTED_PULL_UP_1RM,
            self::WEIGHTED_DIP_1RM => PerformanceMetricCategoryEnum::WEIGHTED_GYMNASTICS,
            self::STRICT_PULL_UP,
            self::KIPPING_PULL_UP,
            self::BUTTERFLY_PULL_UP,
            self::KIPPING_HANDSTAND_PUSH_UP,
            self::STRICT_HANDSTAND_PUSH_UP,
            self::KIPPING_BAR_MUSCLE_UP,
            self::KIPPING_RING_MUSCLE_UP,
            self::PULL_OVER,
            self::STRICT_BAR_MUSCLE_UP,
            self::STRICT_RING_MUSCLE_UP,
            self::HANDSTAND_RAMP,
            self::DOUBLE_UNDER,
            self::DOUBLE_CROSSOVER,
            self::FREE_HANDSTAND_PUSH_UP => PerformanceMetricCategoryEnum::GYMNASTICS_SKILL,
            self::MAX_STRICT_PULL_UPS,
            self::MAX_KIPPING_OR_BUTTERFLY_PULL_UPS,
            self::MAX_CHEST_TO_BAR,
            self::MAX_BAR_MUSCLE_UPS,
            self::MAX_RING_MUSCLE_UPS,
            self::MAX_STRICT_HANDSTAND_PUSH_UPS,
            self::MAX_KIPPING_HANDSTAND_PUSH_UPS,
            self::MAX_PULL_OVERS,
            self::MAX_HANDSTAND_WALK_DISTANCE,
            self::MAX_FREE_HANDSTAND_HOLD => PerformanceMetricCategoryEnum::GYMNASTICS_CAPACITY,
            self::ROW_500M_TIME,
            self::ROW_1KM_TIME,
            self::ROW_5KM_TIME,
            self::BIKE_ERG_20MIN_WATTS,
            self::ECHO_BIKE_10MIN_WATTS,
            self::ECHO_BIKE_20MIN_WATTS,
            self::ECHO_BIKE_30MIN_WATTS,
            self::RUN_1600M_TIME,
            self::RUN_5KM_TIME,
            self::RUN_10KM_TIME,
            self::MAX_UNBROKEN_DOUBLE_UNDERS,
            self::MAX_WALLBALLS_UNBROKEN => PerformanceMetricCategoryEnum::CARDIO,
        };
    }

    public function valueType(): PerformanceMetricValueTypeEnum
    {
        return match ($this) {
            self::STRICT_PULL_UP,
            self::KIPPING_PULL_UP,
            self::BUTTERFLY_PULL_UP,
            self::KIPPING_HANDSTAND_PUSH_UP,
            self::STRICT_HANDSTAND_PUSH_UP,
            self::KIPPING_BAR_MUSCLE_UP,
            self::KIPPING_RING_MUSCLE_UP,
            self::PULL_OVER,
            self::STRICT_BAR_MUSCLE_UP,
            self::STRICT_RING_MUSCLE_UP,
            self::HANDSTAND_RAMP,
            self::DOUBLE_UNDER,
            self::DOUBLE_CROSSOVER,
            self::FREE_HANDSTAND_PUSH_UP => PerformanceMetricValueTypeEnum::BOOLEAN,
            self::ROW_500M_TIME,
            self::ROW_1KM_TIME,
            self::ROW_5KM_TIME,
            self::RUN_1600M_TIME,
            self::RUN_5KM_TIME,
            self::RUN_10KM_TIME,
            self::MAX_FREE_HANDSTAND_HOLD => PerformanceMetricValueTypeEnum::TIME,
            self::BIKE_ERG_20MIN_WATTS,
            self::ECHO_BIKE_10MIN_WATTS,
            self::ECHO_BIKE_20MIN_WATTS,
            self::ECHO_BIKE_30MIN_WATTS => PerformanceMetricValueTypeEnum::WATTS,
            self::MAX_HANDSTAND_WALK_DISTANCE => PerformanceMetricValueTypeEnum::DISTANCE,
            self::MAX_STRICT_PULL_UPS,
            self::MAX_KIPPING_OR_BUTTERFLY_PULL_UPS,
            self::MAX_CHEST_TO_BAR,
            self::MAX_BAR_MUSCLE_UPS,
            self::MAX_RING_MUSCLE_UPS,
            self::MAX_STRICT_HANDSTAND_PUSH_UPS,
            self::MAX_KIPPING_HANDSTAND_PUSH_UPS,
            self::MAX_PULL_OVERS,
            self::MAX_UNBROKEN_DOUBLE_UNDERS,
            self::MAX_WALLBALLS_UNBROKEN => PerformanceMetricValueTypeEnum::REPS,
            default => PerformanceMetricValueTypeEnum::LOAD,
        };
    }

    public function defaultUnit(): ?string
    {
        return match ($this->valueType()) {
            PerformanceMetricValueTypeEnum::LOAD => 'kg',
            PerformanceMetricValueTypeEnum::DISTANCE => 'm',
            PerformanceMetricValueTypeEnum::TIME => 's',
            PerformanceMetricValueTypeEnum::WATTS => 'w',
            PerformanceMetricValueTypeEnum::REPS => 'reps',
            PerformanceMetricValueTypeEnum::BOOLEAN => null,
        };
    }

    public function profilePriority(): string
    {
        return match ($this) {
            self::BACK_SQUAT_1RM,
            self::FRONT_SQUAT_1RM,
            self::DEADLIFT_1RM,
            self::STRICT_PRESS_1RM,
            self::PUSH_PRESS_1RM,
            self::POWER_CLEAN_1RM,
            self::SQUAT_CLEAN_1RM,
            self::POWER_SNATCH_1RM,
            self::SQUAT_SNATCH_1RM,
            self::STRICT_PULL_UP,
            self::KIPPING_PULL_UP,
            self::DOUBLE_UNDER,
            self::ROW_500M_TIME,
            self::RUN_5KM_TIME,
            self::BIKE_ERG_20MIN_WATTS,
            self::ECHO_BIKE_20MIN_WATTS => 'essential',
            self::BACK_SQUAT_5RM,
            self::FRONT_SQUAT_5RM,
            self::OVERHEAD_SQUAT_1RM,
            self::DEADLIFT_5RM,
            self::STRICT_PRESS_5RM,
            self::PUSH_PRESS_5RM,
            self::THRUSTER_1RM,
            self::WEIGHTED_PULL_UP_1RM,
            self::WEIGHTED_DIP_1RM,
            self::STRICT_HANDSTAND_PUSH_UP,
            self::KIPPING_HANDSTAND_PUSH_UP,
            self::KIPPING_BAR_MUSCLE_UP,
            self::KIPPING_RING_MUSCLE_UP,
            self::HANDSTAND_RAMP,
            self::MAX_STRICT_PULL_UPS,
            self::MAX_CHEST_TO_BAR,
            self::MAX_BAR_MUSCLE_UPS,
            self::MAX_RING_MUSCLE_UPS,
            self::MAX_STRICT_HANDSTAND_PUSH_UPS,
            self::MAX_KIPPING_HANDSTAND_PUSH_UPS,
            self::ROW_1KM_TIME,
            self::ROW_5KM_TIME,
            self::RUN_1600M_TIME,
            self::RUN_10KM_TIME,
            self::ECHO_BIKE_10MIN_WATTS,
            self::ECHO_BIKE_30MIN_WATTS,
            self::MAX_UNBROKEN_DOUBLE_UNDERS,
            self::MAX_WALLBALLS_UNBROKEN => 'useful',
            default => 'optional',
        };
    }

    /**
     * @return list<self>
     */
    public static function requiredStrengthMetrics(): array
    {
        return [
            self::BACK_SQUAT_1RM,
            self::BACK_SQUAT_5RM,
            self::FRONT_SQUAT_1RM,
            self::FRONT_SQUAT_5RM,
            self::OVERHEAD_SQUAT_1RM,
            self::OVERHEAD_SQUAT_5RM,
            self::DEADLIFT_1RM,
            self::DEADLIFT_5RM,
            self::BENCH_PRESS_1RM,
            self::BENCH_PRESS_5RM,
            self::STRICT_PRESS_1RM,
            self::STRICT_PRESS_5RM,
            self::PUSH_PRESS_1RM,
            self::PUSH_PRESS_5RM,
            self::THRUSTER_1RM,
            self::THRUSTER_5RM,
        ];
    }

    /**
     * @return list<self>
     */
    public static function requiredWeightliftingMetrics(): array
    {
        return [
            self::POWER_CLEAN_1RM,
            self::SQUAT_CLEAN_1RM,
            self::POWER_SNATCH_1RM,
            self::SQUAT_SNATCH_1RM,
        ];
    }

    /**
     * @return list<self>
     */
    public static function gymnasticsSkillMetrics(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $metricKey): bool => $metricKey->category() === PerformanceMetricCategoryEnum::GYMNASTICS_SKILL
        ));
    }

    /**
     * @return list<self>
     */
    public static function cardioMetrics(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $metricKey): bool => $metricKey->category() === PerformanceMetricCategoryEnum::CARDIO
        ));
    }

    public function requiredSkill(): ?self
    {
        return match ($this) {
            self::MAX_STRICT_PULL_UPS => self::STRICT_PULL_UP,
            self::MAX_CHEST_TO_BAR,
            self::MAX_KIPPING_OR_BUTTERFLY_PULL_UPS => self::KIPPING_PULL_UP,
            self::MAX_BAR_MUSCLE_UPS => self::KIPPING_BAR_MUSCLE_UP,
            self::MAX_RING_MUSCLE_UPS => self::KIPPING_RING_MUSCLE_UP,
            self::MAX_STRICT_HANDSTAND_PUSH_UPS => self::STRICT_HANDSTAND_PUSH_UP,
            self::MAX_KIPPING_HANDSTAND_PUSH_UPS => self::KIPPING_HANDSTAND_PUSH_UP,
            self::MAX_PULL_OVERS => self::PULL_OVER,
            self::MAX_HANDSTAND_WALK_DISTANCE => self::HANDSTAND_RAMP,
            self::MAX_FREE_HANDSTAND_HOLD => self::FREE_HANDSTAND_PUSH_UP,
            default => null,
        };
    }
}
