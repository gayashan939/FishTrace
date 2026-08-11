<?php

namespace App\Services\Admin;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FishingOperationsView
{
    public function fisher(User $fisher): array
    {
        abort_unless($fisher->hasRole('FISHER'), 404);
        $fisher->load(['organizations:id,name,code,type,is_active', 'ownedBoats' => fn ($query) => $query->latest()->limit(100), 'ownedBoats.organization:id,name,code', 'fishingTrips' => fn ($query) => $query->latest()->limit(100), 'fishingTrips.organization:id,name,code', 'fishingTrips.boat:id,name,registration_number', 'fishingTrips.landingSite:id,name,district'])->loadCount(['ownedBoats', 'fishingTrips']);
        $catchStats = DB::table('catch_records')->join('fishing_trips', 'fishing_trips.id', '=', 'catch_records.fishing_trip_id')->where('fishing_trips.fisher_id', $fisher->id)->selectRaw('COUNT(*) AS catch_count, COALESCE(SUM(catch_records.weight_kg), 0) AS total_weight_kg, COALESCE(SUM(catch_records.allocated_weight_kg), 0) AS stored_allocated_weight_kg')->first();

        return ['fisher' => $fisher, 'catchStats' => $catchStats];
    }

    public function boat(Boat $boat): array
    {
        $boat->load(['organization:id,name,code', 'owner:id,name,email', 'trips' => fn ($query) => $query->withCount('catches')->withSum('catches', 'weight_kg')->latest()->limit(100), 'trips.fisher:id,name,email', 'trips.landingSite:id,name,district'])->loadCount('trips');
        $stats = DB::table('catch_records')->join('fishing_trips', 'fishing_trips.id', '=', 'catch_records.fishing_trip_id')->where('fishing_trips.boat_id', $boat->id)->selectRaw('COUNT(*) AS catch_count, COALESCE(SUM(catch_records.weight_kg), 0) AS total_weight_kg')->first();

        return ['boat' => $boat, 'stats' => $stats];
    }

    public function trip(FishingTrip $trip): array
    {
        $trip->load(['organization:id,name,code', 'fisher:id,name,email', 'boat:id,name,registration_number,type,capacity_kg,is_active', 'landingSite:id,name,district', 'catches.species:id,common_name,scientific_name', 'catches.gearType:id,name', 'catches.batches:id,batch_code,status,total_weight_kg']);
        $reconciliation = new Collection;
        $totalWeight = 0.0;
        foreach ($trip->catches as $catch) {
            if (! $catch instanceof CatchRecord) {
                continue;
            }
            $item = $this->reconcile($catch);
            $reconciliation->put($catch->id, $item);
            $totalWeight += (float) $catch->weight_kg;
        }

        return ['trip' => $trip, 'reconciliation' => $reconciliation, 'totals' => ['weight' => $totalWeight, 'ledger_allocated' => $reconciliation->sum('ledger_allocated'), 'unallocated' => $reconciliation->sum('unallocated'), 'overallocated' => $reconciliation->sum('overallocated')]];
    }

    public function catch(CatchRecord $catch): array
    {
        $catch->load(['organization:id,name,code', 'trip.organization:id,name,code', 'trip.fisher:id,name,email', 'trip.boat:id,name,registration_number', 'trip.landingSite:id,name,district', 'species:id,common_name,scientific_name', 'gearType:id,name', 'images', 'batches:id,batch_code,status,total_weight_kg,product_type']);

        return ['catch' => $catch, 'reconciliation' => $this->reconcile($catch)];
    }

    private function reconcile(CatchRecord $catch): array
    {
        $ledger = 0.0;
        foreach ($catch->batches as $batch) {
            if (! $batch instanceof FishBatch) {
                continue;
            }
            $ledger += (float) $batch->getRelation('pivot')->getAttribute('allocated_weight_kg');
        }
        $weight = (float) $catch->weight_kg;
        $stored = (float) $catch->allocated_weight_kg;

        return ['weight' => $weight, 'stored_allocated' => $stored, 'ledger_allocated' => $ledger, 'unallocated' => max($weight - $ledger, 0), 'overallocated' => max($ledger - $weight, 0), 'mismatch' => abs($stored - $ledger) > 0.0005, 'status' => $ledger <= 0 ? 'UNALLOCATED' : ($ledger > $weight + 0.0005 ? 'OVERALLOCATED' : ($ledger < $weight - 0.0005 ? 'PARTIAL' : 'FULL'))];
    }
}
