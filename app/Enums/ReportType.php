<?php

namespace App\Enums;

enum ReportType: string
{
    case CATCH_VOLUME = 'catch-volume';
    case SPECIES_DISTRIBUTION = 'species-distribution';
    case BATCH_STATUS = 'batch-status';
    case PROCESSING_YIELD = 'processing-yield';
    case QUALITY_GRADES = 'quality-grades';
    case TRANSPORT_PERFORMANCE = 'transport-performance';
    case COLD_CHAIN_VIOLATIONS = 'cold-chain-violations';
    case DEVICE_UPTIME = 'device-uptime';
    case FIREBASE_IMPORT_STATUS = 'firebase-import-status';
    case AI_RISK_DISTRIBUTION = 'ai-risk-distribution';
    case INVENTORY = 'inventory';
    case SALES = 'sales';
    case RECALLS = 'recalls';
    case BLOCKCHAIN_STATUS = 'blockchain-status';
}
