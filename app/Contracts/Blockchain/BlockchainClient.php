<?php

namespace App\Contracts\Blockchain;

interface BlockchainClient
{
    /** @return array{transactionReference:string,status:string,network:string} */
    public function submit(string $hash): array;

    public function verify(string $transactionReference, string $hash): bool;
}
