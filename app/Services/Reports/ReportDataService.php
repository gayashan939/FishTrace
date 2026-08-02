<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    public function generate(User $user, ReportType $type, array $filters): array
    {
        $organizationId = $user->primaryOrganization()?->id;
        abort_unless($organizationId !== null, 403);

        $rows = match ($type) {
            ReportType::CATCH_VOLUME => $this->catchVolume($organizationId, $filters),
            ReportType::SPECIES_DISTRIBUTION => $this->speciesDistribution($organizationId, $filters),
            ReportType::BATCH_STATUS => $this->batchStatus($organizationId, $filters),
            ReportType::PROCESSING_YIELD => $this->processingYield($organizationId, $filters),
            ReportType::QUALITY_GRADES => $this->qualityGrades($organizationId, $filters),
            ReportType::TRANSPORT_PERFORMANCE => $this->transportPerformance($organizationId, $filters),
            ReportType::COLD_CHAIN_VIOLATIONS => $this->coldChainViolations($organizationId, $filters),
            ReportType::DEVICE_UPTIME => $this->deviceUptime($organizationId, $filters),
            ReportType::FIREBASE_IMPORT_STATUS => $this->firebaseImportStatus($organizationId, $filters),
            ReportType::AI_RISK_DISTRIBUTION => $this->aiRiskDistribution($organizationId, $filters),
            ReportType::INVENTORY => $this->inventory($organizationId, $filters),
            ReportType::SALES => $this->sales($organizationId, $filters),
            ReportType::RECALLS => $this->recalls($organizationId, $filters),
            ReportType::BLOCKCHAIN_STATUS => $this->blockchainStatus($organizationId, $filters),
        };

        return ['report' => $type->value, 'filters' => $filters, 'generated_at' => now()->toIso8601String(), 'row_count' => count($rows), 'rows' => $rows];
    }

    public function summary(User $user, array $filters): array
    {
        $organizationId = $user->primaryOrganization()?->id;
        abort_unless($organizationId !== null, 403);

        return [
            'catch_weight_kg' => (float) $this->dates(DB::table('catch_records')->where('organization_id', $organizationId), 'caught_at', $filters)->sum('weight_kg'),
            'batches' => DB::table('fish_batches')->where('organization_id', $organizationId)->count(),
            'processing_records' => DB::table('processing_records')->where('organization_id', $organizationId)->count(),
            'transport_trips' => DB::table('transport_trips')->where('organization_id', $organizationId)->count(),
            'open_cold_chain_alerts' => DB::table('cold_chain_alerts')->join('transport_trips', 'transport_trips.id', '=', 'cold_chain_alerts.transport_trip_id')->where('transport_trips.organization_id', $organizationId)->whereIn('cold_chain_alerts.status', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'available_inventory_packages' => (int) DB::table('inventory_lots')->where('organization_id', $organizationId)->whereIn('status', ['IN_STOCK', 'RESERVED'])->sum('available_packages'),
            'sales_total' => (float) $this->dates(DB::table('retail_sales')->where('organization_id', $organizationId), 'sold_at', $filters)->sum('total'),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function catchVolume(string $organizationId, array $filters): array
    {
        $query = DB::table('catch_records')->join('fish_species', 'fish_species.id', '=', 'catch_records.fish_species_id')->where('catch_records.organization_id', $organizationId)->selectRaw('fish_species.common_name AS species, COUNT(*) AS catch_count, SUM(catch_records.quantity) AS quantity, SUM(catch_records.weight_kg) AS total_weight_kg')->groupBy('fish_species.id', 'fish_species.common_name');
        $this->species($query, 'catch_records.fish_species_id', $filters);

        return $this->dates($query, 'catch_records.caught_at', $filters)->orderByDesc('total_weight_kg')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function speciesDistribution(string $organizationId, array $filters): array
    {
        return $this->catchVolume($organizationId, $filters);
    }

    private function batchStatus(string $organizationId, array $filters): array
    {
        $query = DB::table('fish_batches')->where('organization_id', $organizationId)->selectRaw('status, COUNT(*) AS batch_count, SUM(total_weight_kg) AS total_weight_kg')->groupBy('status');
        $this->species($query, 'fish_species_id', $filters);
        $this->status($query, 'status', $filters);

        return $this->dates($query, 'created_at', $filters)->orderBy('status')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function processingYield(string $organizationId, array $filters): array
    {
        $query = DB::table('processing_records')->join('fish_batches', 'fish_batches.id', '=', 'processing_records.fish_batch_id')->where('processing_records.organization_id', $organizationId)->select(['fish_batches.batch_code', 'processing_records.status', 'processing_records.input_weight_kg', 'processing_records.output_weight_kg', 'processing_records.waste_weight_kg', 'processing_records.started_at', 'processing_records.completed_at']);
        $this->species($query, 'fish_batches.fish_species_id', $filters);
        $this->status($query, 'processing_records.status', $filters);

        return $this->dates($query, 'processing_records.started_at', $filters)->latest('processing_records.started_at')->limit(1000)->get()->map(function ($row): array {
            $data = (array) $row;
            $data['yield_percentage'] = (float) $row->input_weight_kg > 0 ? round(((float) $row->output_weight_kg / (float) $row->input_weight_kg) * 100, 2) : null;

            return $data;
        })->all();
    }

    private function qualityGrades(string $organizationId, array $filters): array
    {
        $query = DB::table('quality_inspections')->leftJoin('quality_grades', 'quality_grades.id', '=', 'quality_inspections.quality_grade_id')->where('quality_inspections.organization_id', $organizationId)->selectRaw("COALESCE(quality_grades.code, 'UNSPECIFIED') AS grade, quality_inspections.result, COUNT(*) AS inspection_count")->groupBy('quality_grades.code', 'quality_inspections.result');
        $this->status($query, 'quality_inspections.result', $filters);

        return $this->dates($query, 'quality_inspections.inspected_at', $filters)->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function transportPerformance(string $organizationId, array $filters): array
    {
        $query = DB::table('transport_trips')->leftJoin('sensor_readings', 'sensor_readings.transport_trip_id', '=', 'transport_trips.id')->where('transport_trips.organization_id', $organizationId)->selectRaw('transport_trips.trip_code, transport_trips.status, transport_trips.started_at, transport_trips.completed_at, COUNT(sensor_readings.id) AS reading_count, MIN(sensor_readings.product_temperature) AS minimum_temperature, MAX(sensor_readings.product_temperature) AS maximum_temperature')->groupBy('transport_trips.id', 'transport_trips.trip_code', 'transport_trips.status', 'transport_trips.started_at', 'transport_trips.completed_at');
        $this->status($query, 'transport_trips.status', $filters);

        return $this->dates($query, 'transport_trips.created_at', $filters)->latest('transport_trips.created_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function coldChainViolations(string $organizationId, array $filters): array
    {
        $query = DB::table('cold_chain_alerts')->join('transport_trips', 'transport_trips.id', '=', 'cold_chain_alerts.transport_trip_id')->where('transport_trips.organization_id', $organizationId)->select(['transport_trips.trip_code', 'cold_chain_alerts.type', 'cold_chain_alerts.severity', 'cold_chain_alerts.status', 'cold_chain_alerts.measured_value', 'cold_chain_alerts.threshold_value', 'cold_chain_alerts.first_detected_at', 'cold_chain_alerts.resolved_at']);
        $this->status($query, 'cold_chain_alerts.status', $filters);

        return $this->dates($query, 'cold_chain_alerts.first_detected_at', $filters)->latest('cold_chain_alerts.first_detected_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function deviceUptime(string $organizationId, array $filters): array
    {
        $query = DB::table('iot_devices')->leftJoin('sensor_readings', 'sensor_readings.iot_device_id', '=', 'iot_devices.id')->where('iot_devices.organization_id', $organizationId)->selectRaw('iot_devices.device_code, iot_devices.status, COUNT(sensor_readings.id) AS reading_count, MIN(sensor_readings.recorded_at) AS first_reading_at, MAX(sensor_readings.recorded_at) AS last_reading_at')->groupBy('iot_devices.id', 'iot_devices.device_code', 'iot_devices.status');
        $this->status($query, 'iot_devices.status', $filters);

        return $query->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function firebaseImportStatus(string $organizationId, array $filters): array
    {
        $query = DB::table('firebase_sync_failures')->leftJoin('iot_devices', 'iot_devices.id', '=', 'firebase_sync_failures.device_id')->where('iot_devices.organization_id', $organizationId)->select(['iot_devices.device_code', 'firebase_sync_failures.message_id', 'firebase_sync_failures.error_code', 'firebase_sync_failures.retry_count', 'firebase_sync_failures.first_failed_at', 'firebase_sync_failures.last_failed_at', 'firebase_sync_failures.resolved_at']);
        $this->status($query, 'firebase_sync_failures.error_code', $filters);

        return $this->dates($query, 'firebase_sync_failures.first_failed_at', $filters)->latest('firebase_sync_failures.first_failed_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function aiRiskDistribution(string $organizationId, array $filters): array
    {
        $query = DB::table('ai_predictions')->join('fish_batches', 'fish_batches.id', '=', 'ai_predictions.fish_batch_id')->where('fish_batches.organization_id', $organizationId)->selectRaw('ai_predictions.risk_level, COUNT(*) AS prediction_count, AVG(ai_predictions.confidence) AS average_confidence')->groupBy('ai_predictions.risk_level');
        $this->species($query, 'fish_batches.fish_species_id', $filters);
        $this->status($query, 'ai_predictions.risk_level', $filters);

        return $this->dates($query, 'ai_predictions.predicted_at', $filters)->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function inventory(string $organizationId, array $filters): array
    {
        $query = DB::table('inventory_lots')->join('fish_batches', 'fish_batches.id', '=', 'inventory_lots.fish_batch_id')->join('package_labels', 'package_labels.id', '=', 'inventory_lots.package_label_id')->join('retail_locations', 'retail_locations.id', '=', 'inventory_lots.retail_location_id')->where('inventory_lots.organization_id', $organizationId)->select(['package_labels.label_code', 'fish_batches.batch_code', 'retail_locations.name AS location', 'inventory_lots.status', 'inventory_lots.total_packages', 'inventory_lots.available_packages', 'inventory_lots.reserved_packages', 'inventory_lots.sold_packages', 'inventory_lots.expires_at']);
        $this->species($query, 'fish_batches.fish_species_id', $filters);
        $this->status($query, 'inventory_lots.status', $filters);

        return $this->dates($query, 'inventory_lots.created_at', $filters)->latest('inventory_lots.created_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function sales(string $organizationId, array $filters): array
    {
        $query = DB::table('retail_sales')->join('retail_locations', 'retail_locations.id', '=', 'retail_sales.retail_location_id')->where('retail_sales.organization_id', $organizationId)->select(['retail_sales.receipt_number', 'retail_locations.name AS location', 'retail_sales.status', 'retail_sales.total', 'retail_sales.sold_at']);
        $this->status($query, 'retail_sales.status', $filters);

        return $this->dates($query, 'retail_sales.sold_at', $filters)->latest('retail_sales.sold_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function recalls(string $organizationId, array $filters): array
    {
        $query = DB::table('inventory_lots')->join('fish_batches', 'fish_batches.id', '=', 'inventory_lots.fish_batch_id')->join('package_labels', 'package_labels.id', '=', 'inventory_lots.package_label_id')->where('inventory_lots.organization_id', $organizationId)->where('inventory_lots.status', 'RECALLED')->select(['package_labels.label_code', 'fish_batches.batch_code', 'inventory_lots.total_packages', 'inventory_lots.available_packages', 'inventory_lots.updated_at AS recalled_at']);
        $this->species($query, 'fish_batches.fish_species_id', $filters);

        return $this->dates($query, 'inventory_lots.updated_at', $filters)->latest('inventory_lots.updated_at')->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function blockchainStatus(string $organizationId, array $filters): array
    {
        $query = DB::table('blockchain_transactions')->join('blockchain_event_anchors', 'blockchain_event_anchors.blockchain_transaction_id', '=', 'blockchain_transactions.id')->join('traceability_events', 'traceability_events.id', '=', 'blockchain_event_anchors.traceability_event_id')->join('fish_batches', 'fish_batches.id', '=', 'traceability_events.fish_batch_id')->where('fish_batches.organization_id', $organizationId)->selectRaw('blockchain_transactions.status, COUNT(*) AS transaction_count')->groupBy('blockchain_transactions.status');
        $this->species($query, 'fish_batches.fish_species_id', $filters);
        $this->status($query, 'blockchain_transactions.status', $filters);

        return $this->dates($query, 'blockchain_transactions.created_at', $filters)->limit(1000)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function dates(Builder $query, string $column, array $filters): Builder
    {
        if (isset($filters['date_from'])) {
            $query->where($column, '>=', $filters['date_from'].' 00:00:00');
        }
        if (isset($filters['date_to'])) {
            $query->where($column, '<=', $filters['date_to'].' 23:59:59');
        }

        return $query;
    }

    private function species(Builder $query, string $column, array $filters): void
    {
        if (isset($filters['species_id'])) {
            $query->where($column, $filters['species_id']);
        }
    }

    private function status(Builder $query, string $column, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where($column, $filters['status']);
        }
    }
}
