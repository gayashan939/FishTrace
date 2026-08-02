<?php

namespace App\Services\Blockchain;

use App\Contracts\Blockchain\BlockchainClient;
use App\Enums\NotificationType;
use App\Models\BlockchainTransaction;
use App\Models\BlockchainVerification;
use App\Models\TraceabilityEvent;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TraceabilityAnchorService
{
    public function __construct(private BlockchainClient $client, private CanonicalJsonSerializer $canonical, private OperationalNotifier $notifier, private AuditLogger $audit) {}

    public function anchor(TraceabilityEvent $event): BlockchainTransaction
    {
        $hash = $this->eventHash($event);
        [$transaction, $shouldSubmit] = DB::transaction(function () use ($event, $hash): array {
            $transaction = BlockchainTransaction::query()->where('event_hash', $hash)->lockForUpdate()->first();
            $shouldSubmit = $transaction === null || $transaction->status === 'FAILED';
            if ($transaction === null) {
                $transaction = BlockchainTransaction::query()->create(['event_hash' => $hash, 'network' => (string) config('fishtrace.blockchain.network'), 'contract_address' => config('fishtrace.blockchain.contract'), 'status' => 'PENDING', 'attempts' => 0]);
            } elseif ($shouldSubmit) {
                $transaction->update(['status' => 'PENDING', 'error_message' => null]);
            }
            DB::table('blockchain_event_anchors')->insertOrIgnore(['id' => (string) Str::uuid(), 'traceability_event_id' => $event->id, 'blockchain_transaction_id' => $transaction->id, 'created_at' => now(), 'updated_at' => now()]);

            return [$transaction, $shouldSubmit];
        });
        if (! $shouldSubmit) {
            return $transaction;
        }

        $submission = $this->client->submit($hash);

        return DB::transaction(function () use ($transaction, $submission): BlockchainTransaction {
            $locked = BlockchainTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $locked->update(['transaction_reference' => $submission['transactionReference'], 'network' => $submission['network'], 'contract_address' => config('fishtrace.blockchain.contract'), 'status' => $submission['status'], 'attempts' => $locked->attempts + 1, 'error_message' => null, 'submitted_at' => now(), 'confirmed_at' => $submission['status'] === 'CONFIRMED' ? now() : null]);

            return $locked->fresh() ?? $locked;
        });
    }

    public function verify(BlockchainTransaction $transaction): bool
    {
        $verified = $transaction->transaction_reference !== null && $this->client->verify($transaction->transaction_reference, $transaction->event_hash);
        BlockchainVerification::create(['blockchain_transaction_id' => $transaction->id, 'is_valid' => $verified, 'response' => ['driver' => config('fishtrace.blockchain.driver')], 'verified_at' => now()]);
        if ($verified && $transaction->status !== 'CONFIRMED') {
            $transaction->update(['status' => 'CONFIRMED', 'confirmed_at' => now(), 'error_message' => null]);
        }
        if (! $verified) {
            $organizationId = DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('fish_batches', 'fish_batches.id', '=', 'traceability_events.fish_batch_id')->where('blockchain_event_anchors.blockchain_transaction_id', $transaction->id)->value('fish_batches.organization_id');
            if (is_string($organizationId)) {
                $this->audit->record('BLOCKCHAIN_VERIFICATION_FAILED', $transaction, null, ['transaction_reference' => $transaction->transaction_reference], organizationId: $organizationId);
                $this->notifier->organizationOnce('blockchain-verification:'.$transaction->id, $organizationId, NotificationType::BLOCKCHAIN_VERIFICATION_FAILURE, 'Blockchain verification failed', 'A traceability anchor could not be verified.', ['blockchain_transaction_id' => $transaction->id]);
            }
        }

        return $verified;
    }

    public function recordFailure(TraceabilityEvent $event, Throwable $exception, int $attempts = 1): BlockchainTransaction
    {
        $hash = $this->eventHash($event);
        $message = 'Blockchain anchoring failed ('.class_basename($exception).').';
        $transaction = DB::transaction(function () use ($event, $hash, $message, $attempts): BlockchainTransaction {
            $transaction = BlockchainTransaction::query()->firstOrCreate(
                ['event_hash' => $hash],
                ['network' => (string) config('fishtrace.blockchain.network'), 'contract_address' => config('fishtrace.blockchain.contract'), 'status' => 'FAILED'],
            );
            $transaction->update(['status' => 'FAILED', 'attempts' => max($transaction->attempts, $attempts, 1), 'error_message' => $message]);
            DB::table('blockchain_event_anchors')->insertOrIgnore(['id' => (string) Str::uuid(), 'traceability_event_id' => $event->id, 'blockchain_transaction_id' => $transaction->id, 'created_at' => now(), 'updated_at' => now()]);

            return $transaction;
        });

        $organizationId = $event->batch()->value('organization_id');
        if (is_string($organizationId)) {
            $this->audit->record('BLOCKCHAIN_ANCHOR_FAILED', $transaction, null, ['status' => 'FAILED', 'error_message' => $message, 'attempts' => $transaction->attempts], organizationId: $organizationId);
            $this->notifier->organizationOnce('blockchain-anchor-failure:'.$transaction->id, $organizationId, NotificationType::BLOCKCHAIN_SUBMISSION_FAILURE, 'Blockchain anchoring unavailable', 'A traceability event could not be anchored after retries.', ['blockchain_transaction_id' => $transaction->id, 'traceability_event_id' => $event->id]);
        }

        return $transaction;
    }

    public function recordVerificationFailure(BlockchainTransaction $transaction, Throwable $exception): void
    {
        $message = 'Blockchain verification polling failed ('.class_basename($exception).').';
        $transaction->update(['error_message' => $message]);
        $organizationId = DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('fish_batches', 'fish_batches.id', '=', 'traceability_events.fish_batch_id')->where('blockchain_event_anchors.blockchain_transaction_id', $transaction->id)->value('fish_batches.organization_id');
        if (is_string($organizationId)) {
            $this->audit->record('BLOCKCHAIN_POLL_FAILED', $transaction, null, ['status' => $transaction->status, 'error_message' => $message], organizationId: $organizationId);
            $this->notifier->organizationOnce('blockchain-poll-failure:'.$transaction->id, $organizationId, NotificationType::BLOCKCHAIN_VERIFICATION_FAILURE, 'Blockchain verification polling failed', 'A submitted traceability anchor could not be checked after retries.', ['blockchain_transaction_id' => $transaction->id]);
        }
    }

    public function eventHash(TraceabilityEvent $event): string
    {
        $payload = ['id' => $event->id, 'batchId' => $event->fish_batch_id, 'type' => $event->event_type, 'publicData' => $event->public_data, 'occurredAt' => $event->occurred_at];

        return hash('sha256', $this->canonical->serialize($payload));
    }
}
