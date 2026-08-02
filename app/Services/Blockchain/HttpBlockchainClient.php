<?php

namespace App\Services\Blockchain;

use App\Contracts\Blockchain\BlockchainClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpBlockchainClient implements BlockchainClient
{
    private function client()
    {
        return Http::acceptJson()->withToken((string) config('fishtrace.blockchain.token'))->timeout(10)->retry(2, 300);
    }

    public function submit(string $hash): array
    {
        $data = $this->client()->post(rtrim((string) config('fishtrace.blockchain.url'), '/').'/anchors', ['hash' => $hash, 'network' => config('fishtrace.blockchain.network'), 'contractAddress' => config('fishtrace.blockchain.contract')])->throw()->json();
        $status = is_array($data) ? (string) ($data['status'] ?? 'SUBMITTED') : '';
        if (! is_array($data) || ! is_string($data['transactionReference'] ?? null) || trim($data['transactionReference']) === '' || ! in_array($status, ['PENDING', 'SUBMITTED', 'CONFIRMED'], true)) {
            throw new RuntimeException('The blockchain service returned an invalid submission response.');
        }

        return ['transactionReference' => $data['transactionReference'], 'status' => $status, 'network' => (string) ($data['network'] ?? config('fishtrace.blockchain.network'))];
    }

    public function verify(string $transactionReference, string $hash): bool
    {
        $verified = $this->client()->get(rtrim((string) config('fishtrace.blockchain.url'), '/').'/anchors/'.rawurlencode($transactionReference), ['hash' => $hash])->throw()->json('verified');
        if (! is_bool($verified)) {
            throw new RuntimeException('The blockchain service returned an invalid verification response.');
        }

        return $verified;
    }
}
