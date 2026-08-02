<?php

namespace App\Enums;

enum StockMovementType: string
{
    case RECEIVED = 'RECEIVED';
    case RESERVED = 'RESERVED';
    case RELEASED = 'RELEASED';
    case SOLD = 'SOLD';
    case RECALLED = 'RECALLED';
    case EXPIRED = 'EXPIRED';
    case ADJUSTED_IN = 'ADJUSTED_IN';
    case ADJUSTED_OUT = 'ADJUSTED_OUT';
}
