<?php

namespace App\Http\Resources\Processor;

use App\Models\ProcessingStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class ProcessingStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $step = $this->model();

        return [
            'id' => $step->id,
            'processing_record_id' => $step->processing_record_id,
            'type' => $step->type,
            'sequence' => $step->sequence,
            'status' => $step->status,
            'measurements' => $step->measurements,
            'notes' => $step->notes,
            'performed_by' => $step->performed_by,
            'started_at' => $step->started_at,
            'completed_at' => $step->completed_at,
            'created_at' => $step->created_at,
            'updated_at' => $step->updated_at,
        ];
    }

    private function model(): ProcessingStep
    {
        if (! $this->resource instanceof ProcessingStep) {
            throw new LogicException('ProcessingStepResource requires a ProcessingStep model.');
        }

        return $this->resource;
    }
}
