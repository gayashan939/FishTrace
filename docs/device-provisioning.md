# Device provisioning

Create a device as ADMIN, then call `POST /api/v1/iot/devices/{uuid}/firebase-provision`. The response contains the Firebase email/password once; store it in the device's protected provisioning flow. Laravel retains identity metadata and credential version, never the raw password.

The provisioning and rotation endpoints use a dedicated Firebase device-authentication contract, allowing the mock and Admin SDK drivers to exercise the same workflow. `firebase-rotate` invalidates the prior password and returns the replacement once. Rotation, Firebase disablement, and device deactivation are blocked while the device has an active trip assignment. `firebase-disable` disables Firebase Authentication without retiring the inventory record; `deactivate` disables both access and operational use. Reactivation does not silently re-enable Firebase credentials—a fresh provision/rotation is required.

Transporters can read health, bounded permanent readings, latest reading, and sanitized synchronization status only for devices in their organization. Provisioning and lifecycle mutation remain administrator-only. Raw Firebase emails, passwords, failure payloads, and provider errors are excluded.

An ESP32 uses Firebase Identity Toolkit REST sign-in, stores the returned refresh token securely, refreshes before expiry, and sends the ID token with RTDB REST writes. Never embed service-account or Admin SDK credentials in firmware. Rotate credentials on suspected exposure and deactivate access when a device retires.
