<?php

namespace App\Entity\Product\Enum;

enum ProgrammingGenerationRequestStatusEnum: string
{
    case DRAFT = 'draft';
    case WAITING_ANALYSIS = 'waiting_analysis';
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
