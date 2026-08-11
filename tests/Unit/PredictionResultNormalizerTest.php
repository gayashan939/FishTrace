<?php

namespace Tests\Unit;

use App\Services\AI\PredictionResultNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PredictionResultNormalizerTest extends TestCase
{
    public function test_numeric_provider_values_are_normalized_to_canonical_floats(): void
    {
        $result = (new PredictionResultNormalizer)->normalize([
            'riskLevel' => 'HIGH',
            'confidence' => '0.90',
            'probabilities' => ['LOW' => '0.02', 'MEDIUM' => '0.08', 'HIGH' => '0.90', 'PRIVATE' => 1],
            'recommendation' => ' Inspect immediately. ',
            'modelVersion' => ' model-v1 ',
            'provider' => ' http ',
        ]);

        $this->assertSame(0.9, $result['confidence']);
        $this->assertSame(['LOW' => 0.02, 'MEDIUM' => 0.08, 'HIGH' => 0.9], $result['probabilities']);
        $this->assertSame('Inspect immediately.', $result['recommendation']);
        $this->assertSame('model-v1', $result['modelVersion']);
        $this->assertSame('http', $result['provider']);
    }

    #[DataProvider('invalidResults')]
    public function test_incomplete_or_incoherent_provider_results_are_rejected(array $result): void
    {
        $this->expectException(RuntimeException::class);
        (new PredictionResultNormalizer)->normalize($result);
    }

    public static function invalidResults(): array
    {
        $valid = ['riskLevel' => 'LOW', 'confidence' => .9, 'probabilities' => ['LOW' => .9, 'MEDIUM' => .08, 'HIGH' => .02], 'recommendation' => 'Monitor.', 'modelVersion' => 'v1', 'provider' => 'mock'];

        return [
            'probabilities do not sum to one' => [[...$valid, 'probabilities' => ['LOW' => .8, 'MEDIUM' => .08, 'HIGH' => .02]]],
            'recommendation is required' => [[...$valid, 'recommendation' => '']],
            'model version is required' => [[...$valid, 'modelVersion' => '']],
            'provider is required' => [[...$valid, 'provider' => '']],
        ];
    }
}
