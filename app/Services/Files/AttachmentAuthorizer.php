<?php

namespace App\Services\Files;

use App\Enums\FileCategory;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\ProcessingRecord;
use App\Models\QualityInspection;
use App\Models\RetailReceipt;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AttachmentAuthorizer
{
    public function authorize(User $user, FileCategory $category, string $entityType, string $entityId): void
    {
        abort_unless($this->categoryAllows($category, $entityType), 422, 'The file category is not valid for this resource type.');
        $model = $this->find($entityType, $entityId);
        $organizationId = $user->primaryOrganization()?->id;
        abort_unless($organizationId !== null, 403);

        $allowed = match ($model::class) {
            RetailReceipt::class => $model->retailer_organization_id === $organizationId,
            FishBatch::class, TransportTrip::class, QualityInspection::class, ProcessingRecord::class => $user->can('view', $model),
            default => $model->getAttribute('organization_id') === $organizationId,
        };
        abort_unless($allowed, 403, 'You cannot attach files to this resource.');
    }

    private function find(string $type, string $id): Model
    {
        $class = match ($type) {
            'boat' => Boat::class,
            'catch_record' => CatchRecord::class,
            'quality_inspection' => QualityInspection::class,
            'processing_record' => ProcessingRecord::class,
            'fish_batch' => FishBatch::class,
            'transport_trip' => TransportTrip::class,
            'retail_receipt' => RetailReceipt::class,
            default => abort(422, 'Unsupported attachment resource type.'),
        };

        return $class::query()->findOrFail($id);
    }

    private function categoryAllows(FileCategory $category, string $type): bool
    {
        return match ($category) {
            FileCategory::BOAT_IMAGE => $type === 'boat',
            FileCategory::CATCH_IMAGE => $type === 'catch_record',
            FileCategory::INSPECTION_IMAGE => $type === 'quality_inspection',
            FileCategory::PROCESSING_IMAGE => $type === 'processing_record',
            FileCategory::BATCH_DOCUMENT, FileCategory::CERTIFICATE => $type === 'fish_batch',
            FileCategory::DELIVERY_IMAGE, FileCategory::DELIVERY_SIGNATURE => in_array($type, ['transport_trip', 'retail_receipt'], true),
            FileCategory::REPORT_EXPORT => false,
        };
    }
}
