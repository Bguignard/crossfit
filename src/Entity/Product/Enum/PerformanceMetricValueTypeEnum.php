<?php

namespace App\Entity\Product\Enum;

enum PerformanceMetricValueTypeEnum: string
{
    case LOAD = 'load';
    case BOOLEAN = 'boolean';
    case REPS = 'reps';
    case DISTANCE = 'distance';
    case TIME = 'time';
    case WATTS = 'watts';
}
