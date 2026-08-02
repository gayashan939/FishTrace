<?php

namespace App\Services\Admin;

use App\Models\BatchIntake;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\ProcessorProfile;
use App\Models\QualityInspection;
use Illuminate\Database\Eloquent\Builder;

class ProcessorOperationsQuery
{
    public function facilities(array $f): Builder
    {
        return ProcessorProfile::query()->with(['user:id,name,email,status', 'organization:id,name,code,is_active'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('facility_name', 'like', "%{$term}%")->orWhere('license_number', 'like', "%{$term}%")->orWhereHas('user', fn (Builder $u): Builder => $u->where('name', 'like', "%{$term}%"))))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->orderBy('facility_name');
    }

    public function intakes(array $f): Builder
    {
        return BatchIntake::query()->with(['batch:id,batch_code,status,total_weight_kg,product_type', 'organization:id,name,code', 'receiver:id,name', 'processingRecord:id,batch_intake_id,status'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('processor_organization_id', $id))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('received_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('received_at', '<=', $date.' 23:59:59'))->latest('received_at');
    }

    public function records(array $f): Builder
    {
        return ProcessingRecord::query()->with(['batch:id,batch_code,status,product_type', 'organization:id,name,code', 'creator:id,name', 'processingType:id,name'])->withCount(['steps', 'inspections'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->when($f['processing_type_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('processing_type_id', $id))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('started_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('started_at', '<=', $date.' 23:59:59'))->latest('started_at');
    }

    public function inspections(array $f): Builder
    {
        return QualityInspection::query()->with(['batch:id,batch_code,status', 'organization:id,name,code', 'inspector:id,name', 'qualityGrade:id,code,name'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['result'] ?? null, fn (Builder $q, string $result): Builder => $q->where('result', $result))->when($f['quality_grade_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('quality_grade_id', $id))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('inspected_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('inspected_at', '<=', $date.' 23:59:59'))->latest('inspected_at');
    }

    public function labels(array $f): Builder
    {
        return PackageLabel::query()->with(['batch:id,batch_code,organization_id,status,product_type,total_weight_kg', 'batch.organization:id,name,code', 'retailReceipt:id,package_label_id,retailer_organization_id,received_at'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('label_code', 'like', "%{$term}%")->orWhereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%"))))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('batch', fn (Builder $b): Builder => $b->where('organization_id', $id)))->latest();
    }
}
