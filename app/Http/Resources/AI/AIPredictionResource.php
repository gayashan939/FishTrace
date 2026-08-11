<?php

namespace App\Http\Resources\AI;

use App\Models\AIPrediction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use LogicException;

class AIPredictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $prediction = $this->resource;
        if (! $prediction instanceof AIPrediction) {
            throw new LogicException('AIPredictionResource requires an AIPrediction model.');
        }

        return [
            'id' => $prediction->id,
            'fish_batch_id' => $prediction->fish_batch_id,
            'transport_trip_id' => $prediction->transport_trip_id,
            'risk_level' => $prediction->risk_level,
            'confidence' => $prediction->confidence,
            'probabilities' => $prediction->probabilities,
            'recommendation' => $prediction->recommendation,
            'model_version' => $prediction->model_version,
            'provider' => $prediction->provider,
            'predicted_at' => Carbon::parse($prediction->predicted_at)->setTimezone(config('app.timezone'))->toIso8601String(),
            'decision_support' => true,
            'disclaimer' => 'Decision support only; apply the required quality and food-safety controls.',
        ];
    }
}
