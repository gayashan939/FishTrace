<?php

namespace App\Actions\Retail;

use App\Enums\BatchStatus;
use App\Enums\InventoryStatus;
use App\Enums\NotificationType;
use App\Enums\StockMovementType;
use App\Models\FishBatch;
use App\Models\InventoryLot;
use App\Models\PackageLabel;
use App\Models\RetailLocation;
use App\Models\RetailReceipt;
use App\Models\User;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;

class ReceiveRetailPackage
{
    public function __construct(private OperationalNotifier $notifier) {}

    public function execute(User $user, PackageLabel $label, array $data): InventoryLot
    {
        return DB::transaction(function () use ($user, $label, $data): InventoryLot {
            $lockedLabel = PackageLabel::query()->lockForUpdate()->findOrFail($label->id);
            abort_if(RetailReceipt::query()->where('package_label_id', $lockedLabel->id)->exists(), 409, 'This package label has already been received.');
            abort_unless((int) $data['received_package_count'] === (int) $lockedLabel->package_count, 422, 'The received package count must match the package label count.');
            $organization = $user->primaryOrganization();
            abort_unless($organization !== null, 403);
            $location = RetailLocation::query()->whereKey($data['retail_location_id'])->where('organization_id', $organization->id)->where('is_active', true)->first();
            abort_unless($location !== null, 403, 'The retail location does not belong to your organization or is inactive.');
            $batch = FishBatch::query()->lockForUpdate()->findOrFail($lockedLabel->fish_batch_id);
            abort_if($batch->is_recalled, 409, 'The package batch cannot be received.');
            abort_unless(in_array($batch->getRawOriginal('status'), [BatchStatus::PROCESSED->value, BatchStatus::READY_FOR_TRANSPORT->value, BatchStatus::IN_TRANSPORT->value], true), 409, 'The batch is not ready for retailer intake.');

            $count = (int) $data['received_package_count'];
            $receipt = RetailReceipt::create([
                'package_label_id' => $lockedLabel->id,
                'fish_batch_id' => $batch->id,
                'retailer_organization_id' => $organization->id,
                'retail_location_id' => $location->id,
                'received_by' => $user->id,
                'received_package_count' => $count,
                'received_weight_kg' => round((float) $lockedLabel->package_weight_kg * $count, 3),
                'condition_temperature' => $data['condition_temperature'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_at' => now(),
            ]);
            $lot = InventoryLot::create([
                'retail_receipt_id' => $receipt->id,
                'package_label_id' => $lockedLabel->id,
                'fish_batch_id' => $batch->id,
                'organization_id' => $organization->id,
                'retail_location_id' => $location->id,
                'status' => InventoryStatus::IN_STOCK,
                'total_packages' => $count,
                'available_packages' => $count,
                'reserved_packages' => 0,
                'sold_packages' => 0,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
            $lot->movements()->create(['actor_id' => $user->id, 'type' => StockMovementType::RECEIVED, 'quantity' => $count, 'resulting_available' => $count, 'resulting_reserved' => 0, 'resulting_sold' => 0, 'reference_type' => 'retail_receipt', 'reference_id' => $receipt->id, 'occurred_at' => now()]);
            $batch->update(['status' => BatchStatus::AVAILABLE_FOR_SALE]);
            $batch->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'RETAIL_RECEIVED', 'title' => 'Package received by retailer', 'public_data' => ['retailer' => $organization->name, 'location' => $location->name, 'package_count' => $count], 'occurred_at' => now()]);
            $batch->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'AVAILABLE_FOR_SALE', 'title' => 'Package available for sale', 'public_data' => ['location' => $location->name], 'occurred_at' => now()]);
            $this->notifier->organization($organization->id, NotificationType::RETAIL_RECEIPT, 'Retail package received', $lockedLabel->label_code.' was received into '.$location->name.'.', ['batch_id' => $batch->id, 'retail_receipt_id' => $receipt->id]);

            return $lot->load(['receipt', 'label', 'batch.species', 'location', 'movements']);
        });
    }
}
