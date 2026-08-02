<?php

namespace App\Services\IoT;

use App\Enums\NotificationType;
use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Carbon\Carbon;

class OfflineDeviceMonitor
{
    public function __construct(private AlertRuleResolver $rules, private OperationalNotifier $notifier, private AuditLogger $audit) {}

    public function check(?int $overrideMinutes = null): int
    {
        $assignments = DeviceAssignment::query()->where('status', 'ACTIVE')->with(['device', 'trip'])->limit(500)->get();
        foreach ($assignments as $assignment) {
            $device = $assignment->device;
            $trip = $assignment->trip;
            if (! $device instanceof IotDevice || ! $trip instanceof TransportTrip) {
                continue;
            }
            $rule = $this->rules->resolve($trip->organization_id, 'DEVICE_OFFLINE');
            $minutes = $overrideMinutes ?? (int) $rule['duration_minutes'];
            $lastSeenAt = $device->last_seen_at === null ? null : Carbon::parse($device->last_seen_at);
            $offline = $rule['is_enabled'] && ($lastSeenAt === null || $lastSeenAt->lt(now()->subMinutes($minutes)));
            $alert = ColdChainAlert::query()->where('transport_trip_id', $trip->id)->where('type', 'DEVICE_OFFLINE')->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->first();
            if (! $offline) {
                $alert?->update(['status' => 'RESOLVED', 'resolved_at' => now(), 'last_detected_at' => now()]);

                continue;
            }
            $age = $lastSeenAt?->diffInMinutes(now());
            if ($alert !== null) {
                $alert->update(['measured_value' => $age, 'threshold_value' => $minutes, 'last_detected_at' => now()]);
            } else {
                $alert = ColdChainAlert::create(['transport_trip_id' => $trip->id, 'type' => 'DEVICE_OFFLINE', 'severity' => 'WARNING', 'status' => 'OPEN', 'measured_value' => $age, 'threshold_value' => $minutes, 'first_detected_at' => now(), 'last_detected_at' => now()]);
                $this->audit->record('COLD_CHAIN_ALERT_CREATED', $alert, null, ['type' => 'DEVICE_OFFLINE', 'severity' => 'WARNING', 'threshold_value' => $minutes], organizationId: $trip->organization_id);
            }
            $period = $lastSeenAt?->format('YmdHi') ?? 'never';
            $this->notifier->organizationOnce('device-offline:'.$assignment->id.':'.$period, $trip->organization_id, NotificationType::DEVICE_OFFLINE, 'Assigned device offline', $device->device_code.' has not reported within the expected interval.', ['alert_id' => $alert->id, 'device_id' => $device->id, 'transport_trip_id' => $trip->id, 'last_seen_at' => $lastSeenAt?->toIso8601String(), 'threshold_minutes' => $minutes]);
        }

        return $assignments->count();
    }
}
