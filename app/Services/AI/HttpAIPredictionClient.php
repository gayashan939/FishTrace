<?php

namespace App\Services\AI;

use App\Contracts\AI\AIPredictionClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpAIPredictionClient implements AIPredictionClient
{
    public function predict(array $features): array
    {
        $response = Http::acceptJson()->withToken((string) config('fishtrace.ai.token'))->timeout((int) config('fishtrace.ai.timeout'))->retry(2, 250)->post(rtrim((string) config('fishtrace.ai.url'), '/').'/predict', $features)->throw()->json();
        $probabilities = is_array($response) ? ($response['probabilities'] ?? null) : null;
        $validProbabilities = is_array($probabilities)
            && collect(['LOW', 'MEDIUM', 'HIGH'])->every(fn (string $risk): bool => is_numeric($probabilities[$risk] ?? null) && (float) $probabilities[$risk] >= 0 && (float) $probabilities[$risk] <= 1);
        $confidence = is_array($response) && is_numeric($response['confidence'] ?? null) ? (float) $response['confidence'] : -1;
        if (! is_array($response) || ! in_array($response['riskLevel'] ?? null, ['LOW', 'MEDIUM', 'HIGH'], true) || $confidence < 0 || $confidence > 1 || ! $validProbabilities) {
            throw new RuntimeException('The AI service returned an invalid prediction response.');
        }

        return ['riskLevel' => $response['riskLevel'], 'confidence' => $confidence, 'probabilities' => $probabilities, 'recommendation' => (string) ($response['recommendation'] ?? ''), 'modelVersion' => (string) ($response['modelVersion'] ?? 'unknown'), 'provider' => 'http'];
    }
}
