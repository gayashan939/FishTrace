<?php

namespace App\Services\AI;

use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FeatureAggregator
{
    /** @return array<string, bool|float|int|string|null> */
    public function forBatch(FishBatch $batch): array
    {
        $species = FishSpecies::findOrFail($batch->fish_species_id);
        $tripIds = DB::table('transport_batches')->where('fish_batch_id', $batch->id)->pluck('transport_trip_id');
        $readings = SensorReading::query()->whereIn('transport_trip_id', $tripIds)->orderBy('recorded_at')->get();
        $temperatures = $readings->pluck('product_temperature')->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value);
        $firstCatch = CatchRecord::query()->whereHas('batches', fn ($query) => $query->whereKey($batch->id))->oldest('caught_at')->first();
        $firstReading = $readings->first();
        $lastReading = $readings->last();

        $lastAirTemperature = $lastReading?->air_temperature === null ? null : (float) $lastReading->air_temperature;
        $lastHumidity = $lastReading?->humidity === null ? null : (float) $lastReading->humidity;
        $catchTime = $firstCatch ? Carbon::parse($firstCatch->getAttribute('caught_at')) : null;
        $transportHours = ($firstReading && $lastReading) ? Carbon::parse($firstReading->getAttribute('recorded_at'))->diffInHours(Carbon::parse($lastReading->getAttribute('recorded_at'))) : 0;

        return ['fishSpecies' => $species->common_name, 'hasTemperatureTelemetry' => $temperatures->isNotEmpty(), 'temperatureReadingCount' => $temperatures->count(), 'currentProductTemperature' => $temperatures->last(), 'averageProductTemperature' => $temperatures->avg(), 'minimumProductTemperature' => $temperatures->min(), 'maximumProductTemperature' => $temperatures->max(), 'airTemperature' => $lastAirTemperature, 'humidity' => $lastHumidity, 'storageDurationHours' => $catchTime?->diffInHours(now()) ?? 0, 'transportDurationHours' => $transportHours, 'timeAboveLimitMinutes' => $this->timeAboveLimitMinutes($readings), 'temperatureViolationCount' => $readings->where('product_temperature', '>', 4)->count(), 'timeSinceCatchHours' => $catchTime?->diffInHours(now()) ?? 0];
    }

    /** @param Collection<int, SensorReading> $readings */
    private function timeAboveLimitMinutes(Collection $readings): int
    {
        $ordered = $readings->values();
        $minutes = 0.0;
        for ($index = 0; $index < $ordered->count() - 1; $index++) {
            $current = $ordered[$index];
            if ($current->product_temperature === null || (float) $current->product_temperature <= 4) {
                continue;
            }
            $minutes += Carbon::parse($current->recorded_at)->diffInMinutes(Carbon::parse($ordered[$index + 1]->recorded_at));
        }

        return (int) round($minutes);
    }
}
