<?php

namespace Tests\Feature;

use App\Models\FishBatch;
use App\Models\TraceabilityEvent;
use App\Services\AI\SpoilagePredictionService;
use App\Services\Blockchain\CanonicalJsonSerializer;
use App\Services\Blockchain\TraceabilityAnchorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_ai_prediction_persists_features_and_result(): void
    {
        $this->seed();
        $prediction = app(SpoilagePredictionService::class)->predict(FishBatch::firstOrFail());
        $this->assertContains($prediction->risk_level, ['LOW', 'MEDIUM', 'HIGH']);
        $this->assertDatabaseHas('ai_prediction_inputs', ['ai_prediction_id' => $prediction->id]);
    }

    public function test_canonical_json_and_mock_anchor_are_deterministic_and_idempotent(): void
    {
        $serializer = app(CanonicalJsonSerializer::class);
        $this->assertSame($serializer->serialize(['b' => 2, 'a' => 1]), $serializer->serialize(['a' => 1, 'b' => 2]));
        $this->assertSame($serializer->serialize(['weight' => 1]), $serializer->serialize(['weight' => 1.0]));
        $this->seed();
        $event = TraceabilityEvent::firstOrFail();
        $service = app(TraceabilityAnchorService::class);
        $first = $service->anchor($event);
        $second = $service->anchor($event);
        $this->assertSame($first->id, $second->id);
        $this->assertTrue($service->verify($first));
    }
}
