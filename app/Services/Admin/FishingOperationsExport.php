<?php

namespace App\Services\Admin;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingGearType;
use App\Models\FishingTrip;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FishingOperationsExport
{
    public function __construct(private FishingOperationsQuery $query, private AuditLogger $audit) {}

    public function trips(User $actor, array $filters): StreamedResponse
    {
        $records = $this->query->trips($filters)->limit(10000)->get();
        $this->audit->record('FISHING_TRIPS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $records->count()], $actor, $actor->primaryOrganization()?->id);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fputcsv($stream, ['trip_code', 'organization', 'fisher', 'boat', 'registration_number', 'status', 'landing_site', 'catch_area', 'catch_count', 'catch_weight_kg', 'allocated_weight_kg', 'departed_at', 'returned_at'], escape: '\\');
            foreach ($records as $trip) {
                if (! $trip instanceof FishingTrip) {
                    continue;
                }
                $organization = $trip->organization;
                $fisher = $trip->fisher;
                $boat = $trip->boat;
                $landingSite = $trip->landingSite;
                fputcsv($stream, [$trip->trip_code, $organization instanceof Organization ? $organization->name : null, $fisher instanceof User ? $fisher->name : null, $boat instanceof Boat ? $boat->name : null, $boat instanceof Boat ? $boat->registration_number : null, $trip->getRawOriginal('status'), $landingSite instanceof LandingSite ? $landingSite->name : null, $trip->general_catch_area, $trip->getAttribute('catches_count'), $trip->getAttribute('catches_sum_weight_kg'), $trip->getAttribute('catches_sum_allocated_weight_kg'), $trip->departed_at ? Carbon::parse($trip->departed_at)->toIso8601String() : null, $trip->returned_at ? Carbon::parse($trip->returned_at)->toIso8601String() : null], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-fishing-trips-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function catches(User $actor, array $filters): StreamedResponse
    {
        $records = $this->query->catches($filters)->limit(10000)->get();
        $this->audit->record('CATCH_RECORDS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $records->count()], $actor, $actor->primaryOrganization()?->id);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fputcsv($stream, ['catch_id', 'trip_code', 'organization', 'species', 'gear', 'quantity', 'weight_kg', 'stored_allocated_weight_kg', 'ledger_allocated_weight_kg', 'unallocated_weight_kg', 'caught_at'], escape: '\\');
            foreach ($records as $catch) {
                if (! $catch instanceof CatchRecord) {
                    continue;
                }
                $organization = $catch->organization;
                $species = $catch->species;
                $trip = $catch->trip;
                $gear = $catch->gearType;
                $ledger = (float) $catch->getAttribute('ledger_allocated_weight_kg');
                fputcsv($stream, [$catch->id, $trip instanceof FishingTrip ? $trip->trip_code : null, $organization instanceof Organization ? $organization->name : null, $species instanceof FishSpecies ? $species->common_name : null, $gear instanceof FishingGearType ? $gear->name : null, $catch->quantity, $catch->weight_kg, $catch->allocated_weight_kg, $ledger, max((float) $catch->weight_kg - $ledger, 0), Carbon::parse($catch->caught_at)->toIso8601String()], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-catches-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
