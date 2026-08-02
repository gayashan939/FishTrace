# Fisher workflow

A FISHER creates an owned, active boat, creates and edits a draft trip, starts it, and records positive-weight catches while it is active. The dashboard returns status-grouped trip counts, the active trip, boat and catch totals, available versus allocated catch weight, the fisher's batch count, and at most five recent trips.

Boat, trip, catch, and batch directories are organization-scoped by the typed fisher query service and accept only validated pagination capped at 100 rows. Their API Resources explicitly whitelist the Flutter contract; QR public tokens and private traceability-event data are never serialized by authenticated list, detail, or timeline responses. Boat creation, update, and deletion use separate authorized Actions.

`GET /api/v1/fisher/reference-data` supplies the active species, fishing-gear types, and landing sites needed by Flutter trip and catch forms. Inactive registry values remain available on historical records but are never offered for a new workflow.

Catch edits and deletion are limited to an owned active trip. Offline `client_record_id` and trip assignment are immutable. Once any allocation exists, species is immutable, weight cannot fall below the greater of the stored allocation total and the batch-allocation ledger total, and deletion is rejected. Offline create replay returns the existing catch. Batch creation locks catches, verifies organization/species, rejects over-allocation, updates allocated weights atomically, issues a 64-character random public token, and records `BATCH_CREATED`.

Trip transitions are `DRAFT → ACTIVE → COMPLETED` or `DRAFT → CANCELLED`; generic status updates are not exposed.

Fisher-organization members can upload a PDF, JPEG, or PNG batch document up to 10 MiB with `POST /api/v1/batches/{batch}/documents`. The route derives the batch association instead of trusting client-supplied entity metadata. `GET` on the same endpoint returns private download descriptors; the generic authorized file endpoint handles download and uploader-owned deletion.
