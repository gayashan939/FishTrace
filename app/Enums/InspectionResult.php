<?php

namespace App\Enums;

enum InspectionResult: string
{
    case PASSED = 'PASSED';
    case FAILED = 'FAILED';
    case CONDITIONAL = 'CONDITIONAL';
}
