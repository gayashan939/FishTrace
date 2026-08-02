<?php

namespace App\Http\Resources\Fisher;

use App\Models\TraceabilityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class TraceabilityEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $event = $this->model();

        return [
            'id' => $event->id,
            'fish_batch_id' => $event->fish_batch_id,
            'organization_id' => $event->organization_id,
            'actor_id' => $event->actor_id,
            'event_type' => $event->event_type,
            'title' => $event->title,
            'public_data' => $event->public_data,
            'occurred_at' => $event->occurred_at,
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];
    }

    private function model(): TraceabilityEvent
    {
        if (! $this->resource instanceof TraceabilityEvent) {
            throw new LogicException('TraceabilityEventResource requires a TraceabilityEvent model.');
        }

        return $this->resource;
    }
}
