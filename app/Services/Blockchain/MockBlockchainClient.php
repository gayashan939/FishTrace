<?php

namespace App\Services\Blockchain;

use App\Contracts\Blockchain\BlockchainClient;

class MockBlockchainClient implements BlockchainClient
{
    public function submit(string $hash): array
    {
        return ['transactionReference' => 'mock-tx-'.$hash, 'status' => 'CONFIRMED', 'network' => 'mock'];
    }

    public function verify(string $transactionReference, string $hash): bool
    {
        return hash_equals('mock-tx-'.$hash, $transactionReference);
    }
}
