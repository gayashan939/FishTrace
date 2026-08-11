<?php

namespace Tests\Feature;

use App\Jobs\AnchorTraceabilityEvent;
use App\Jobs\GenerateReportExport;
use App\Jobs\PollBlockchainTransaction;
use App\Jobs\RequestSpoilagePrediction;
use App\Models\BlockchainTransaction;
use App\Models\BlockchainVerification;
use App\Models\FishBatch;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TraceabilityEvent;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\AI\FeatureAggregator;
use App\Services\AI\SpoilagePredictionService;
use App\Services\IoT\TelemetryImporter;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AIBlockchainContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_features_preserve_missing_telemetry_and_measure_elapsed_violation_minutes(): void
    {
        $this->seed();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();
        $trip = TransportTrip::query()->whereHas('batches', fn ($query) => $query->whereKey($batch->id))->firstOrFail();
        $device = IotDevice::query()->firstOrFail();
        SensorReading::query()->where('transport_trip_id', $trip->id)->delete();

        $missing = app(FeatureAggregator::class)->forBatch($batch);
        $this->assertFalse($missing['hasTemperatureTelemetry']);
        $this->assertSame(0, $missing['temperatureReadingCount']);
        $this->assertNull($missing['currentProductTemperature']);
        $this->assertNull($missing['airTemperature']);

        $start = now()->startOfMinute();
        foreach ([[0, 5.0], [10, 6.0], [25, 3.0]] as [$minutes, $temperature]) {
            SensorReading::query()->create([
                'message_id' => "ai-feature-{$minutes}",
                'iot_device_id' => $device->id,
                'transport_trip_id' => $trip->id,
                'product_temperature' => $temperature,
                'recorded_at' => $start->copy()->addMinutes($minutes),
                'imported_at' => now(),
            ]);
        }

        $features = app(FeatureAggregator::class)->forBatch($batch);
        $this->assertTrue($features['hasTemperatureTelemetry']);
        $this->assertSame(3, $features['temperatureReadingCount']);
        $this->assertSame(25, $features['timeAboveLimitMinutes']);
        $this->assertSame(2, $features['temperatureViolationCount']);
    }

    public function test_ai_api_is_whitelisted_and_marks_predictions_as_decision_support(): void
    {
        $this->seed();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $prediction = app(SpoilagePredictionService::class)->predict($batch, $user);
        Sanctum::actingAs($user);

        $latest = $this->getJson("/api/v1/batches/{$batch->id}/ai-predictions/latest")
            ->assertOk()
            ->assertJsonPath('data.id', $prediction->id)
            ->assertJsonPath('data.decision_support', true)
            ->assertJsonStructure(['data' => ['risk_level', 'confidence', 'probabilities', 'recommendation', 'model_version', 'provider', 'predicted_at', 'disclaimer']]);
        $this->assertIsFloat($latest->json('data.confidence'));
        foreach (['LOW', 'MEDIUM', 'HIGH'] as $riskLevel) {
            $this->assertIsFloat($latest->json("data.probabilities.{$riskLevel}"));
        }
        $this->assertSame(['LOW', 'MEDIUM', 'HIGH'], array_keys($latest->json('data.probabilities')));
        $this->assertNotNull(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $latest->json('data.predicted_at')));
        foreach (['features', 'requested_by', 'ai_prediction_inputs'] as $privateField) {
            $this->assertStringNotContainsString($privateField, $latest->getContent());
        }

        $this->getJson("/api/v1/batches/{$batch->id}/ai-predictions")
            ->assertOk()
            ->assertJsonPath('data.data.0.decision_support', true);
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $batch->id, 'event_type' => 'AI_PREDICTION_ANCHORED']);

        Queue::fake();
        $this->postJson("/api/v1/batches/{$batch->id}/ai-predictions/request")
            ->assertAccepted()
            ->assertJsonPath('data.status', 'QUEUED')
            ->assertJsonPath('data.decision_support', true);
        Queue::assertPushed(RequestSpoilagePrediction::class, fn (RequestSpoilagePrediction $job): bool => $job->uniqueId() === $batch->id);
    }

    public function test_public_verification_is_derived_from_persisted_anchor_evidence(): void
    {
        $this->seed();

        $verified = $this->getJson('/api/v1/public/trace/demo-trace-yellowfin-tuna-2026')
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'VERIFIED')
            ->assertJsonPath('data.blockchain.status', 'VERIFIED')
            ->assertJsonPath('data.blockchain.eligible_event_count', 3)
            ->assertJsonPath('data.blockchain.anchored_event_count', 3);
        foreach (['event_hash', 'transaction_reference', 'contract_address', 'response'] as $privateField) {
            $this->assertStringNotContainsString($privateField, $verified->getContent());
        }

        BlockchainVerification::query()->where('is_valid', true)->firstOrFail()->delete();
        $this->getJson('/api/v1/public/trace/demo-trace-yellowfin-tuna-2026')
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'TRACE_RECORDED')
            ->assertJsonPath('data.blockchain.status', 'PENDING');
    }

    public function test_approved_milestones_auto_anchor_and_polling_is_bounded(): void
    {
        $this->seed();
        Queue::fake();
        config(['fishtrace.blockchain.auto_anchor' => true]);
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();

        $event = TraceabilityEvent::query()->create([
            'fish_batch_id' => $batch->id,
            'organization_id' => $batch->organization_id,
            'event_type' => 'PROCESSING_COMPLETED',
            'title' => 'Processing complete',
            'public_data' => ['status' => 'COMPLETED'],
            'occurred_at' => now(),
        ]);
        TraceabilityEvent::query()->create([
            'fish_batch_id' => $batch->id,
            'organization_id' => $batch->organization_id,
            'event_type' => 'INTERNAL_NOTE',
            'title' => 'Not anchorable',
            'occurred_at' => now(),
        ]);
        Queue::assertPushed(AnchorTraceabilityEvent::class, 1);
        Queue::assertPushed(AnchorTraceabilityEvent::class, fn (AnchorTraceabilityEvent $job): bool => $job->eventId === $event->id);

        $transaction = BlockchainTransaction::query()->create([
            'event_hash' => hash('sha256', (string) Str::uuid()),
            'transaction_reference' => 'pending-'.Str::uuid(),
            'network' => 'mock',
            'status' => 'SUBMITTED',
            'attempts' => 1,
            'submitted_at' => now(),
        ]);
        $this->artisan('fishtrace:poll-blockchain-transactions', ['--limit' => 1])->assertSuccessful();
        Queue::assertPushed(PollBlockchainTransaction::class, 1);
        Queue::assertPushed(PollBlockchainTransaction::class, fn (PollBlockchainTransaction $job): bool => $job->transactionId === $transaction->id);
        $this->artisan('fishtrace:poll-blockchain-transactions', ['--limit' => 501])->assertFailed();
    }

    public function test_telemetry_import_queues_non_blocking_batch_prediction(): void
    {
        $this->seed();
        Queue::fake();
        config(['fishtrace.ai.auto_predict' => true]);
        $device = IotDevice::query()->firstOrFail();
        $trip = TransportTrip::query()->where('status', 'ACTIVE')->firstOrFail();

        app(TelemetryImporter::class)->import($device, 'ai-auto-predict-reading', [
            'tripId' => $trip->id,
            'productTemperature' => 4.2,
            'airTemperature' => 4.8,
            'humidity' => 82,
            'batteryPercentage' => 80,
            'recordedAt' => now()->getTimestampMs(),
            'schemaVersion' => 1,
        ]);

        Queue::assertPushed(RequestSpoilagePrediction::class, fn (RequestSpoilagePrediction $job): bool => $job->batchId === FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->value('id'));
    }

    public function test_all_jobs_have_bounded_execution_and_deduplication(): void
    {
        foreach ([
            AnchorTraceabilityEvent::class => 'event-id',
            GenerateReportExport::class => 'export-id',
            PollBlockchainTransaction::class => 'transaction-id',
            RequestSpoilagePrediction::class => 'batch-id',
        ] as $jobClass => $identifier) {
            $job = match ($jobClass) {
                AnchorTraceabilityEvent::class => new AnchorTraceabilityEvent($identifier),
                GenerateReportExport::class => new GenerateReportExport($identifier),
                PollBlockchainTransaction::class => new PollBlockchainTransaction($identifier),
                RequestSpoilagePrediction::class => new RequestSpoilagePrediction($identifier),
            };

            $this->assertInstanceOf(ShouldQueue::class, $job);
            $this->assertInstanceOf(ShouldBeUnique::class, $job);
            $this->assertGreaterThan(0, $job->tries);
            $this->assertGreaterThan(0, $job->timeout);
            $this->assertNotEmpty($job->backoff);
            $this->assertGreaterThanOrEqual($job->timeout, $job->uniqueFor);
            $this->assertSame($identifier, $job->uniqueId());
        }
    }
}
