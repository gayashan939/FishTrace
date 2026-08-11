<?php

namespace App\Services\AI;

use RuntimeException;

class PredictionResultNormalizer
{
    private const RISK_LEVELS = ['LOW', 'MEDIUM', 'HIGH'];

    /** @return array{riskLevel:string,confidence:float,probabilities:array{LOW:float,MEDIUM:float,HIGH:float},recommendation:string,modelVersion:string,provider:string} */
    public function normalize(array $result): array
    {
        $riskLevel = $result['riskLevel'] ?? null;
        $confidence = is_numeric($result['confidence'] ?? null) ? (float) $result['confidence'] : -1.0;
        $probabilities = $result['probabilities'] ?? null;
        $recommendation = trim((string) ($result['recommendation'] ?? ''));
        $modelVersion = trim((string) ($result['modelVersion'] ?? ''));
        $provider = trim((string) ($result['provider'] ?? ''));

        if (! in_array($riskLevel, self::RISK_LEVELS, true)
            || $confidence < 0 || $confidence > 1
            || ! is_array($probabilities)
            || $recommendation === '' || mb_strlen($recommendation) > 2000
            || $modelVersion === '' || mb_strlen($modelVersion) > 255
            || $provider === '' || mb_strlen($provider) > 255) {
            throw new RuntimeException('The AI service returned an invalid prediction response.');
        }

        $normalizedProbabilities = [];
        foreach (self::RISK_LEVELS as $level) {
            $value = $probabilities[$level] ?? null;
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 1) {
                throw new RuntimeException('The AI service returned an invalid prediction response.');
            }
            $normalizedProbabilities[$level] = (float) $value;
        }
        if (abs(array_sum($normalizedProbabilities) - 1.0) > 0.001) {
            throw new RuntimeException('The AI service returned an invalid prediction response.');
        }

        return [
            'riskLevel' => $riskLevel,
            'confidence' => $confidence,
            'probabilities' => $normalizedProbabilities,
            'recommendation' => $recommendation,
            'modelVersion' => $modelVersion,
            'provider' => $provider,
        ];
    }
}
