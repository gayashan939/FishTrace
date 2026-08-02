<?php

namespace App\Enums;

enum ProcessingStatus: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case QUALITY_HOLD = 'QUALITY_HOLD';
    case COMPLETED = 'COMPLETED';
}
