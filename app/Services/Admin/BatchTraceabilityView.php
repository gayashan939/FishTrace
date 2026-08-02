<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\FishBatch;
use Illuminate\Support\Facades\DB;

class BatchTraceabilityView
{
    public function build(FishBatch $batch): array
    {
        $batch->load([
            'organization:id,name,code,type', 'creator:id,name,email', 'species:id,common_name,scientific_name', 'qrCode:id,fish_batch_id,revoked_at',
            'catches:id,fishing_trip_id,weight_kg,quantity,caught_at', 'catches.trip:id,boat_id,trip_code,general_catch_area,departed_at,returned_at', 'catches.trip.boat:id,name,registration_number',
            'events' => fn ($query) => $query->select(['id', 'fish_batch_id', 'organization_id', 'actor_id', 'event_type', 'title', 'public_data', 'occurred_at'])->limit(250),
            'intakes:id,fish_batch_id,processor_organization_id,status,received_weight_kg,rejection_reason,received_at',
            'processingRecord:id,fish_batch_id,status,input_weight_kg,output_weight_kg,waste_weight_kg,started_at,completed_at',
            'processingRecord.steps:id,processing_record_id,type,sequence,status,measurements,started_at,completed_at',
            'inspections:id,fish_batch_id,result,product_temperature,ph_level,appearance,odor,inspected_at',
            'childLinks:id,parent_batch_id,child_batch_id,allocated_weight_kg', 'childLinks.child:id,batch_code,status,total_weight_kg',
            'parentLinks:id,parent_batch_id,child_batch_id,allocated_weight_kg', 'parentLinks.parent:id,batch_code,status,total_weight_kg',
            'transportTrips:id,organization_id,vehicle_id,trip_code,driver_name,status,origin,destination,started_at,completed_at', 'transportTrips.vehicle:id,registration_number,name',
            'transportTrips.assignments:id,iot_device_id,transport_trip_id,status,firebase_sync_status,assigned_at,ended_at', 'transportTrips.assignments.device:id,device_code,display_name,status,last_seen_at',
            'latestAiPrediction',
            'packageLabels:id,fish_batch_id,label_code,package_weight_kg,package_count,printed_at',
            'packageLabels.retailReceipt:id,package_label_id,fish_batch_id,retail_location_id,received_package_count,received_weight_kg,condition_temperature,received_at', 'packageLabels.retailReceipt.location:id,code,name',
            'inventoryLots:id,fish_batch_id,retail_location_id,status,total_packages,available_packages,reserved_packages,sold_packages,expires_at', 'inventoryLots.location:id,code,name',
        ]);

        $tripIds = $batch->transportTrips->pluck('id');
        $telemetry = DB::table('sensor_readings')->whereIn('transport_trip_id', $tripIds)->selectRaw('transport_trip_id, COUNT(*) AS reading_count, MIN(product_temperature) AS minimum_temperature, MAX(product_temperature) AS maximum_temperature, AVG(product_temperature) AS average_temperature, MIN(battery_percentage) AS minimum_battery, MAX(recorded_at) AS last_recorded_at')->groupBy('transport_trip_id')->get()->keyBy('transport_trip_id');
        $alerts = DB::table('cold_chain_alerts')->whereIn('transport_trip_id', $tripIds)->selectRaw("transport_trip_id, COUNT(*) AS alert_count, SUM(CASE WHEN status IN ('OPEN', 'ACKNOWLEDGED') THEN 1 ELSE 0 END) AS open_alert_count")->groupBy('transport_trip_id')->get()->keyBy('transport_trip_id');
        $blockchain = DB::table('blockchain_event_anchors')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('blockchain_transactions', 'blockchain_transactions.id', '=', 'blockchain_event_anchors.blockchain_transaction_id')->where('traceability_events.fish_batch_id', $batch->id)->selectRaw('blockchain_transactions.status, COUNT(*) AS anchor_count')->groupBy('blockchain_transactions.status')->orderBy('blockchain_transactions.status')->get();
        $auditLogs = AuditLog::query()->with('actor:id,name')->where('auditable_type', $batch->getMorphClass())->where('auditable_id', $batch->id)->latest()->limit(50)->get();

        return ['batch' => $batch, 'telemetry' => $telemetry, 'alerts' => $alerts, 'blockchain' => $blockchain, 'auditLogs' => $auditLogs];
    }
}
