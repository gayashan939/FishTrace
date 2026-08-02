<?php

namespace App\Jobs;

use App\Models\BlockchainTransaction;
use App\Services\Blockchain\TraceabilityAnchorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PollBlockchainTransaction implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 30;

    public int $uniqueFor = 240;

    public array $backoff = [30, 120, 300];

    public function __construct(public string $transactionId) {}

    public function uniqueId(): string
    {
        return $this->transactionId;
    }

    public function handle(TraceabilityAnchorService $service): void
    {
        $transaction = BlockchainTransaction::query()->findOrFail($this->transactionId);
        if (! in_array($transaction->status, ['PENDING', 'SUBMITTED'], true) || $transaction->transaction_reference === null) {
            return;
        }

        $service->verify($transaction);
    }

    public function failed(Throwable $exception): void
    {
        $transaction = BlockchainTransaction::query()->find($this->transactionId);
        if ($transaction !== null) {
            app(TraceabilityAnchorService::class)->recordVerificationFailure($transaction, $exception);
        }
    }
}
