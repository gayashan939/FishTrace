<?php

namespace App\Actions\IoT;

use App\Models\IotDevice;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ManageIotDevice
{
    private const AUDIT_FIELDS = [
        'organization_id',
        'device_code',
        'serial_number',
        'display_name',
        'status',
        'firmware_version',
        'supports_product_temperature',
        'supports_air_temperature',
        'supports_humidity',
        'supports_gps',
        'supports_door_sensor',
    ];

    public function __construct(private AuditLogger $audit) {}

    public function create(User $actor, array $data): IotDevice
    {
        return DB::transaction(function () use ($actor, $data): IotDevice {
            $device = IotDevice::query()->create($data);
            $this->audit->record('IOT_DEVICE_CREATED', $device, null, $device->only(self::AUDIT_FIELDS), $actor, $device->organization_id);

            return $device;
        });
    }

    public function update(User $actor, IotDevice $device, array $data): IotDevice
    {
        return DB::transaction(function () use ($actor, $device, $data): IotDevice {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            $old = $locked->only(self::AUDIT_FIELDS);
            $locked->update($data);
            $this->audit->record('IOT_DEVICE_UPDATED', $locked, $old, $locked->only(self::AUDIT_FIELDS), $actor, $locked->organization_id);

            return $locked->fresh() ?? $locked;
        });
    }
}
