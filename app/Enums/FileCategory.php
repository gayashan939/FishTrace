<?php

namespace App\Enums;

enum FileCategory: string
{
    case BOAT_IMAGE = 'BOAT_IMAGE';
    case CATCH_IMAGE = 'CATCH_IMAGE';
    case INSPECTION_IMAGE = 'INSPECTION_IMAGE';
    case PROCESSING_IMAGE = 'PROCESSING_IMAGE';
    case BATCH_DOCUMENT = 'BATCH_DOCUMENT';
    case CERTIFICATE = 'CERTIFICATE';
    case DELIVERY_IMAGE = 'DELIVERY_IMAGE';
    case DELIVERY_SIGNATURE = 'DELIVERY_SIGNATURE';
    case REPORT_EXPORT = 'REPORT_EXPORT';

    public function isImage(): bool
    {
        return in_array($this, [self::BOAT_IMAGE, self::CATCH_IMAGE, self::INSPECTION_IMAGE, self::PROCESSING_IMAGE, self::DELIVERY_IMAGE, self::DELIVERY_SIGNATURE], true);
    }
}
