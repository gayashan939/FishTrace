# Administrator transport and IoT operations

The Transport & IoT console is available under `/admin/transport/*` and is restricted to administrators. It covers transporter identities, vehicles, transport trips, assigned batches and devices, permanent telemetry, cold-chain alerts, and Firebase synchronization health across organizations.

## Operational surfaces

- Transporter profiles derive from users with the `TRANSPORTER` role, their organization memberships, and the newest 100 trips they created.
- Vehicle pages show organization ownership, active state, and the newest 100 trips with batch, reading, and alert counts.
- Trip pages link vehicle, creator, batches, the newest 100 device assignments, aggregate telemetry health, the newest 250 permanent readings, and the newest 100 alerts.
- Device pages show identity, firmware/capabilities, authentication enablement, credential version, health, assignments, newest 250 readings, sync cursor, and newest 100 failure records.
- Telemetry and alert directories provide server-side filters and bounded pagination.
- Sync health combines device import cursors with the failure ledger and unresolved counts.

## Privacy and authority boundaries

MySQL/MariaDB remains authoritative. The console never reads Firebase as operational truth. Firebase synchronization state is displayed only from permanent cursor, assignment, and failure records.

Raw telemetry payloads, latitude/longitude, Firebase UIDs, Firebase email addresses, failed Firebase payload bodies, credentials, tokens, and provisioning secrets are excluded from views and exports. Telemetry exports include environmental/device-health fields only. Trip pages aggregate temperatures, battery, door events, and alert counts without exposing routes as coordinate streams.

## Exports and audit

Telemetry and cold-chain alert CSV exports apply the same validated filters as their directories, are capped at 10,000 rows, and send safe download headers. Downloads create `TELEMETRY_DIRECTORY_EXPORTED` or `COLD_CHAIN_ALERTS_EXPORTED` audit records containing the actor, filters, row count, and request correlation ID.

Vehicle, IoT device, sensor reading, cold-chain alert, and transport-trip authorization is explicit. Supply-chain transporter credentials remain organization-scoped through their API policies and cannot access these cross-organization administrator pages.
