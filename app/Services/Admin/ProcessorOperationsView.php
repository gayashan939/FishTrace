<?php

namespace App\Services\Admin;

use App\Models\BatchIntake;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\ProcessorProfile;
use App\Models\QualityInspection;

class ProcessorOperationsView
{
    public function facility(ProcessorProfile $profile): array
    {
        $profile->load(['user:id,name,email,status', 'organization:id,name,code,is_active']);
        $intakes = BatchIntake::query()->where('processor_organization_id', $profile->organization_id)->with('batch:id,batch_code,status,total_weight_kg')->latest('received_at')->limit(100)->get();
        $records = ProcessingRecord::query()->where('organization_id', $profile->organization_id)->with('batch:id,batch_code')->withCount('steps')->latest('started_at')->limit(100)->get();

        return compact('profile', 'intakes', 'records');
    }

    public function intake(BatchIntake $intake): array
    {
        $intake->load(['batch.species:id,common_name,scientific_name', 'organization:id,name,code', 'receiver:id,name,email', 'processingRecord.processingType:id,name']);

        return compact('intake');
    }

    public function record(ProcessingRecord $record): array
    {
        $record->load(['batch:id,batch_code,status,product_type,total_weight_kg', 'organization:id,name,code', 'creator:id,name,email', 'processingType:id,name', 'steps.performer:id,name', 'inspections.inspector:id,name', 'inspections.qualityGrade:id,code,name', 'batch.childLinks.child.packageLabels', 'batch.childLinks.child.inventoryLots']);

        return compact('record');
    }

    public function inspection(QualityInspection $inspection): array
    {
        $inspection->load(['batch:id,batch_code,status,product_type', 'organization:id,name,code', 'inspector:id,name,email', 'qualityGrade:id,code,name,rank', 'processingRecord:id,fish_batch_id,status']);

        return compact('inspection');
    }

    public function label(PackageLabel $label): array
    {
        $label->load(['batch.organization:id,name,code', 'batch.parentLinks.parent:id,batch_code,status', 'retailReceipt.location:id,code,name', 'retailReceipt.retailerOrganization:id,name,code', 'retailReceipt.inventoryLot']);

        return compact('label');
    }
}
