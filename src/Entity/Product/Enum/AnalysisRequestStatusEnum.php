<?php

namespace App\Entity\Product\Enum;

enum AnalysisRequestStatusEnum: string
{
    case DRAFT = 'draft';
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
