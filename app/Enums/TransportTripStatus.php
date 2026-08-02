<?php

namespace App\Enums;

enum TransportTripStatus: string
{
    case DRAFT = 'DRAFT';
    case READY = 'READY';
    case ACTIVE = 'ACTIVE';
    case DELIVERED = 'DELIVERED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
