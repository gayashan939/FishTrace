# Files, notifications, and reports

Batch-document, notification, and report-export directories use authorized Form Requests with pagination capped at 100 and typed query/operation services. Notification Resources retain the existing mobile `data` envelope but allow only approved context identifiers and operational metrics; arbitrary context and workflow reasons are excluded. Report-export Resources expose status, filters, timing, and authorized file metadata while keeping internal failure details private. Export creation and user-scoped notification read transitions are delegated outside controllers.

## Private file assets

`POST /api/v1/files` accepts one authorized attachment with a category, resource type, resource UUID, and file. Supported categories cover boat, catch, inspection, processing, batch, certificate, delivery, signature, and internally generated report-export files. Category/resource combinations are explicit; arbitrary model class names are not accepted.

For Fisher clients, `GET|POST /api/v1/batches/{batch}/documents` provides a safer route-bound contract. Uploads default to `BATCH_DOCUMENT`, optionally accept `CERTIFICATE`, and derive `entity_type` and `entity_id` from the authorized batch route. The list returns metadata and authorized download URLs without exposing storage internals.

Image uploads are content-validated JPEG, PNG, or WebP files up to 5 MiB with bounded dimensions. Documents are content-validated PDF, JPEG, or PNG files up to 10 MiB. Assets are stored on the configured private filesystem disk under randomized names, so the implementation can move from local storage to S3 without changing API contracts. The database retains MIME type, extension, byte size, and SHA-256. API responses never expose a disk name or private path.

`GET /api/v1/files/{file}` performs an organization policy check and returns a download with `nosniff`. `DELETE /api/v1/files/{file}` is limited to the uploader or an administrator in the same organization.

## Operational notifications

Database notifications are created for cold-chain warnings, processor acceptance/rejection, inspection outcomes, processing completion, transport start and delivery completion, retail receipt/recall, high AI risk, device-offline checks, Firebase assignment/import failures, blockchain verification failures, and report export outcomes. Repeated offline and external-verification failures use unique event keys to prevent notification floods.

- `GET /api/v1/notifications` supports `read`, `type`, and bounded pagination filters.
- `GET /api/v1/notifications/unread-count` returns the current user's unread count.
- `POST /api/v1/notifications/{notification}/read` can modify only the authenticated user's notification.
- `POST /api/v1/notifications/read-all` marks all of the current user's unread notifications.

## Reports and exports

The report catalog includes catch volume, species distribution, batch status, processing yield, quality grades, transport performance, cold-chain violations, device uptime, Firebase import failures, AI risk distribution, inventory, sales, recalls, and blockchain status. Reports accept date range, organization, species, and status filters where applicable. Organization selection remains restricted to the authenticated user's organization.

Each report is available as JSON at `GET /api/v1/reports/{report}`, bounded CSV at `/{report}/csv`, and print-friendly HTML at `/{report}/print`. `GET /api/v1/reports/summary` provides compact organization metrics.

Large CSV creation uses `POST /api/v1/reports/exports`. The idempotent queue job records processing state, writes the result through the configured private filesystem, creates a `REPORT_EXPORT` file asset, and notifies the requester on completion or terminal failure. Export status is available from `GET /api/v1/reports/exports/{export}`.
