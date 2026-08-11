# Flutter / Laravel contract audit

## Evidence inspected

- Flutter role features: authentication, common, fisher, processor,
  transporter, retailer; repositories, DTOs, GetX controllers, and Drift.
- Laravel API routes, migrations, models, Form Requests, Resources, Actions,
  policies, and feature tests.

## Findings

- The standard Laravel response envelope is already `data` plus
  `meta.request_id`.
- Flutter's `ApiData` supports snake-case responses, but individual DTOs
  remain the contract boundary and must not use display labels as API keys.
- Boat had three confirmed form fields without Laravel persistence/resource
  support: length, engine details, and home port. They are corrected in the
  2026_08_03_000016 additive migration and end-to-end DTO path.
- Boat type previously used display strings in mock/UI and unconstrained
  strings in Laravel. It is now serialized as a documented uppercase enum.
- The Fisher trip sync dropped trip code, planned departure, expected duration,
  crew, GPS coordinates, and notes. Migration `000017` and dedicated request
  DTOs now preserve them; crew is normalized into its own relationship table.
- Catch condition, GPS, notes, gear, uploaded images, and batch linkage now
  round-trip through the authenticated API.
- Batch handling metadata now persists, and batches are constrained to catches
  from one fishing trip.
- Fisher list endpoints now honor documented search/status/sort/direction
  parameters instead of silently ignoring Flutter queries.

## Status

The Boat and Fisher trip/catch/batch/QR/timeline phase is reconciled for new
mobile writes. Processor, transport/IoT, retail, and common auxiliary modules
still require exhaustive field-level verification.

## Processor audit

- Confirmed and fixed: Flutter submitted a grade code while the inspection sync
  path required an unavailable quality-grade UUID. Laravel now accepts either
  canonical input, resolves codes server-side, and retains UUID responses.
- Confirmed and fixed: inspection result is now derived from the visible
  criterion results (`PASSED`, `CONDITIONAL`, or `FAILED`) instead of always
  being sent as `PASSED`.
- Fixed: the processing screen creates one server record, resolves returned
  step UUIDs, and starts/completes the four ordered steps with their required
  measurements. Operator and processing-area assignment values now persist.

## Transporter audit in progress

- Fixed: vehicle capacity and the displayed type, refrigeration, temperature
  range, reefer-unit, and default-driver values now have additive database and
  Resource fields. Capacity is no longer discarded by mobile sync.
- Fixed: the visible checklist now represents Laravel's five canonical keys;
  sync serializes the actual selected states instead of fabricated values.
- Fixed: delivery photos and the drawn signature are uploaded separately as
  `DELIVERY_IMAGE` and `DELIVERY_SIGNATURE` assets linked to the trip.
- Still open: transport-trip creation has no active mobile creation form, and
  the remaining incident/alert read contracts require field-level
  verification before the Transport/IoT phase is complete.

## IoT telemetry audit

- Fixed: Laravel exposes nullable `product_temperature`, `air_temperature`,
  `humidity`, GPS, and `battery_percentage` fields. Flutter had instead read
  different keys and converted absent data into zeroes and default coordinates.
  The shared telemetry domain and Laravel/Firebase mappers now retain absence.
- Fixed: the live-monitoring screen now reads from `LiveMonitoringController`,
  which uses the Firebase stream with Laravel REST-history fallback. It reports
  actual timestamps and connection state and omits chart points for readings
  without a product temperature.
- Verified statically: the focused Flutter analyzer completed with no issues.
  The combined integration file requires a clean-process test rerun because it
  exceeded 60 seconds without output in this workspace.
- Fixed: the transport Alerts tab no longer renders a hardcoded temperature,
  battery, and door list. It maps the Laravel `ColdChainAlertResource`, queues
  `POST /alerts/{id}/acknowledge`, and exposes a validated incident form that
  queues `POST /transport-trips/{id}/incidents`.

## Retail reports audit

- Fixed: the active Reports screen previously used a fixed 2024 date label,
  invented percentage deltas, static chart points, and a fabricated offline
  export confirmation. It now uses typed summary, sales, and inventory report
  entities from the Laravel report endpoints.
- Fixed: the mobile date-range picker serializes canonical `date_from` and
  `date_to` (`YYYY-MM-DD`) query parameters accepted by `ReportFilterRequest`.
- Deliberately removed: the report screen's Quality tab. Laravel exposes no
  retailer quality-report endpoint, so keeping it would require fabricated
  data. This is recorded as an API capability boundary, not a UI-only value.

## Retail alerts audit

- Fixed: `RetailAlertResource` fields for status, measured value, threshold,
  and detection time were previously discarded by Flutter's alert DTO. They
  now round-trip into the retailer alert domain and screen.
- Fixed: resolving an alert now collects Laravel's required note (minimum five
  characters) and queues the canonical `POST /retailer/alerts/{id}/resolve`
  request. Recall quarantine remains a distinct write to
  `POST /retailer/recalls/{id}/quarantine`.

## Shared notifications audit

- Fixed: notification classification previously searched enum strings for the
  words `ALERT` and `SYSTEM`, causing canonical types such as
  `HIGH_TEMPERATURE`, `DEVICE_OFFLINE`, and `RECALL` to appear as updates.
  Classification and severity now use the Laravel `NotificationType` values.
- Fixed: API notifications no longer receive a hardcoded `Today` group. The
  typed notification DTO preserves UTC `created_at`, `read_at`, and allowlisted
  context, with local conversion used only for display grouping.
- Fixed: the repository requests the maximum supported page size and follows
  Laravel paginator metadata until every page is loaded. Individual mark-read
  remains connected to `POST /notifications/{id}/read`.
- Backend-only boundary: unread-count and read-all endpoints remain available,
  but the active Flutter screen has no read-all control and derives its badge
  from the fully loaded typed list.

## Profile and organization audit

- Fixed: the active Flutter name-edit dialog previously displayed a success
  message while API mode only stored a process-local override. It now writes to
  authenticated `PUT /auth/profile`, updates the active session from the
  returned typed user, and retains the current email when only name is edited.
- Fixed: Business Information previously displayed a hardcoded cooperative and
  licence. `UserResource` and Flutter's shared `UserDto` now preserve the
  primary organization's id, name, code, type, and active state.
- Scope decision: the camera glyph was not an interactive image picker and the
  users table has no avatar contract, so no avatar database field was inferred.
  Actual file/avatar support remains part of the file-contract audit.

## Private file contract audit

- Fixed: API-mode uploads previously read nonexistent camelCase `url` and
  `mimeType` response keys. A typed file DTO now maps the complete snake_case
  `FileAssetResource`, including the authorized `download_url`, entity linkage,
  original name, MIME type, size, checksum, and UTC creation time.
- Fixed: mock uploads now expose the same metadata-bearing domain entity as API
  uploads instead of a three-field approximation.
- Confirmed boundary: Laravel intentionally streams binary content from the
  descriptor's authenticated `GET /files/{file}` URL. There is no active
  generic file-list screen or repository call in Flutter; batch document lists
  remain a separate authorized backend workflow, so no speculative list UI or
  endpoint was introduced.

## Device settings audit

- Fixed: Preferences, Units & Measurements, Language, and Notification
  Settings previously closed with a success snackbar but discarded the value.
  They now use a typed `MobileSettings` entity, controller, and versioned
  secure-storage repository loaded before the app starts.
- Fixed: current selections are visible in the profile rows and sheets; compact
  density and device-theme choices update the app shell reactively, while the
  temperature-alert choice gates local cold-chain notifications.
- Scope decision: these are device-owned preferences, so mock and API modes
  share the same local repository. Canonical API/database measurements remain
  metric and no speculative Laravel settings table was added.

## AI prediction audit

- Confirmed boundary: Flutter contains no active AI screen, route, domain model,
  repository, request control, or result view. AI notification types alone do
  not justify inventing a mobile workflow, so the existing authenticated
  Laravel list/latest/request endpoints remain backend-only.
- Fixed: provider numeric strings could pass validation and leak into the JSON
  probability map. All providers now pass through one normalizer that enforces
  LOW/MEDIUM/HIGH, float confidence and probabilities summing to one, and
  non-empty recommendation/model/provider metadata.
- Fixed: private feature aggregation no longer converts missing product, air,
  or humidity telemetry to zero. It publishes presence and reading-count fields,
  and `timeAboveLimitMinutes` now measures ordered reading intervals rather than
  counting samples.
- Fixed: `predicted_at` is explicitly returned as UTC ISO-8601; sensitive input
  features and requester/provider credentials remain excluded from resources.
