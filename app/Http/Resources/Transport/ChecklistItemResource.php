<?php

namespace App\Http\Resources\Transport;

use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class ChecklistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->model();

        return [
            'id' => $item->id,
            'pre_trip_checklist_id' => $item->pre_trip_checklist_id,
            'item_key' => $item->item_key,
            'label' => $item->label,
            'is_mandatory' => $item->is_mandatory,
            'is_completed' => $item->is_completed,
            'completed_by' => $item->completed_by,
            'completed_at' => $item->completed_at,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function model(): ChecklistItem
    {
        if (! $this->resource instanceof ChecklistItem) {
            throw new LogicException('ChecklistItemResource requires a ChecklistItem model.');
        }

        return $this->resource;
    }
}
