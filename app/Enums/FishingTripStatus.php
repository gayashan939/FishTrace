<?php

namespace App\Enums;

enum FishingTripStatus: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
