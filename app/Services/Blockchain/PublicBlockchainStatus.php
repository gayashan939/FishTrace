<?php

namespace App\Services\Blockchain;

use App\Models\BlockchainVerification;
use App\Models\FishBatch;
use App\Models\TraceabilityEvent;
use Illuminate\Support\Facades\DB;

class PublicBlockchainStatus
{
    /** @return array<string, mixed> */
    public function forBatch(FishBatch $batch): array
    {
        $eventIds = TraceabilityEvent::query()
            ->where('fish_batch_id', $batch->id)
            ->whereIn('event_type', TraceabilityAnchorPolicy::EVENT_TYPES)
            ->pluck('id');
        if ($eventIds->isEmpty()) {
            return ['status' => 'NOT_APPLICABLE', 'eligible_event_count' => 0, 'anchored_event_count' => 0, 'verified_at' => null];
        }

        $transactions = DB::table('blockchain_event_anchors')
            ->join('blockchain_transactions', 'blockchain_transactions.id', '=', 'blockchain_event_anchors.blockchain_transaction_id')
            ->whereIn('blockchain_event_anchors.traceability_event_id', $eventIds)
            ->get(['blockchain_transactions.id', 'blockchain_transactions.status']);
        $transactionIds = $transactions->pluck('id')->unique()->values();
        $validTransactionIds = BlockchainVerification::query()
            ->whereIn('blockchain_transaction_id', $transactionIds)
            ->where('is_valid', true)
            ->distinct()
            ->pluck('blockchain_transaction_id');

        $status = match (true) {
            $transactions->contains('status', 'FAILED') => 'FAILED',
            $transactions->count() < $eventIds->count() => 'PENDING',
            $transactions->every(fn (object $transaction): bool => $transaction->status === 'CONFIRMED') && $validTransactionIds->count() === $transactionIds->count() => 'VERIFIED',
            default => 'PENDING',
        };
        $verifiedAt = BlockchainVerification::query()
            ->whereIn('blockchain_transaction_id', $transactionIds)
            ->where('is_valid', true)
            ->max('verified_at');

        return [
            'status' => $status,
            'eligible_event_count' => $eventIds->count(),
            'anchored_event_count' => $transactions->count(),
            'verified_at' => $verifiedAt,
            'note' => 'Only canonical traceability-event hashes and transaction references are anchored.',
        ];
    }
}
