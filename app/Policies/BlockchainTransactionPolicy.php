<?php

namespace App\Policies;

use App\Models\BlockchainTransaction;
use App\Models\User;

class BlockchainTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function view(User $user, BlockchainTransaction $transaction): bool
    {
        return $user->hasRole('ADMIN');
    }
}
