<?php

namespace App\Enums;

enum ProcessingStepType: string
{
    case CLEANING = 'CLEANING';
    case GRADING = 'GRADING';
    case FREEZING = 'FREEZING';
    case PACKAGING = 'PACKAGING';

    public function sequence(): int
    {
        return match ($this) {
            self::CLEANING => 1,
            self::GRADING => 2,
            self::FREEZING => 3,
            self::PACKAGING => 4,
        };
    }
}
