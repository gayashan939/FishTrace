<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'subject' => $this->resource->subject,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
