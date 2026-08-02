<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseDeviceAuth;
use App\Models\IotDevice;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceProvisioner
{
    public function __construct(private FirebaseDeviceAuth $auth, private AuditLogger $audit) {}

    public function provision(IotDevice $device, User $actor, bool $rotation = false): array
    {
        [$uid, $email, $password] = DB::transaction(function () use ($device, $rotation): array {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            $this->assertProvisionable($locked, $rotation);

            return [
                $locked->firebase_uid ?: 'device:'.$locked->id,
                'device+'.strtolower($locked->device_code).'@fishtrace.invalid',
                Str::password(24, symbols: true),
            ];
        });

        $this->auth->upsert($uid, $email, $password);

        return DB::transaction(function () use ($device, $actor, $rotation, $uid, $email, $password): array {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            $this->assertProvisionable($locked, $rotation);
            $locked->update(['firebase_uid' => $uid, 'firebase_email' => $email, 'firebase_auth_enabled' => true, 'credential_version' => $locked->credential_version + 1]);
            $this->audit->record($rotation ? 'IOT_DEVICE_CREDENTIAL_ROTATED' : 'IOT_DEVICE_PROVISIONED', $locked, null, ['firebase_auth_enabled' => true, 'credential_version' => $locked->credential_version], $actor, $locked->organization_id);

            return ['firebase_uid' => $uid, 'firebase_email' => $email, 'firebase_password' => $password, 'credential_version' => $locked->credential_version, 'warning' => 'Store this credential now. It will not be shown again.'];
        });
    }

    public function disableFirebase(IotDevice $device, User $actor): IotDevice
    {
        $uid = DB::transaction(function () use ($device): ?string {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            abort_if($locked->assignments()->where('status', 'ACTIVE')->exists(), 409, 'End the active trip assignment before disabling Firebase access.');

            return $locked->firebase_auth_enabled ? $locked->firebase_uid : null;
        });
        if ($uid !== null) {
            $this->auth->disable($uid);
        }

        return DB::transaction(function () use ($device, $actor): IotDevice {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            abort_if($locked->assignments()->where('status', 'ACTIVE')->exists(), 409, 'End the active trip assignment before disabling Firebase access.');
            $locked->update(['firebase_auth_enabled' => false]);
            $this->audit->record('IOT_DEVICE_FIREBASE_DISABLED', $locked, null, ['firebase_auth_enabled' => false], $actor, $locked->organization_id);

            return $locked->fresh() ?? $locked;
        });
    }

    public function activate(IotDevice $device, User $actor): IotDevice
    {
        return DB::transaction(function () use ($device, $actor): IotDevice {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            $locked->update(['status' => 'ACTIVE']);
            $this->audit->record('IOT_DEVICE_ACTIVATED', $locked, null, ['status' => 'ACTIVE'], $actor, $locked->organization_id);

            return $locked->fresh() ?? $locked;
        });
    }

    public function deactivate(IotDevice $device, User $actor): IotDevice
    {
        $uid = DB::transaction(function () use ($device): ?string {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            abort_if($locked->assignments()->where('status', 'ACTIVE')->exists(), 409, 'End the active trip assignment before deactivating the device.');

            return $locked->firebase_auth_enabled ? $locked->firebase_uid : null;
        });
        if ($uid !== null) {
            $this->auth->disable($uid);
        }

        return DB::transaction(function () use ($device, $actor): IotDevice {
            $locked = IotDevice::query()->lockForUpdate()->findOrFail($device->id);
            abort_if($locked->assignments()->where('status', 'ACTIVE')->exists(), 409, 'End the active trip assignment before deactivating the device.');
            $locked->update(['status' => 'INACTIVE', 'firebase_auth_enabled' => false]);
            $this->audit->record('IOT_DEVICE_DEACTIVATED', $locked, null, ['status' => 'INACTIVE', 'firebase_auth_enabled' => false], $actor, $locked->organization_id);

            return $locked->fresh() ?? $locked;
        });
    }

    private function assertProvisionable(IotDevice $device, bool $rotation): void
    {
        abort_unless($device->status === 'ACTIVE', 409, 'Only an active device can be provisioned.');
        abort_if($rotation && ! $device->firebase_auth_enabled, 409, 'Provision Firebase access before rotating credentials.');
        abort_if(! $rotation && $device->firebase_auth_enabled, 409, 'Firebase access is already provisioned. Rotate credentials instead.');
        abort_if($rotation && $device->assignments()->where('status', 'ACTIVE')->exists(), 409, 'End the active trip assignment before rotating credentials.');
    }
}
