# Transporter workflow

Vehicle and transport-trip directories use authorized Form Requests with pagination capped at 100 and delegate organization-scoped reads to the typed transport query service. Vehicle, trip, assignment, checklist, incident, and delivery responses use explicit Resources. Firebase UIDs/emails and assignment synchronization error details are excluded from nested trip, dashboard, and lifecycle responses; live-access output is an explicit whitelist.

A TRANSPORTER manages organization-scoped vehicles, creates and edits a pre-departure trip, attaches non-recalled batches, and assigns an active Firebase-provisioned device. A batch may belong to only one unfinished trip and an active vehicle cannot be retired while referenced by unfinished work. Assignment changes persist in MySQL first, then mirror the device/batch assignment and all participating organization members to Firebase. The durable assignment records `SYNCED` or a sanitized `FAILED` state rather than silently reporting success.

Pre-departure trip edits, batch attachment/removal, device assignment/removal, and checklist changes lock the trip before checking its state. Device assignment also locks the device before checking active assignments. This prevents an operation that began against stale `DRAFT` or `READY` data from committing after departure, and nested batch removal returns 404 unless the batch belongs to the route's trip.

Every trip receives five mandatory checklist items covering the vehicle, refrigeration, cargo, sensor, and door seal. Starting requires an active vehicle, driver, at least one batch, a completed checklist, and an unexpired synchronized assignment. It atomically moves the trip to `ACTIVE` and batches to `IN_TRANSPORT`.

During transport, operators can record incidents and inspect bounded reading history, latest state, and an aggregate sensor summary. Delivery confirmation requires receiver details before final completion. Confirmation and incidents create privacy-safe traceability milestones; receiver contact and incident descriptions remain private. Completion ends the assignment, removes live-trip/member access, retains permanent MySQL telemetry, and records the completed milestone. Draft/ready trips can instead remove batches/devices or be cancelled with a reason.

The transporter explicitly marks an active trip as arrived before receiver confirmation. Arrival is idempotent, timestamped, audited, and added to every linked batch timeline. Delivery photos and the receiver signature are uploaded as private `DELIVERY_IMAGE` and `DELIVERY_SIGNATURE` assets linked to the transport trip; confirmation cannot proceed from the mobile flow until both forms of evidence are captured.

Incident and delivery-confirmation actions lock the parent trip and recheck `ACTIVE` inside their transaction. Late writes after completion or cancellation are rejected without creating operational or traceability records.

`php artisan fishtrace:reconcile-firebase-assignments` retries bounded `PENDING`/`FAILED` mirrors and closures. It is scheduled every five minutes with an overlap lock.
