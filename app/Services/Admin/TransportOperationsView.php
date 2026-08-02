<?php

namespace App\Services\Admin;

use App\Models\ColdChainAlert;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class TransportOperationsView
{
    public function transporter(User $transporter): array
    {
        abort_unless($transporter->hasRole('TRANSPORTER'), 404);
        $transporter->load(['organizations:id,name,code,type,is_active', 'transportTrips' => fn ($q) => $q->withCount(['batches', 'readings', 'alerts'])->with('vehicle:id,name,registration_number')->latest()->limit(100)])->loadCount('transportTrips');

        return ['transporter' => $transporter];
    }

    public function vehicle(Vehicle $vehicle): array
    {
        $vehicle->load(['organization:id,name,code', 'trips' => fn ($q) => $q->withCount(['batches', 'readings', 'alerts'])->latest()->limit(100), 'trips.creator:id,name'])->loadCount('trips');

        return ['vehicle' => $vehicle];
    }

    public function trip(TransportTrip $trip): array
    {
        $trip->load(['organization:id,name,code', 'creator:id,name,email', 'vehicle:id,name,registration_number,is_active', 'batches:id,batch_code,status,total_weight_kg,product_type', 'assignments' => fn ($q) => $q->with('device:id,device_code,display_name,status,last_seen_at,battery_percentage,signal_strength')->latest()->limit(100), 'readings' => fn ($q) => $q->select(['id', 'iot_device_id', 'transport_trip_id', 'product_temperature', 'air_temperature', 'humidity', 'speed_kph', 'battery_percentage', 'signal_strength', 'door_open', 'recorded_at', 'imported_at'])->latest('recorded_at')->limit(250), 'alerts' => fn ($q) => $q->latest('last_detected_at')->limit(100)]);
        $stats = DB::table('sensor_readings')->where('transport_trip_id', $trip->id)->selectRaw('COUNT(*) AS reading_count, MIN(product_temperature) AS minimum_temperature, MAX(product_temperature) AS maximum_temperature, AVG(product_temperature) AS average_temperature, MIN(battery_percentage) AS minimum_battery, SUM(CASE WHEN door_open = 1 THEN 1 ELSE 0 END) AS door_open_count, MAX(recorded_at) AS last_recorded_at')->first();

        return ['trip' => $trip, 'stats' => $stats];
    }

    public function device(IotDevice $device): array
    {
        $device->load(['organization:id,name,code', 'syncCursor', 'assignments' => fn ($q) => $q->with('trip:id,trip_code,status,origin,destination')->latest()->limit(100), 'readings' => fn ($q) => $q->select(['id', 'iot_device_id', 'transport_trip_id', 'product_temperature', 'air_temperature', 'humidity', 'speed_kph', 'battery_percentage', 'signal_strength', 'door_open', 'recorded_at', 'imported_at'])->with('trip:id,trip_code')->latest('recorded_at')->limit(250), 'syncFailures' => fn ($q) => $q->latest('last_failed_at')->limit(100)])->loadCount('readings');

        return ['device' => $device];
    }

    public function alert(ColdChainAlert $alert): array
    {
        $alert->load(['trip.organization:id,name,code', 'trip.vehicle:id,name,registration_number', 'trip.assignments.device:id,device_code,display_name', 'batch:id,batch_code,status']);

        return ['alert' => $alert];
    }
}
