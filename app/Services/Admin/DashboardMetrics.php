<?php

namespace App\Services\Admin;

use App\Models\AIPrediction;
use App\Models\ColdChainAlert;
use App\Models\FirebaseSyncFailure;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\InventoryLot;
use App\Models\IotDevice;
use App\Models\Organization;
use App\Models\ProcessingRecord;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\RetailSale;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\IoT\AlertRuleResolver;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    public function __construct(private AlertRuleResolver $rules) {}

    public function get(): array
    {
        $offlineRule = $this->rules->resolve(null, 'DEVICE_OFFLINE');

        return [
            'supply' => [
                ['label' => 'Active users', 'value' => User::query()->where('status', 'ACTIVE')->count(), 'route' => 'admin.users.index'],
                ['label' => 'Active organizations', 'value' => Organization::query()->where('is_active', true)->count(), 'route' => 'admin.organizations.index'],
                ['label' => 'Traceable batches', 'value' => FishBatch::query()->count(), 'route' => 'admin.batches.index'],
                ['label' => 'Fishing underway', 'value' => FishingTrip::query()->where('status', 'ACTIVE')->count(), 'route' => 'admin.fishing.trips.index'],
                ['label' => 'Active transports', 'value' => TransportTrip::query()->where('status', 'ACTIVE')->count(), 'route' => 'admin.transport.trips.index'],
                ['label' => 'Processing holds', 'value' => ProcessingRecord::query()->where('status', 'QUALITY_HOLD')->count(), 'route' => 'admin.processor.records.index', 'query' => ['status' => 'QUALITY_HOLD']],
            ],
            'risk' => [
                ['label' => 'Quality incidents', 'value' => QualityInspection::query()->whereIn('result', ['FAILED', 'CONDITIONAL'])->count(), 'route' => 'admin.compliance.incidents.index'],
                ['label' => 'Open cold-chain alerts', 'value' => ColdChainAlert::query()->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->count(), 'route' => 'admin.transport.alerts.index'],
                ['label' => 'Recalled inventory lots', 'value' => InventoryLot::query()->where('status', 'RECALLED')->count(), 'route' => 'admin.compliance.recalls.index'],
                ['label' => 'Expiring within 7 days', 'value' => InventoryLot::query()->whereNotIn('status', ['SOLD_OUT', 'RECALLED', 'EXPIRED'])->whereBetween('expires_at', [now(), now()->addDays(7)])->count(), 'route' => 'admin.retail.risks.index'],
                ['label' => 'High AI predictions', 'value' => AIPrediction::query()->where('risk_level', 'HIGH')->where('predicted_at', '>=', now()->subDays(7))->count(), 'route' => 'admin.compliance.ai.index', 'query' => ['risk_level' => 'HIGH']],
                ['label' => 'Unresolved sync failures', 'value' => FirebaseSyncFailure::query()->whereNull('resolved_at')->count(), 'route' => 'admin.transport.sync.index'],
            ],
            'retail' => $this->retailSummary(),
            'qualityIncidents' => QualityInspection::query()->whereIn('result', ['FAILED', 'CONDITIONAL'])->with(['batch:id,batch_code', 'organization:id,name'])->latest('inspected_at')->limit(5)->get(),
            'coldAlerts' => ColdChainAlert::query()->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->with(['trip:id,trip_code', 'batch:id,batch_code'])->latest('last_detected_at')->limit(5)->get(),
            'inventoryRisks' => InventoryLot::query()->where(fn ($q) => $q->whereIn('status', ['RECALLED', 'EXPIRED'])->orWhere(fn ($e) => $e->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays(7))))->with(['label:id,label_code', 'organization:id,name', 'location:id,name'])->orderBy('expires_at')->limit(5)->get(),
            'deviceHealth' => IotDevice::query()->where(fn ($q) => $q->where('status', '!=', 'ACTIVE')->when($offlineRule['is_enabled'], fn ($q) => $q->orWhereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes((int) $offlineRule['duration_minutes']))))->with('organization:id,name')->withCount(['syncFailures as unresolved_failures_count' => fn ($q) => $q->whereNull('resolved_at')])->orderBy('last_seen_at')->limit(5)->get(),
            'reportJobs' => ReportExport::query()->whereIn('status', ['PENDING', 'PROCESSING', 'FAILED'])->with(['organization:id,name', 'requester:id,name'])->latest()->limit(5)->get(),
            'recentBatches' => FishBatch::query()->with('species:id,common_name')->latest()->limit(8)->get(),
            'latestReadings' => SensorReading::query()->select(['id', 'iot_device_id', 'transport_trip_id', 'product_temperature', 'humidity', 'battery_percentage', 'door_open', 'recorded_at'])->with(['device:id,device_code', 'trip:id,trip_code'])->latest('recorded_at')->limit(10)->get(),
        ];
    }

    private function retailSummary(): array
    {
        $stock = DB::table('inventory_lots')->selectRaw('COALESCE(SUM(available_packages),0) AS available, COALESCE(SUM(reserved_packages),0) AS reserved, COALESCE(SUM(sold_packages),0) AS sold')->first();

        return [
            'available_packages' => (int) ($stock->available ?? 0),
            'reserved_packages' => (int) ($stock->reserved ?? 0),
            'sold_packages' => (int) ($stock->sold ?? 0),
            'today_sales' => (float) RetailSale::query()->where('sold_at', '>=', now()->startOfDay())->sum('total'),
        ];
    }
}
