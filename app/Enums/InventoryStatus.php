<?php

namespace App\Enums;

enum InventoryStatus: string
{
    case IN_STOCK = 'IN_STOCK';
    case RESERVED = 'RESERVED';
    case SOLD_OUT = 'SOLD_OUT';
    case RECALLED = 'RECALLED';
    case EXPIRED = 'EXPIRED';
}
