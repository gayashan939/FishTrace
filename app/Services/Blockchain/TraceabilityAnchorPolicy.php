<?php

namespace App\Services\Blockchain;

class TraceabilityAnchorPolicy
{
    public const EVENT_TYPES = [
        'CATCH_REGISTERED',
        'BATCH_CREATED',
        'PROCESSOR_ACCEPTED',
        'QUALITY_INSPECTION_COMPLETED',
        'PROCESSING_COMPLETED',
        'TRANSPORT_STARTED',
        'COLD_CHAIN_VIOLATION',
        'TRANSPORT_COMPLETED',
        'RETAIL_RECEIVED',
        'BATCH_RECALLED',
        'SENSOR_SUMMARY_ANCHORED',
        'AI_PREDICTION_ANCHORED',
    ];

    public function supports(string $eventType): bool
    {
        return in_array($eventType, self::EVENT_TYPES, true);
    }
}
