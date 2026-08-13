<?php

namespace Database\Seeders;

use App\Actions\Retail\ReceiveRetailPackage;
use App\Models\BatchIntake;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\ChildBatch;
use App\Models\DeviceAssignment;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\FishSpecies;
use App\Models\IotDevice;
use App\Models\Organization;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\QualityInspection;
use App\Models\RetailLocation;
use App\Models\Role;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Blockchain\TraceabilityAnchorService;
use App\Services\Transport\TransportChecklistService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'FishTrace@2026';

    public function run(): void
    {
        $roles = collect(['ADMIN', 'FISHER', 'PROCESSOR', 'TRANSPORTER', 'RETAILER', 'INSPECTOR'])->mapWithKeys(fn ($name) => [$name => Role::create(['name' => $name])]);
        $organizations = collect([
            'ADMIN' => ['FishTrace National Operations', 'FT-ADMIN', 'REGULATOR'],
            'FISHER' => ['Southern Fisheries Cooperative', 'SFC-001', 'FISHER'],
            'PROCESSOR' => ['Ceylon Blue Processing', 'CBP-001', 'PROCESSOR'],
            'TRANSPORTER' => ['Lanka Cold Logistics', 'LCL-001', 'TRANSPORTER'],
            'RETAILER' => ['Ocean Fresh Markets', 'OFM-001', 'RETAILER'],
            'INSPECTOR' => ['National Fisheries Inspectorate', 'NFI-001', 'INSPECTOR'],
        ])->mapWithKeys(fn ($data, $role) => [$role => Organization::create(['name' => $data[0], 'code' => $data[1], 'type' => $data[2]])]);
        $names = ['ADMIN' => 'System Administrator', 'FISHER' => 'Nimal Fernando', 'PROCESSOR' => 'Saman Perera', 'TRANSPORTER' => 'Kasun Silva', 'RETAILER' => 'Amali Jayasinghe', 'INSPECTOR' => 'Dinuka Wijesinghe'];
        $users = collect($names)->mapWithKeys(function ($name, $role) use ($roles, $organizations) {
            $user = User::create(['name' => $name, 'email' => strtolower($role).'@fishtrace.demo', 'password' => Hash::make(self::DEMO_PASSWORD), 'status' => 'ACTIVE']);
            $user->update(['firebase_uid' => 'user:'.$user->id]);
            $user->roles()->attach($roles[$role]);
            $user->organizations()->attach($organizations[$role], ['is_primary' => true]);

            return [$role => $user];
        });
        $retailLocation = RetailLocation::create(['organization_id' => $organizations['RETAILER']->id, 'code' => 'CMB-01', 'name' => 'Ocean Fresh Colombo', 'address' => 'Colombo 03']);
        $processingTypeId = (string) Str::uuid();
        DB::table('processing_types')->insert(['id' => $processingTypeId, 'name' => 'Chilled Whole Fish Processing', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $qualityGradeId = (string) Str::uuid();
        DB::table('quality_grades')->insert(['id' => $qualityGradeId, 'code' => 'A', 'name' => 'Export Grade A', 'rank' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('processor_profiles')->insert(['id' => (string) Str::uuid(), 'user_id' => $users['PROCESSOR']->id, 'organization_id' => $organizations['PROCESSOR']->id, 'facility_name' => 'Ceylon Blue Processing - Mirissa', 'license_number' => 'SL-FP-2026-001', 'created_at' => now(), 'updated_at' => now()]);
        $species = FishSpecies::create(['common_name' => 'Yellowfin Tuna', 'scientific_name' => 'Thunnus albacares']);
        $gearId = (string) Str::uuid();
        DB::table('fishing_gear_types')->insert(['id' => $gearId, 'name' => 'Longline', 'created_at' => now(), 'updated_at' => now()]);
        $siteId = (string) Str::uuid();
        DB::table('landing_sites')->insert(['id' => $siteId, 'name' => 'Mirissa Fisheries Harbour', 'district' => 'Matara', 'created_at' => now(), 'updated_at' => now()]);
        $boat = Boat::create(['organization_id' => $organizations['FISHER']->id, 'owner_id' => $users['FISHER']->id, 'registration_number' => 'IMUL-A-1042-MTR', 'name' => 'Sagara Kumari', 'type' => 'Multi-day', 'capacity_kg' => 2500]);
        $fishingTrip = FishingTrip::create(['organization_id' => $organizations['FISHER']->id, 'fisher_id' => $users['FISHER']->id, 'boat_id' => $boat->id, 'landing_site_id' => $siteId, 'trip_code' => 'FTR-DEMO-001', 'status' => 'COMPLETED', 'general_catch_area' => 'FAO Area 51, Southern Sri Lanka', 'departed_at' => now()->subDays(2), 'returned_at' => now()->subDay()]);
        $catch = CatchRecord::create(['organization_id' => $organizations['FISHER']->id, 'fishing_trip_id' => $fishingTrip->id, 'fish_species_id' => $species->id, 'fishing_gear_type_id' => $gearId, 'client_record_id' => (string) Str::uuid(), 'weight_kg' => 120, 'quantity' => 6, 'allocated_weight_kg' => 100, 'caught_at' => now()->subDays(2)]);
        $batch = FishBatch::create(['organization_id' => $organizations['FISHER']->id, 'created_by' => $users['FISHER']->id, 'fish_species_id' => $species->id, 'batch_code' => 'FT-DEMO-0001', 'type' => 'RAW', 'status' => 'IN_TRANSPORT', 'product_type' => 'Chilled whole tuna', 'total_weight_kg' => 100, 'created_from_catch_at' => now()->subDay()]);
        $batch->catches()->attach($catch->id, ['allocated_weight_kg' => 100]);
        $batch->qrCode()->create(['public_token' => 'demo-trace-yellowfin-tuna-2026']);
        $demoEvents = collect();
        foreach ([['CATCH_REGISTERED', 'Catch registered', now()->subDays(2)], ['BATCH_CREATED', 'Batch created and QR issued', now()->subDay()], ['TRANSPORT_STARTED', 'Cold-chain transport started', now()->subHours(3)]] as $event) {
            $demoEvents->push($batch->events()->create(['organization_id' => $organizations['FISHER']->id, 'actor_id' => $users['FISHER']->id, 'event_type' => $event[0], 'title' => $event[1], 'public_data' => ['verified' => true], 'occurred_at' => $event[2]]));
        }
        if (config('fishtrace.blockchain.driver') === 'mock') {
            $anchors = app(TraceabilityAnchorService::class);
            foreach ($demoEvents as $event) {
                $anchors->verify($anchors->anchor($event));
            }
        }
        $intake = BatchIntake::create(['fish_batch_id' => $batch->id, 'processor_organization_id' => $organizations['PROCESSOR']->id, 'received_by' => $users['PROCESSOR']->id, 'status' => 'ACCEPTED', 'received_weight_kg' => 100, 'received_at' => now()->subHours(20)]);
        $processing = ProcessingRecord::create(['fish_batch_id' => $batch->id, 'batch_intake_id' => $intake->id, 'organization_id' => $organizations['PROCESSOR']->id, 'created_by' => $users['PROCESSOR']->id, 'processing_type_id' => $processingTypeId, 'status' => 'COMPLETED', 'input_weight_kg' => 100, 'output_weight_kg' => 92, 'waste_weight_kg' => 8, 'started_at' => now()->subHours(19), 'completed_at' => now()->subHours(12)]);
        foreach ([['CLEANING', 1, ['cleaned_weight_kg' => 98]], ['GRADING', 2, ['grade' => 'A']], ['FREEZING', 3, ['product_temperature' => -2]], ['PACKAGING', 4, ['output_weight_kg' => 92, 'waste_weight_kg' => 8, 'package_count' => 10]]] as $step) {
            $processing->steps()->create(['type' => $step[0], 'sequence' => $step[1], 'status' => 'COMPLETED', 'measurements' => $step[2], 'performed_by' => $users['PROCESSOR']->id, 'started_at' => now()->subHours(18 - $step[1]), 'completed_at' => now()->subHours(17 - $step[1])]);
        }
        QualityInspection::create(['fish_batch_id' => $batch->id, 'processing_record_id' => $processing->id, 'organization_id' => $organizations['PROCESSOR']->id, 'inspector_id' => $users['INSPECTOR']->id, 'quality_grade_id' => $qualityGradeId, 'result' => 'PASSED', 'product_temperature' => -1.5, 'ph_level' => 5.8, 'appearance' => 'Bright and firm', 'odor' => 'Fresh', 'inspected_at' => now()->subHours(11)]);
        $retailLabels = collect([[40, 4, 'FT-DEMO-0001-01', 'LBL-DEMO-RETAIL-01', 'demo-retail-package-01'], [52, 4, 'FT-DEMO-0001-02', 'LBL-DEMO-RETAIL-02', 'demo-retail-package-02']])->map(function (array $data) use ($batch, $organizations, $users): PackageLabel {
            $child = FishBatch::create(['organization_id' => $organizations['PROCESSOR']->id, 'created_by' => $users['PROCESSOR']->id, 'fish_species_id' => $batch->fish_species_id, 'batch_code' => $data[2], 'type' => 'CHILD', 'status' => 'PROCESSED', 'product_type' => 'Frozen tuna loin', 'total_weight_kg' => $data[0], 'created_from_catch_at' => $batch->created_from_catch_at]);
            ChildBatch::create(['parent_batch_id' => $batch->id, 'child_batch_id' => $child->id, 'allocated_weight_kg' => $data[0], 'created_by' => $users['PROCESSOR']->id]);
            DB::table('batch_relationships')->insert(['id' => (string) Str::uuid(), 'parent_batch_id' => $batch->id, 'child_batch_id' => $child->id, 'relationship_type' => 'SPLIT', 'created_at' => now(), 'updated_at' => now()]);
            $child->qrCode()->create(['public_token' => $data[4]]);

            return PackageLabel::create(['fish_batch_id' => $child->id, 'label_code' => $data[3], 'public_token' => $data[4], 'package_weight_kg' => $data[0] / $data[1], 'package_count' => $data[1]]);
        });
        app(ReceiveRetailPackage::class)->execute($users['RETAILER'], $retailLabels->first(), ['retail_location_id' => $retailLocation->id, 'received_package_count' => 4, 'condition_temperature' => -1.2, 'expires_at' => now()->addDays(21)->toIso8601String(), 'notes' => 'Demo retailer intake verified.']);
        $vehicle = Vehicle::create(['organization_id' => $organizations['TRANSPORTER']->id, 'registration_number' => 'WP-CAB-2048', 'name' => 'Reefer Truck 01', 'vehicle_type' => 'Refrigerated Truck', 'refrigeration_category' => 'Category A', 'capacity_tonnes' => 1.2, 'reefer_unit' => 'Carrier Supra 550', 'min_temperature_celsius' => -20, 'max_temperature_celsius' => 20, 'default_driver_name' => 'Alex Johnson']);
        $device = IotDevice::create(['organization_id' => $organizations['TRANSPORTER']->id, 'device_code' => 'IOT-001', 'serial_number' => 'ESP32-FT-0001', 'display_name' => 'Reefer Sensor 01', 'status' => 'ACTIVE', 'firmware_version' => '1.2.0', 'firebase_uid' => 'device:demo-iot-001', 'firebase_email' => 'device+iot-001@fishtrace.invalid', 'firebase_auth_enabled' => true, 'credential_version' => 1]);
        $transport = TransportTrip::create(['organization_id' => $organizations['TRANSPORTER']->id, 'created_by' => $users['TRANSPORTER']->id, 'vehicle_id' => $vehicle->id, 'trip_code' => 'TTR-DEMO-001', 'driver_name' => 'Sunil Kumara', 'status' => 'ACTIVE', 'origin' => 'Mirissa', 'origin_latitude' => 5.9483, 'origin_longitude' => 80.4716, 'destination' => 'Colombo', 'destination_latitude' => 6.9271, 'destination_longitude' => 79.8612, 'estimated_distance_km' => 150, 'started_at' => now()->subHours(3)]);
        $transportChecklist = app(TransportChecklistService::class)->initialize($transport);
        $transportChecklist->items()->update(['is_completed' => true, 'completed_by' => $users['TRANSPORTER']->id, 'completed_at' => $transport->started_at]);
        $transportChecklist->update(['completed_by' => $users['TRANSPORTER']->id, 'completed_at' => $transport->started_at]);
        $transport->batches()->attach($batch->id);
        DeviceAssignment::create(['iot_device_id' => $device->id, 'transport_trip_id' => $transport->id, 'status' => 'ACTIVE', 'firebase_sync_status' => 'SYNCED', 'assigned_at' => now()->subHours(3)]);
        for ($i = 0; $i < 12; $i++) {
            SensorReading::create(['message_id' => 'demo-reading-'.$i, 'iot_device_id' => $device->id, 'transport_trip_id' => $transport->id, 'product_temperature' => 3.1 + ($i * .04), 'air_temperature' => 3.8 + ($i * .03), 'humidity' => 81 + ($i % 3), 'latitude' => 5.95 + ($i * .08), 'longitude' => 80.45 - ($i * .05), 'speed_kph' => 42, 'battery_percentage' => 94 - $i, 'signal_strength' => -70, 'door_open' => false, 'recorded_at' => now()->subMinutes(60 - ($i * 5)), 'imported_at' => now(), 'raw_payload' => ['schemaVersion' => 1]]);
        }
    }
}
