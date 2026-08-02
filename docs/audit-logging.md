# Audit logging

FishTrace records immutable audit events for security-sensitive authentication and business mutations. The centralized model observer covers users, vessels, trips, catches, batches, processor records, inspections, devices, cold-chain alerts, retail receipts, inventory, sales, files, and report exports. Explicit service events cover login outcomes, password reset milestones, file downloads, Firebase failures, cold-chain alerts, blockchain verification failures, and queue-generated report outcomes.

Each record can contain the actor, actor organization, action, entity type and UUID, redacted old/new state, IP address, user agent, request UUID, and UTC creation time. Passwords, tokens, OTPs, credentials, authorization data, cookies, private keys, Firebase details, error messages, private storage paths, disks, and raw telemetry payloads are replaced with `[REDACTED]` recursively before persistence.

Audit records cannot be updated or deleted through the Eloquent model or API. The only deletion path is `fishtrace:prune-audit-logs`, which clamps retention to at least 365 days, processes bounded batches, and supports `--dry-run`. Production defaults to 2,555 days and runs monthly through the scheduler.

## Authorization boundary

`ADMIN` represents the regulator operations role for audit oversight and is explicitly allowed to search audit records across organizations. This is a narrow, documented exception to normal organization isolation. `INSPECTOR` users can search only records from their primary organization. Other supply-chain roles cannot access audit APIs or the administration page.

## Interfaces

- `GET /api/v1/audit-logs` supports organization, actor, action, entity, request UUID, date range, and bounded pagination filters.
- `GET /api/v1/audit-logs/{auditLog}` returns one authorized record.
- `GET /api/v1/audit-logs/export` streams at most 10,000 filtered CSV rows and audits the export itself.
- `/admin/audit-logs` provides an authenticated, filterable regulator console with paginated redacted state inspection.

Audit tables should be included in encrypted backups. Database credentials used by the web application should not be granted broad manual delete privileges in production where hosting controls permit separation.
