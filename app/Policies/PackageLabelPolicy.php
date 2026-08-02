<?php

namespace App\Policies;

use App\Models\FishBatch;
use App\Models\PackageLabel;
use App\Models\RetailReceipt;
use App\Models\User;

class PackageLabelPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->primaryOrganization() !== null
            && ($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR') || $user->hasRole('RETAILER'));
    }

    public function view(User $user, PackageLabel $label): bool
    {
        if ($user->hasRole('RETAILER')) {
            return RetailReceipt::query()->where('package_label_id', $label->id)->where('retailer_organization_id', $user->primaryOrganization()?->id)->exists();
        }

        $batch = FishBatch::find($label->fish_batch_id);

        return $batch !== null && ($user->primaryOrganization()?->id === $batch->organization_id || $user->hasRole('INSPECTOR'));
    }

    public function receive(User $user, PackageLabel $label): bool
    {
        return $user->hasRole('RETAILER')
            && $user->primaryOrganization() !== null;
    }
}
