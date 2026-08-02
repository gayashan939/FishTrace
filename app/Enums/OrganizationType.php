<?php

namespace App\Enums;

enum OrganizationType: string
{
    case REGULATOR = 'REGULATOR';
    case FISHER = 'FISHER';
    case PROCESSOR = 'PROCESSOR';
    case TRANSPORTER = 'TRANSPORTER';
    case RETAILER = 'RETAILER';
    case INSPECTOR = 'INSPECTOR';
}
