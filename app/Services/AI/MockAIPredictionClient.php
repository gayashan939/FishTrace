<?php

namespace App\Services\AI;

use App\Contracts\AI\AIPredictionClient;

class MockAIPredictionClient implements AIPredictionClient
{
    public function predict(array $features): array
    {
        $maximum = (float) ($features['maximumProductTemperature'] ?? 0);
        $risk = $maximum >= 8 ? 'HIGH' : ($maximum > 4 ? 'MEDIUM' : 'LOW');
        $probabilities = match ($risk) {
            'HIGH' => ['LOW' => .02, 'MEDIUM' => .08, 'HIGH' => .90], 'MEDIUM' => ['LOW' => .15, 'MEDIUM' => .75, 'HIGH' => .10], default => ['LOW' => .92, 'MEDIUM' => .07, 'HIGH' => .01]
        };

        return ['riskLevel' => $risk, 'confidence' => max($probabilities), 'probabilities' => $probabilities, 'recommendation' => $risk === 'HIGH' ? 'Immediate quality inspection required.' : 'Continue standard cold-chain monitoring.', 'modelVersion' => 'mock-1.0.0', 'provider' => 'mock'];
    }
}
