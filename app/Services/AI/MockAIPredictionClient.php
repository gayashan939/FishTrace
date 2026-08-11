<?php

namespace App\Services\AI;

use App\Contracts\AI\AIPredictionClient;

class MockAIPredictionClient implements AIPredictionClient
{
    public function predict(array $features): array
    {
        $hasTemperatureTelemetry = ($features['hasTemperatureTelemetry'] ?? false) === true;
        $maximum = $hasTemperatureTelemetry ? (float) $features['maximumProductTemperature'] : null;
        $risk = ! $hasTemperatureTelemetry ? 'MEDIUM' : ($maximum >= 8 ? 'HIGH' : ($maximum > 4 ? 'MEDIUM' : 'LOW'));
        $probabilities = match ($risk) {
            'HIGH' => ['LOW' => .02, 'MEDIUM' => .08, 'HIGH' => .90], 'MEDIUM' => ['LOW' => .15, 'MEDIUM' => .75, 'HIGH' => .10], default => ['LOW' => .92, 'MEDIUM' => .07, 'HIGH' => .01]
        };

        $recommendation = ! $hasTemperatureTelemetry
            ? 'Collect product-temperature telemetry before making a release decision.'
            : ($risk === 'HIGH' ? 'Immediate quality inspection required.' : 'Continue standard cold-chain monitoring.');

        return ['riskLevel' => $risk, 'confidence' => max($probabilities), 'probabilities' => $probabilities, 'recommendation' => $recommendation, 'modelVersion' => 'mock-1.0.0', 'provider' => 'mock'];
    }
}
