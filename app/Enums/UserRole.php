<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case FISHER = 'FISHER';
    case PROCESSOR = 'PROCESSOR';
    case TRANSPORTER = 'TRANSPORTER';
    case RETAILER = 'RETAILER';
    case INSPECTOR = 'INSPECTOR';
}
