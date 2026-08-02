<?php

namespace App\Console\Commands;

use App\Jobs\PollBlockchainTransaction;
use App\Models\BlockchainTransaction;
use Illuminate\Console\Command;

class PollBlockchainTransactions extends Command
{
    protected $signature = 'fishtrace:poll-blockchain-transactions {--limit=}';

    protected $description = 'Queue verification polls for submitted blockchain anchors';

    public function handle(): int
    {
        $configured = (int) config('fishtrace.blockchain.poll_batch_size', 100);
        $requested = $this->option('limit');
        $limit = $requested === null ? $configured : (int) $requested;
        if ($limit < 1 || $limit > 500) {
            $this->error('The limit must be between 1 and 500.');

            return self::INVALID;
        }

        $transactions = BlockchainTransaction::query()
            ->whereIn('status', ['PENDING', 'SUBMITTED'])
            ->whereNotNull('transaction_reference')
            ->oldest('submitted_at')
            ->limit($limit)
            ->get(['id']);

        foreach ($transactions as $transaction) {
            PollBlockchainTransaction::dispatch($transaction->id);
        }

        $this->info("Queued {$transactions->count()} blockchain verification poll(s).");

        return self::SUCCESS;
    }
}
