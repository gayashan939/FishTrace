<?php

namespace App\Services\AI;

use App\Contracts\AI\AIPredictionClient;
use App\Enums\NotificationType;
use App\Models\AIPrediction;
use App\Models\AIServiceFailure;
use App\Models\FishBatch;
use App\Models\TraceabilityEvent;
use App\Models\User;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpoilagePredictionService
{
    public function __construct(private AIPredictionClient $client, private FeatureAggregator $features, private OperationalNotifier $notifier, private PredictionResultNormalizer $normalizer) {}

    public function predict(FishBatch $batch, ?User $requestedBy = null): AIPrediction
    {
        $features = $this->features->forBatch($batch);
        $result = $this->normalizer->normalize($this->client->predict($features));
        $tripId = DB::table('transport_batches')
            ->join('transport_trips', 'transport_trips.id', '=', 'transport_batches.transport_trip_id')
            ->where('transport_batches.fish_batch_id', $batch->id)
            ->orderByDesc('transport_trips.created_at')
            ->value('transport_batches.transport_trip_id');

        $prediction = DB::transaction(function () use ($batch, $requestedBy, $features, $result, $tripId): AIPrediction {
            $prediction = AIPrediction::create(['fish_batch_id' => $batch->id, 'transport_trip_id' => $tripId, 'requested_by' => $requestedBy?->id, 'risk_level' => $result['riskLevel'], 'confidence' => $result['confidence'], 'probabilities' => $result['probabilities'], 'recommendation' => $result['recommendation'], 'model_version' => $result['modelVersion'], 'provider' => $result['provider'], 'predicted_at' => now()]);
            DB::table('ai_prediction_inputs')->insert(['id' => (string) Str::uuid(), 'ai_prediction_id' => $prediction->id, 'features' => json_encode($features, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
            TraceabilityEvent::query()->create(['fish_batch_id' => $batch->id, 'organization_id' => $batch->organization_id, 'actor_id' => $requestedBy?->id, 'event_type' => 'AI_PREDICTION_ANCHORED', 'title' => 'AI spoilage-risk prediction recorded', 'public_data' => ['risk_level' => $prediction->risk_level, 'confidence' => $prediction->confidence, 'model_version' => $prediction->model_version, 'decision_support' => true], 'occurred_at' => $prediction->predicted_at]);

            return $prediction;
        });
        if ($prediction->risk_level === 'HIGH') {
            $this->notifier->organization($batch->organization_id, NotificationType::HIGH_AI_RISK, 'High spoilage risk predicted', $batch->batch_code.' has a high predicted spoilage risk.', ['batch_id' => $batch->id, 'prediction_id' => $prediction->id, 'confidence' => $prediction->confidence]);
        }
        AIServiceFailure::query()->where('fish_batch_id', $batch->id)->whereNull('resolved_at')->update(['resolved_at' => now()]);

        return $prediction;
    }
}
