<?php

namespace App\Services\Files;

use App\Models\FishBatch;
use Illuminate\Pagination\LengthAwarePaginator;

class BatchDocumentQuery
{
    public function forBatch(FishBatch $batch, int $perPage): LengthAwarePaginator
    {
        return $batch->documents()->latest()->paginate($perPage);
    }
}
