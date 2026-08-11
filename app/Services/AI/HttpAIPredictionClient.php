<?php

namespace App\Services\AI;

use App\Contracts\AI\AIPredictionClient;
use Illuminate\Support\Facades\Http;

class HttpAIPredictionClient implements AIPredictionClient
{
    public function __construct(private readonly PredictionResultNormalizer $normalizer = new PredictionResultNormalizer) {}

    public function predict(array $features): array
    {
        $response = Http::acceptJson()->withToken((string) config('fishtrace.ai.token'))->timeout((int) config('fishtrace.ai.timeout'))->retry(2, 250)->post(rtrim((string) config('fishtrace.ai.url'), '/').'/predict', $features)->throw()->json();

        return $this->normalizer->normalize(is_array($response) ? [...$response, 'provider' => 'http'] : []);
    }
}
