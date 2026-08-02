# System and administrator account settings

Administrators manage non-secret runtime options at `/admin/settings/system`. The supported allowlist is platform name, public support email, consumer trace-portal availability and notice, and Firebase telemetry retention from 24 to 72 hours. Stored values override defaults; telemetry retention otherwise continues to use `FIREBASE_TELEMETRY_RETENTION_HOURS`. Values are cached briefly and invalidated immediately after an audited update.

The system-settings interface intentionally cannot change application keys, database credentials, Firebase service accounts, external AI/blockchain credentials or endpoints, debug mode, queue drivers, or other deployment security controls. Those remain environment-only.

The consumer trace web page and API return `503` while the portal is disabled. When enabled, only the configured public notice, platform name, and support email are added to the existing privacy-safe response. The Firebase cleanup command uses the effective telemetry-retention value and still deletes only synchronized records.

Administrators update their own name and sign-in email at `/admin/profile`; role and organization assignments remain read-only there. `/admin/security` requires the current password for password changes and session revocation. Password changes revoke all Sanctum tokens and other database sessions while preserving and regenerating the current browser session. All profile and security changes are audited.
