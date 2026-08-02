<?php

namespace App\Actions\Processing;

use App\Enums\ProcessingStatus;
use App\Models\ProcessingRecord;
use Illuminate\Support\Facades\DB;

class UpdateProcessingRecord
{
    public function execute(ProcessingRecord $record, array $attributes): ProcessingRecord
    {
        return DB::transaction(function () use ($record, $attributes): ProcessingRecord {
            $locked = ProcessingRecord::query()->lockForUpdate()->findOrFail($record->id);
            abort_if($locked->hasStatus(ProcessingStatus::COMPLETED), 409, 'Completed processing records cannot be edited.');
            $locked->update($attributes);

            return $locked->fresh() ?? $locked;
        });
    }
}
