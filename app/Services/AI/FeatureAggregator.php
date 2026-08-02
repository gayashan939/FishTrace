<?php

namespace App\Services\AI;

use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\SensorReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FeatureAggregator
{
    public function forBatch(FishBatch $batch): array
    {
        $species = FishSpecies::findOrFail($batch->fish_species_id);
        $tripIds = DB::table('transport_batches')->where('fish_batch_id', $batch->id)->pluck('transport_trip_id');
        $readings = SensorReading::query()->whereIn('transport_trip_id', $tripIds)->orderBy('recorded_at')->get();
        $temperatures = $readings->pluck('product_temperature')->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value);
        $firstCatch = CatchRecord::query()->whereHas('batches', fn ($query) => $query->whereKey($batch->id))->oldest('caught_at')->first();
        $firstReading = $readings->first();
        $lastReading = $readings->last();

        $lastAirTemperature = $lastReading ? (float) $lastReading->air_temperature : 0;
        $lastHumidity = $lastReading ? (float) $lastReading->humidity : 0;
        $catchTime = $firstCatch ? Carbon::parse($firstCatch->getAttribute('caught_at')) : null;
        $transportHours = ($firstReading && $lastReading) ? Carbon::parse($firstReading->getAttribute('recorded_at'))->diffInHours(Carbon::parse($lastReading->getAttribute('recorded_at'))) : 0;

        return ['fishSpecies' => $species->common_name, 'currentProductTemperature' => $temperatures->last() ?? 0, 'averageProductTemperature' => $temperatures->avg() ?? 0, 'minimumProductTemperature' => $temperatures->min() ?? 0, 'maximumProductTemperature' => $temperatures->max() ?? 0, 'airTemperature' => $lastAirTemperature, 'humidity' => $lastHumidity, 'storageDurationHours' => $catchTime?->diffInHours(now()) ?? 0, 'transportDurationHours' => $transportHours, 'timeAboveLimitMinutes' => $readings->where('product_temperature', '>', 4)->count(), 'temperatureViolationCount' => $readings->where('product_temperature', '>', 4)->count(), 'timeSinceCatchHours' => $catchTime?->diffInHours(now()) ?? 0];
    }
}
