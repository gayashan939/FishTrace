# Administrator processor operations

The Processor Operations console is available under `/admin/processor/*` and is restricted to administrators. It provides cross-organization, read-only operational visibility from facility intake through processing, inspection, splitting, packaging, and retailer custody.

## Operational surfaces

- Facilities link processor profiles, organizations, licensed operators, and their newest 100 intake and processing records.
- The intake queue exposes accepted and rejected batches, received weight, receiver, decision notes, and subsequent processing status.
- Processing records show input/output/waste reconciliation, processing type, ordered steps, recorded measurements, operators, inspections, child batches, and package labels.
- Quality inspection pages expose result, grade, product temperature, pH, sensory findings, inspector, and links back to authoritative processing and traceability records.
- Package label pages connect child batches to package counts, weight, parent batches, processor ownership, retailer receipts, locations, and inventory state.

All directories use validated server-side filters and pagination. Detail collections are capped at 100 records where a facility can accumulate unbounded history.

## Privacy and authority boundaries

The console reads permanent MySQL/MariaDB records and does not mutate processor workflows. Cross-organization access is administrator-only; processor and inspector API policies remain organization-scoped.

Package `public_token` values are hidden by the model and excluded from every page and export. The console exposes label codes for operations without leaking public trace URLs, authentication material, or private integration payloads.

## Exports and audit

Processing-record and quality-inspection CSV exports apply the same validated filters as their directories, are capped at 10,000 rows, and return safe download headers. Downloads create `PROCESSING_RECORDS_EXPORTED` or `QUALITY_INSPECTIONS_EXPORTED` audit records with filters, row count, actor, and request correlation.
