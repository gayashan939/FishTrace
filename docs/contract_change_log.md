# Contract change log

- Added migration `2026_08_03_000016_add_mobile_contract_fields_to_boats_table`.
- Added `boats.length_meters` (decimal 8,2), `engine_details` (varchar 160),
  and `home_port` (varchar 120). All are nullable at database level only to
  preserve existing records; the mobile write contract requires them.
- Replaced the Flutter internal Boat property `engine` with `engineDetails`.
- Canonicalized Boat type write values to `LONG_LINER`, `GILLNETTER`, and
  `DAY_BOAT`; labels remain unchanged in the Flutter UI.
- Added exact create/update resource contract coverage in
  `BoatMobileContractTest` and updated Flutter API fixture parsing.
- Added migration `2026_08_08_000017_reconcile_fisher_mobile_contracts` for
  trip planning/crew, catch context, and batch handling metadata.
- Added dedicated Flutter `CreateFishingTripRequestDto`,
  `CreateCatchRequestDto`, and `CreateBatchRequestDto` serializers.
- Added uppercase catch-condition, quality-grade, and ice-type wire values.
- Added authenticated catch image descriptors and exact gear/batch nesting.
- Added Fisher directory filtering and deterministic sorting.
- Allowed Processor quality inspections to submit canonical `quality_grade`
  codes while preserving UUID-backed database relationships.
- Derived inspection result from Flutter criterion outcomes and corrected the
  `Odour`/`odor` field mapping.
- Added migration `2026_08_08_000018_add_mobile_assignment_fields_to_processing_records`
  for `operator_name` and `processing_area`.
- Wired the Flutter Processor workflow to create one processing record and to
  start and complete each ordered server step using its returned UUID.
- Added explicit cleaning, grading, freezing, and packaging measurement inputs
  and exact Processor write DTOs, shared fixtures, and serialization coverage.
- Added migration `2026_08_08_000019_add_mobile_contract_fields_to_vehicles_table`
  for the visible vehicle capacity, type, refrigeration, temperature-range, and
  default-driver details.
- Replaced fabricated checklist sync values with the five canonical Laravel
  checklist keys and matching visible mobile labels.
- Added exact Transporter request DTOs and distinct `DELIVERY_IMAGE` and
  `DELIVERY_SIGNATURE` uploads for delivery confirmation.
- Added migration `2026_08_08_000020_add_mobile_distance_to_transport_trips_table`;
  trip list/detail responses now expose estimated distance and the latest
  permanent product temperature using explicit unit-qualified keys.
- Removed fabricated condition-temperature and note values from the Retail
  receipt screen; optional receipt fields are now omitted when the user did
  not supply them.
- Added migration `2026_08_08_000021_add_mobile_display_fields_to_inventory_lots_table`
  for optional default unit price and low-stock threshold values. Flutter now
  represents unset values as absent instead of showing invented zeros.
- Reconciled Retail stock and sale units: Flutter now submits whole package
  counts required by Laravel and derives displayed kilograms from each package
  label's package weight.
- Connected the Retail Reports screen to the existing summary, sales, and
  inventory endpoints with typed DTOs, domain entities, mock/API repositories,
  a GetX controller, and canonical `date_from` / `date_to` filter keys.
- Removed fabricated Retail report date text, metric deltas, chart series,
  quality tab, and offline-export claim. The screen now exposes only the
  report contracts Laravel currently provides.
- Reconciled the IoT telemetry read contract: Flutter now reads Laravel's
  `product_temperature`, `air_temperature`, and `battery_percentage` keys,
  preserves nullable readings instead of substituting zeroes or coordinates,
  and parses both UTC ISO-8601 and Firebase epoch-millisecond timestamps.
- Connected the monitoring overview to its `LiveMonitoringController` and
  removed fabricated telemetry timestamps, status, GPS strength, and chart
  samples when no reading exists.
- Connected the monitoring Alerts tab to Laravel's paginated trip-alert feed,
  added canonical acknowledgement writes, and replaced the static incident
  confirmation with an offline-safe incident form targeting the existing
  `transport-trips/{id}/incidents` contract.
- Reconciled Retail alert details and resolution: Flutter preserves alert
  status, measured and threshold values, and timestamps, and now submits the
  required resolution note to `retailer/alerts/{id}/resolve` while retaining
  the existing recall-quarantine flow.
- Reconciled shared notifications: Flutter now parses the nested Laravel
  notification payload through a typed DTO, preserves allowlisted context and
  UTC timestamps, classifies canonical notification enums correctly, retrieves
  all paginated records, and posts mark-read mutations to the published route.
- Added authenticated `PUT|PATCH /auth/profile` for the active mobile name-edit
  flow. The mutation preserves roles and organization memberships, records a
  `PROFILE_UPDATED` audit event, and returns primary organization code, type,
  and active state through `UserResource`.
- Replaced Flutter's API-mode profile-only local override and fabricated
  cooperative/licence text with the persisted profile response and actual
  primary-organization details.
- Replaced snackbar-only profile settings with a typed, versioned mobile
  settings repository. Choices survive restarts in secure device storage;
  compact density and device theme update the app shell, and the temperature
  notification preference gates local cold-chain alerts. These device-owned
  values intentionally do not add Laravel user columns.
- Added centralized AI prediction-result normalization. Provider numeric strings
  now persist and serialize as floats; probability maps are restricted to LOW,
  MEDIUM, and HIGH and must sum to one; required recommendation, model version,
  and provider metadata cannot be empty. AI feature aggregation now preserves
  missing telemetry as null and calculates `timeAboveLimitMinutes` from reading
  intervals instead of treating sample count as minutes.
- Replaced loose file-upload response parsing (`url`/`mimeType`) with the
  canonical Laravel `FileAsset` descriptor (`download_url`, `mime_type`, entity
  metadata, checksum, size, and UTC creation time). Mock uploads now return the
  same domain shape, and OpenAPI documents upload, authorized download, and
  delete contracts.
