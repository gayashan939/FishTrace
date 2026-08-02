<?php

namespace App\Http\Resources\Transport;

use App\Models\DeviceAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class DeviceAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->model();

        return [
            'id' => $assignment->id,
            'iot_device_id' => $assignment->iot_device_id,
            'transport_trip_id' => $assignment->transport_trip_id,
            'status' => $assignment->status,
            'firebase_sync_status' => $assignment->firebase_sync_status,
            'assigned_at' => $assignment->assigned_at,
            'expires_at' => $assignment->expires_at,
            'ended_at' => $assignment->ended_at,
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
            'device' => $this->when($assignment->relationLoaded('device'), fn () => $assignment->device?->only(['id', 'organization_id', 'device_code', 'serial_number', 'display_name', 'status', 'firmware_version', 'battery_percentage', 'signal_strength', 'last_seen_at', 'firebase_auth_enabled', 'credential_version'])),
        ];
    }

    private function model(): DeviceAssignment
    {
        if (! $this->resource instanceof DeviceAssignment) {
            throw new LogicException('DeviceAssignmentResource requires a DeviceAssignment model.');
        }

        return $this->resource;
    }
}
