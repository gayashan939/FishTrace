<?php

namespace App\Http\Resources\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use LogicException;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource;
        if (! $log instanceof AuditLog) {
            throw new LogicException('AuditLogResource requires an AuditLog model.');
        }

        return [
            'id' => $log->id,
            'action' => $log->action,
            'entity_type' => $log->auditable_type ? Str::snake(class_basename($log->auditable_type)) : null,
            'entity_id' => $log->auditable_id,
            'actor' => $log->relationLoaded('actor') ? $log->actor?->only(['id', 'name', 'email']) : null,
            'organization' => $log->relationLoaded('organization') ? $log->organization?->only(['id', 'name', 'code']) : null,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'request_id' => $log->request_id,
            'created_at' => $log->created_at,
        ];
    }
}
