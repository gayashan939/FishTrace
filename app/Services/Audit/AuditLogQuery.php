<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\BatchIntake;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\InventoryLot;
use App\Models\IotDevice;
use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class AuditLogQuery
{
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        return $this->build($user, $filters)->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();
    }

    public function details(AuditLog $auditLog): AuditLog
    {
        return $auditLog->load(['actor:id,name,email', 'organization:id,name,code']);
    }

    /** @return Collection<int, AuditLog> */
    public function exportRecords(User $user, array $filters): Collection
    {
        return $this->build($user, $filters)->limit(10000)->get();
    }

    /** @return Builder<AuditLog> */
    public function build(User $user, array $filters): Builder
    {
        $query = AuditLog::query()->with(['actor:id,name,email', 'organization:id,name,code']);
        if (! $user->hasRole('ADMIN')) {
            $query->where('organization_id', $user->primaryOrganization()?->id);
        } elseif (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        foreach (['user_id', 'action', 'request_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $field === 'action' ? mb_strtoupper($filters[$field]) : $filters[$field]);
            }
        }
        if (isset($filters['entity_type'])) {
            $query->where('auditable_type', $this->entityClass($filters['entity_type']));
        }
        if (isset($filters['entity_id'])) {
            $query->where('auditable_id', $filters['entity_id']);
        }
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        return $query->latest('created_at');
    }

    private function entityClass(string $alias): string
    {
        return match ($alias) {
            'user' => User::class,
            'boat' => Boat::class,
            'fishing_trip' => FishingTrip::class,
            'catch_record' => CatchRecord::class,
            'fish_batch' => FishBatch::class,
            'batch_intake' => BatchIntake::class,
            'processing_record' => ProcessingRecord::class,
            'processing_step' => ProcessingStep::class,
            'quality_inspection' => QualityInspection::class,
            'transport_trip' => TransportTrip::class,
            'device_assignment' => DeviceAssignment::class,
            'iot_device' => IotDevice::class,
            'cold_chain_alert' => ColdChainAlert::class,
            'retail_receipt' => RetailReceipt::class,
            'inventory_lot' => InventoryLot::class,
            'retail_sale' => RetailSale::class,
            'file_asset' => FileAsset::class,
            'report_export' => ReportExport::class,
            default => throw new InvalidArgumentException('Unsupported audit entity alias.'),
        };
    }
}
