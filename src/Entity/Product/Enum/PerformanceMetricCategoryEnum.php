<?php

namespace App\Entity\Product\Enum;

enum PerformanceMetricCategoryEnum: string
{
    case STRENGTH = 'strength';
    case WEIGHTLIFTING = 'weightlifting';
    case WEIGHTED_GYMNASTICS = 'weighted_gymnastics';
    case GYMNASTICS_SKILL = 'gymnastics_skill';
    case GYMNASTICS_CAPACITY = 'gymnastics_capacity';
    case CARDIO = 'cardio';
}
