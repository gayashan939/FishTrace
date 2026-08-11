# Offline sync

FishTrace uses schema-versioned Drift/SQLite through `FishTraceDatabase`. Typed tables
persist safe auth metadata, boats, trips, catch drafts, catch photo metadata, batch
drafts and recent reference data. Generic local records and queued operations remain
separate so a successful upload can remove its queue operation without deleting the
local domain record.

Every queue row stores a client record ID, optional server ID, resolved endpoint,
HTTP method, JSON payload, local file references, original creation time, update
time, sync status, retry count, last error and idempotency key. Fisher catch
submission writes the local record and queue operation before showing success.

`OfflineFirstFisherRepository` performs network-first reads with durable local fallback
in API mode. Fisher mutations are written locally before success is shown.

`AppController.syncNow` processes eligible records in creation order. It marks a record as
syncing, sends it through `SyncTransport`, removes successful queue rows, and persists
failed attempts with their error, incremented retry count and bounded exponential next
attempt time. Returning online triggers sync; manual retry clears the delay. Mock mode
uses `MockSyncTransport`; API mode sends each operation to its resolved Laravel
endpoint with its record type, payload, client ID, original timestamps and
`Idempotency-Key`. Failed operations keep their endpoint and file metadata for
manual or reconnect-driven retry.

The database file is stored in the platform application-documents directory as
`fishtrace.sqlite`. Tests use an in-memory Drift database.
