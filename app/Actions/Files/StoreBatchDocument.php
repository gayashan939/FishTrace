<?php

namespace App\Actions\Files;

use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\User;

class StoreBatchDocument
{
    public function __construct(private StoreFileAsset $storeFile) {}

    public function execute(User $user, FishBatch $batch, array $data): FileAsset
    {
        return $this->storeFile->execute($user, [
            'category' => $data['category'],
            'entity_type' => 'fish_batch',
            'entity_id' => $batch->id,
            'file' => $data['file'],
        ]);
    }
}
