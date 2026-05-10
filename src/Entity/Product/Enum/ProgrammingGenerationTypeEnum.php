<?php

namespace App\Entity\Product\Enum;

enum ProgrammingGenerationTypeEnum: string
{
    case INDIVIDUAL = 'individual';
    case BOX = 'box';
    case COMPETITION = 'competition';
}
