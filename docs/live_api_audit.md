# Live API and Postman contract audit

Audit date: 2026-08-03

Flutter base URL: `http://192.168.1.120:8002/api/v1`

Postman sources:

- `D:\Web\FishTrace\postman\FishTrace.postman_collection.json`
- `D:\Web\FishTrace\postman\FishTrace.local.postman_environment.json`

## Verification result

- The current Postman collection contains 167 requests.
- 54 Flutter mobile method/path pairs were compared against the collection.
- 49 pairs match directly. Active client calls to absent common routes were
  removed or replaced by explicit local behavior.
- FISHER, PROCESSOR, TRANSPORTER and RETAILER login, `/auth/me`, notifications,
  principal role lists and logout passed against the live server. All audit
  tokens were revoked.
- Main live reads returned HTTP 200: boats, fishing trips, catches, batches,
  processor incoming/history, transport trips, vehicles, IoT devices, retail
  inventory, alerts, sales and receipts.
- Laravel paginator responses use `data.data`; Flutter unwraps this shape.
- Laravel decimal columns can arrive as strings. Numeric parsing now safely
  accepts both JSON numbers and numeric strings.

No destructive or business mutation was sent to the live seeded database.
Mutation requests were verified with capturing Dio adapter tests.

## Client fixes applied

- Aligned login and mock credentials with Postman: `FishTrace@2026`.
- Corrected the active fishing-trip filter from `IN_PROGRESS` to `ACTIVE`.
- Mapped nested Laravel resources for species, batches, processing records,
  transport trips, IoT capabilities, retail inventory, receipts, sales,
  alerts and notifications.
- Added record-specific offline-sync request mapping. Catch, batch, trip,
  intake, processing, split, vehicle, device, checklist, delivery, receipt,
  sale, stock and recall bodies now use the Postman field names and shapes.
- Fisher species, gear and landing-site UUIDs resolve through
  `/fisher/reference-data`.
- Local-to-server IDs are retained during a sync run for dependent operations.
- Draft-only processing and inspection saves no longer submit incomplete API
  mutations.
- Retail receipt loading now uses `/retail/incoming-labels` and preserves the
  received package count.
- Multipart uploads now send `category`, `entity_type` and `entity_id`; queued
  media uploads after its owning record exists.
- Removed guaranteed 404 calls for help/support/profile update. Help content,
  support acknowledgement and profile edits use scoped local behavior.

## Remaining backend limitations

1. There is no processor reference-data endpoint for quality-grade UUIDs.
   `POST /quality-inspections` requires `quality_grade_id`. Flutter reports
   `missing_quality_grade_reference` rather than sending invalid data or
   hard-coding a database UUID.
2. There are no mobile profile-update, help-article or support-ticket routes.
3. There is no `/auth/refresh` route and login issues no refresh token. Expired
   Sanctum sessions are cleared and return to login.
4. Unauthenticated protected requests return HTTP 500 with an
   `Unauthenticated.` payload instead of HTTP 401. Flutter has a compatibility
   mapping.
5. Firebase requires a valid backend service account and platform files. The
   verified build used `FIREBASE_ENABLED=false`.
6. Production Android builds must use HTTPS. LAN cleartext HTTP is debug/profile
   only.

## Verification commands

- `flutter analyze` — PASS, no issues
- `flutter test` — PASS, 78 tests
- API debug APK build with the live LAN URL and Firebase disabled — PASS
- Artifact: `build/app/outputs/flutter-apk/app-debug.apk`
