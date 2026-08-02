# Administrator compliance and reporting

The Compliance & Reporting console is available under `/admin/compliance/*` and is restricted to administrators. It consolidates quality incidents, downstream recalls, private evidence, operational notifications, report jobs, AI risk, and blockchain verification without changing the underlying workflows.

## Operational surfaces

- The quality incident queue isolates failed and conditional inspections and links findings to processing, source traceability, child-batch retail custody, and evidence.
- Recall monitoring finds both batch-level recalls and recalled downstream inventory lots, including affected retailer, location, label, and trace-event counts.
- Evidence pages expose safe file metadata and audited authorized downloads from private storage.
- Notification delivery shows recipient, organization, type, title, message, read state, and creation time.
- Report export history shows report type, organization, requester, processing state, timing, and completed artifacts.
- AI review shows prediction risk/confidence, model/provider, recommendations, and redacted service-failure health.
- Blockchain review links anchors to traceability events and batches and shows status plus the newest 100 verification results.

All directories use validated server-side filters and bounded pagination. Incident evidence and blockchain verification histories are capped at 100 records.

## Privacy and authority boundaries

The console never renders private file disk/path/checksum values, notification context payloads, report filters or failure bodies, AI input features or service error bodies, blockchain verification response bodies, credentials, or integration secrets. Evidence downloads verify administrator authorization, check private-storage existence, use safe response headers, and create an audit record.

## Exports and audit

Quality-incident and recall CSV exports apply validated filters, are capped at 10,000 rows, and use safe download headers. They create `COMPLIANCE_INCIDENTS_EXPORTED` and `COMPLIANCE_RECALLS_EXPORTED` audit records. Evidence downloads create `ADMIN_EVIDENCE_DOWNLOADED` records.
