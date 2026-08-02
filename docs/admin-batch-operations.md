# Administrator batch operations

The Batch Operations console provides administrators with a cross-organization registry and a traceability detail view backed entirely by permanent Laravel/MySQL records. Routes are under `/admin/batches`; authenticated users without the `ADMIN` role receive `403 Forbidden` even if their supply-chain role permits access to a batch through the mobile API.

## Registry

The registry supports batch-code/product search; status, organization, species, type, recall, public-visibility, and creation-date filters; whitelisted sorting; and pagination capped at 100 rows. Each row summarizes ownership, species, current state, weight, event/transport/split counts, and the latest AI risk assessment.

CSV export applies the same validated filters, is bounded to 10,000 rows, sets safe download headers, and records `BATCH_DIRECTORY_EXPORTED` with the actor, filters, request ID, and row count.

## Traceability detail

The detail page joins:

- catch allocations, fishing trip, catch area, and vessel;
- parent/child split lineage and allocated weights;
- up to 250 chronological public traceability events;
- processor intake, processing weights and ordered steps;
- quality inspections;
- transport trips, vehicles, assigned devices, aggregated sensor temperatures and battery, and cold-chain alert counts;
- latest AI risk, confidence, recommendation, provider, and model version;
- blockchain anchor counts by transaction status;
- retail locations and stock position; and
- immutable audit entries directly associated with the batch.

Raw telemetry payloads, GPS coordinates, trace-event private data, package public tokens, QR public tokens, credentials, and secrets are not rendered. Active QR images are generated through an administrator-authorized web route; revoked QR records return `404`.

## Source-of-truth boundaries

The console aggregates MySQL records only. Firebase remains temporary live telemetry and assignment-mirror infrastructure and is never queried as an authoritative batch source. AI and blockchain information is read from their persisted prediction/transaction records rather than calling external providers while rendering a page.
